<?php
/**
 * Authentication and Session Management
 * Role-Based Access Control (RBAC) & CSRF Protection
 */

require_once __DIR__ . '/../config/database.php';

// Start Session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate CSRF Token for form submissions
 * @return string
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token from POST request
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token($token) {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if a user is currently logged in
 * @return bool
 */
function is_logged_in() {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

/**
 * Get the currently logged-in user array
 * @return array|null
 */
function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'name'      => $_SESSION['user_name'] ?? '',
        'email'     => $_SESSION['user_email'] ?? '',
        'role'      => $_SESSION['user_role'] ?? '',
        'phone'     => $_SESSION['user_phone'] ?? '',
        'doctor_id' => $_SESSION['doctor_id'] ?? null
    ];
}

/**
 * Get current user role
 * @return string|null
 */
function get_user_role() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Enforce authentication and role permissions
 * Redirects to login if unauthenticated or unauthorized
 * 
 * @param array $allowed_roles e.g. ['patient'], ['doctor'], ['admin']
 */
function check_auth(array $allowed_roles = []) {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please log in to access this page.';
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    $current_role = get_user_role();
    if (!empty($allowed_roles) && !in_array($current_role, $allowed_roles, true)) {
        $_SESSION['flash_error'] = 'Access denied. You do not have permission to view that page.';
        // Redirect to their respective dashboard
        redirect_by_role($current_role);
        exit;
    }
}

/**
 * Redirect user to their appropriate dashboard based on their role
 * @param string $role
 */
function redirect_by_role($role) {
    switch ($role) {
        case 'admin':
            header('Location: ' . BASE_URL . '/admin/index.php');
            break;
        case 'doctor':
            header('Location: ' . BASE_URL . '/doctor/index.php');
            break;
        case 'patient':
            header('Location: ' . BASE_URL . '/patient/index.php');
            break;
        default:
            header('Location: ' . BASE_URL . '/index.php');
            break;
    }
    exit;
}
