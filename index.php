<?php
/**
 * Main Entry Point
 * Redirects to login page or appropriate dashboard
 */

require_once __DIR__ . '/includes/init.php';

// If user is already logged in, redirect to their dashboard
if (isLoggedIn()) {
    $dashboard = getRoleDashboard($_SESSION['user_role']);
    header('Location: ' . $dashboard);
    exit;
}

// Not logged in - redirect to login page
header('Location: /my_site/auth/login.php');
exit;
?>
