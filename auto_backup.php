<?php
// Encrypted backup runner. Execute from Windows Task Scheduler, not from page visibility events.
if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') session_start();
require_once __DIR__ . '/config.php';

function create_encrypted_backup($conn, $backup_dir, $triggered_by = 'Scheduled Task') {
    $encryption_key = getenv('BACKUP_ENCRYPTION_KEY') ?: ($_ENV['BACKUP_ENCRYPTION_KEY'] ?? '');
    if ($encryption_key === '') throw new RuntimeException('BACKUP_ENCRYPTION_KEY is not configured.');
    if (!is_dir($backup_dir) && !mkdir($backup_dir, 0700, true)) throw new RuntimeException('Unable to create the backup directory.');
    @file_put_contents($backup_dir . DIRECTORY_SEPARATOR . '.htaccess', "Deny from all\n");

    $sql_script = "-- C-More encrypted database backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql_script .= "-- Triggered by: " . preg_replace('/[^a-zA-Z0-9 _.-]/', '', $triggered_by) . "\n\nSET FOREIGN_KEY_CHECKS=0;\n";
    $tables_result = mysqli_query($conn, 'SHOW TABLES');
    if (!$tables_result) throw new RuntimeException(mysqli_error($conn));
    while ($table_row = mysqli_fetch_row($tables_result)) {
        $table = $table_row[0];
        $sql_script .= "\nDROP TABLE IF EXISTS `$table`;\n";
        $create_result = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
        if (!$create_result) throw new RuntimeException(mysqli_error($conn));
        $create_row = mysqli_fetch_row($create_result);
        $sql_script .= $create_row[1] . ";\n";
        $data_result = mysqli_query($conn, "SELECT * FROM `$table`");
        if (!$data_result) throw new RuntimeException(mysqli_error($conn));
        while ($row = mysqli_fetch_assoc($data_result)) {
            $values = array_map(function ($value) use ($conn) {
                return $value === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, $value) . "'";
            }, array_values($row));
            $keys = array_map(function ($key) { return '`' . $key . '`'; }, array_keys($row));
            $sql_script .= "INSERT INTO `$table` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
        }
    }
    $sql_script .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

    $key = hash('sha256', $encryption_key, true);
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($sql_script, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($ciphertext === false) throw new RuntimeException('OpenSSL encryption failed.');

    $archive_name = 'cmore_' . date('Y-m-d_H-i-s') . '.sql.enc';
    $archive_path = $backup_dir . DIRECTORY_SEPARATOR . $archive_name;
    $archive = json_encode(['format' => 'cmore-aes-256-gcm-v1', 'iv' => base64_encode($iv), 'tag' => base64_encode($tag), 'data' => base64_encode($ciphertext)], JSON_THROW_ON_ERROR);
    if (file_put_contents($archive_path, $archive, LOCK_EX) === false) throw new RuntimeException('Unable to write encrypted backup.');
    if (!copy($archive_path, $backup_dir . DIRECTORY_SEPARATOR . 'cmore_latest_backup.sql.enc')) throw new RuntimeException('Unable to update the latest backup pointer.');
    @unlink($backup_dir . DIRECTORY_SEPARATOR . 'cmore_latest_backup.sql');

    $verification = json_decode(file_get_contents($archive_path), true, 512, JSON_THROW_ON_ERROR);
    $verified_sql = openssl_decrypt(base64_decode($verification['data']), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, base64_decode($verification['iv']), base64_decode($verification['tag']));
    if ($verified_sql === false || hash('sha256', $verified_sql) !== hash('sha256', $sql_script)) {
        throw new RuntimeException('Encrypted backup verification failed.');
    }

    // Keep the latest 14 daily archives and one archive for each of the latest 8 weeks.
    $archives = glob($backup_dir . DIRECTORY_SEPARATOR . 'cmore_????-??-??_??-??-??.sql.enc') ?: [];
    usort($archives, function ($a, $b) { return filemtime($b) <=> filemtime($a); });
    $keep = [];
    $weekly_keys = [];
    foreach ($archives as $index => $archive_file) {
        $week_key = date('o-W', filemtime($archive_file));
        $has_week = false;
        foreach ($keep as $kept_file) if (date('o-W', filemtime($kept_file)) === $week_key) $has_week = true;
        if ($index < 14 || (!$has_week && count($weekly_keys) < 8)) {
            $keep[] = $archive_file;
            if (!$has_week) $weekly_keys[] = $week_key;
        }
    }
    foreach ($archives as $archive_file) if (!in_array($archive_file, $keep, true)) @unlink($archive_file);
    return ['name' => $archive_name, 'count' => count($keep)];
}

$backup_dir = __DIR__ . DIRECTORY_SEPARATOR . 'backups';
$triggered_by = PHP_SAPI === 'cli' ? 'Scheduled Task' : ($_SESSION['NAME'] ?? 'Manual Backup');
try {
    $result = create_encrypted_backup($conn, $backup_dir, $triggered_by);
    if (PHP_SAPI !== 'cli' && isset($_GET['logout']) && $_GET['logout'] === 'true') {
        systemLog($conn, 'Encrypted database backup completed upon staff logout');
        session_destroy();
        header('Location: login.php');
        exit();
    }
    if (PHP_SAPI === 'cli') echo "Backup created: {$result['name']}\n";
} catch (Throwable $exception) {
    error_log('[C-More Backup Failure] ' . $exception->getMessage());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Backup failed: {$exception->getMessage()}\n");
        exit(1);
    }
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Encrypted backup failed. Check the server error log.'];
    if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
        // Keep the session active so staff can retry instead of losing unsaved work.
        header('Location: backup.php');
        exit();
    }
}
