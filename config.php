<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);

if (empty($_SESSION['USER_ID'])) {
    if ($current_page !== 'login.php') {
        header('Location: login.php');
        exit();
    }
} elseif ($current_page === 'login.php' && !isset($_GET['action'])) {
    header('Location: directory.php');
    exit();
}

// Secure .env parser for native PHP
$env_path = __DIR__ . '/.env';
if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\""); // Strip spaces and quotes
            
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Fetch DB credentials from .env, fallback to defaults if .env is missing
$host = $_ENV['DB_HOST'] ?? "localhost";
$user = $_ENV['DB_USER'] ?? "root";
$pass = $_ENV['DB_PASS'] ?? "";
$db   = $_ENV['DB_NAME'] ?? "cmore";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("<div style='color:red; font-family:sans-serif;'>
            <strong>Connection Error:</strong> " . mysqli_connect_error() . "
         </div>");
}

// ==========================================
// SESSION LOCK ENFORCEMENT
// ==========================================
// If the session is locked, force them to the lock screen.
$current_page = basename($_SERVER['PHP_SELF']);

// We exclude login.php and lock.php to prevent infinite redirect loops
if (isset($_SESSION['is_locked']) && $_SESSION['is_locked'] === true) {
    if ($current_page !== 'lock.php' && $current_page !== 'login.php') {
        header("Location: lock.php");
        exit();
    }
}

// ==========================================
// GLOBAL AUDIT LOG FUNCTION
// ==========================================
// Use this function across your system to track actions.
// Example usage: systemLog($conn, "Added new patient", "patients", $new_patient_id);

if (!function_exists('systemLog')) {
    function systemLog($conn, $action, $table_name = NULL, $record_id = NULL) {
        if(isset($_SESSION['USER_ID'])) {
            $uid = $_SESSION['USER_ID'];
            $act = mysqli_real_escape_string($conn, $action);
            $tbl = $table_name ? "'" . mysqli_real_escape_string($conn, $table_name) . "'" : "NULL";
            $rid = $record_id ? intval($record_id) : "NULL";
            
            $sql = "INSERT INTO audit_log (USER_ID, ACTION, TABLE_NAME, RECORD_ID) 
                    VALUES ($uid, '$act', $tbl, $rid)";
            mysqli_query($conn, $sql);
        }
    }
}
?>