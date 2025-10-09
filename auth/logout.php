<?php
/**
 * Logout Handler
 * Destroys session and redirects to login
 */

require_once __DIR__ . '/../includes/init.php';

// Log out user
authLogout();

// Redirect to login page
header('Location: /my_site/auth/login.php');
exit;

?>
