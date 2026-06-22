<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Store the last page that user was on before session locked
if (isset($_POST['last_page'])) {
    $last_page = trim($_POST['last_page']);
    // Only store if it's a valid format (prevents open redirect attacks)
    if (!empty($last_page) && preg_match('/^[a-zA-Z0-9_\-\.]+\.php(\?[a-zA-Z0-9_=&\-]+)?$/', $last_page)) {
        $_SESSION['last_page'] = $last_page;
    }
}

// Return success response
http_response_code(200);
?>
