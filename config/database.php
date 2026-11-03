<?php
/**
 * Database Connection & Global Configuration
 * Doctor Appointment System - Core PHP & MySQL
 */

// Set Default Timezone (IST - Indian Standard Time or local server time)
date_default_timezone_set('Asia/Kolkata');

// Database Credentials (Standard XAMPP default)
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'doctor_appointment_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Dynamic Application BASE_URL detection
// Works automatically on both "http://localhost/doctor_appointment_system/" and "http://localhost:8000/"
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // Strip sub-folders if called from inside patient/, doctor/, admin/, api/, etc.
    $cleanPath = preg_replace('/(\/(patient|doctor|admin|api|includes|config))$/', '', $scriptDir);
    if ($cleanPath === '/' || $cleanPath === '\\') {
        $cleanPath = '';
    }
    define('BASE_URL', rtrim($cleanPath, '/'));
}

// Global Application Name
define('APP_NAME', 'DocCare Medical Clinic');

/**
 * Get PDO Database Connection
 * @return PDO
 */
function get_db_connection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Friendly error message for demonstration
            die("<strong>Database Connection Error:</strong> Unable to connect to MySQL database.<br>" .
                "Please verify that MySQL service is running in XAMPP and database <code>" . DB_NAME . "</code> exists.<br>" .
                "<em>Details: " . htmlspecialchars($e->getMessage()) . "</em>");
        }
    }

    return $pdo;
}
