<?php
/**
 * User Logout Script
 * Destroys session securely and redirects to login
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if set
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

// Destroy the session
session_destroy();

// Start a new session just for the flash message
session_start();
set_flash('success', 'You have been successfully logged out. See you soon!');
header('Location: ' . BASE_URL . '/login.php');
exit;
