<?php
// includes/auth.php

if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session cookie parameters
    session_set_cookie_params([
        'lifetime' => 0,              // Session lasts until browser closes
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']), // True if using HTTPS
        'httponly' => true,           // Mitigate XSS attacks
        'samesite' => 'Lax'           // Protect against CSRF
    ]);
    session_start();
}

/**
 * Regenerates session ID to prevent Session Fixation attacks.
 */
function regenerate_secure_session(array $userData = []): void {
    // Prevent session fixation by deleting the old session ID
    session_regenerate_id(true);

    // Generate a secure random token for session tracking/CSRF checks
    $_SESSION['session_token'] = bin2hex(random_bytes(32));
    $_SESSION['logged_in']     = true;
    $_SESSION['last_active']   = time();

    foreach ($userData as $key => $value) {
        $_SESSION[$key] = $value;
    }
}

/**
 * Auth Middleware: Requires authentication to view protected pages.
 */
function require_auth(): void {
    // Session expiration check (e.g., 30 minutes of inactivity)
    $maxInactivity = 1800; // 30 mins
    if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active'] > $maxInactivity)) {
        logout_user();
        header('Location: /NovaDesk/auth/login.php?error=expired');
        exit();
    }

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: /NovaDesk/auth/login.php');
        exit();
    }

    // Refresh active timestamp
    $_SESSION['last_active'] = time();
}

/**
 * Destroys session completely on logout.
 */
function logout_user(): void {
    $_SESSION = [];

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

    session_destroy();
}