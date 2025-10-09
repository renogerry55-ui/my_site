<?php
/**
 * Application Initialization
 * Load environment, start secure session, include core dependencies
 */

// Define init constant
define('APP_INIT', true);

// Start output buffering
ob_start();

// Load environment configuration
require_once __DIR__ . '/../config/.env.php';

// Error reporting based on environment
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Enable secure cookies in production
if (APP_ENV === 'production') {
    ini_set('session.cookie_secure', 1);
}

// Start session with custom name
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();

    // Regenerate session ID periodically
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Include core dependencies
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

?>
