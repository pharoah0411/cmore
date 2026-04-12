<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "cmore";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("<div style='color:red; font-family:sans-serif;'>
            <strong>Connection Error:</strong> " . mysqli_connect_error() . "
         </div>");
}
?>