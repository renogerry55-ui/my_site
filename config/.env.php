<?php
/**
 * Environment Configuration
 * IMPORTANT: Keep this file secure and never commit to version control
 */

// Prevent direct access
if (!defined('APP_INIT')) {
    die('Direct access not permitted');
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'br');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP MySQL password is empty
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'Daily Closing Web System');
define('APP_URL', 'http://localhost/my_site');
define('APP_ENV', 'development'); // development or production

// Session Configuration
define('SESSION_NAME', 'MY_SITE_SESSION');
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Security Settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_EXPIRY', 1800); // 30 minutes

// Timezone
date_default_timezone_set('Asia/Manila'); // Adjust to your timezone

?>
