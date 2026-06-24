<?php
// Silently run backup and optionally logout
if (session_status() === PHP_SESSION_NONE) session_start();
include('config.php');

$backup_dir = 'backups/';
$backup_file = $backup_dir . 'cmore_latest_backup.sql';

if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
    file_put_contents($backup_dir . '.htaccess', "Deny from all");
}

// Perform Backup Dump
$sqlScript = "-- C-More Clinical Suite Database Backup (Auto-Triggered)\n";
$sqlScript .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sqlScript .= "-- Triggered By: " . htmlspecialchars($_SESSION['NAME'] ?? 'System Tab Closed') . "\n";
$sqlScript .= "-- --------------------------------------------------------\n\n";
$sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

$tables = [];
$result = mysqli_query($conn, "SHOW TABLES");
if ($result) {
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }

    foreach ($tables as $table) {
        $sqlScript .= "\n-- Table structure for table `$table`\n";
        $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
        $create_table_result = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
        $create_table_row = mysqli_fetch_row($create_table_result);
        $sqlScript .= $create_table_row[1] . ";\n\n";

        $sqlScript .= "-- Data for table `$table`\n";
        $data_result = mysqli_query($conn, "SELECT * FROM `$table`");
        
        while ($row = mysqli_fetch_assoc($data_result)) {
            $keys = array_keys($row);
            $values = array_map(function($value) use ($conn) {
                if ($value === null) return 'NULL';
                return "'" . mysqli_real_escape_string($conn, $value) . "'";
            }, array_values($row));
            
            $sqlScript .= "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $values) . ");\n";
        }
    }
    $sqlScript .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

    file_put_contents($backup_file, $sqlScript);
}

// 1. If triggered via explicit "Logout & Backup" button
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    systemLog($conn, 'Automatic backup completed upon staff logout');
    session_destroy();
    header("Location: login.php");
    exit();
}

// 2. If triggered invisibly when browser tab is closed (Beacon API)
if (isset($_GET['bg']) && $_GET['bg'] === 'true') {
    http_response_code(200);
    exit();
}