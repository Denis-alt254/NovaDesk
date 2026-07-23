<?php
// auth/logout.php

require_once __DIR__ . '/../includes/security.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Clear all session variables in memory
$_SESSION = array();

// 2. Destroy the session cookie in the client's browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 3. Destroy session data on the server
session_destroy();

// 4. Redirect to login with strict cache controls to prevent Back-Button viewing
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies
header("Location: login.php?logged_out=1");
exit();