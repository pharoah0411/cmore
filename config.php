<?php
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
?>