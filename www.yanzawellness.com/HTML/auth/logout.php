<?php
/**
 * ==========================================================================
 * YANZA WELLNESS LOGOUT HANDLER
 * Unsets session arrays, destroys browser session cookies, exits securely.
 * ==========================================================================
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Unset all session variables in memory
$_SESSION = [];

// 2. Erase the session cookie stored in the visitor's browser.
// This completely unlinks the browser from the server session.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session on the server
session_destroy();

// 4. Redirect the logged-out visitor back to the landing page
header('Location: ../index.php');
exit;
