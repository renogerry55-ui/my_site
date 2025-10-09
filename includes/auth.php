<?php
/**
 * Authentication Helper Functions
 */

// Prevent direct access
if (!defined('APP_INIT')) {
    die('Direct access not permitted');
}

/**
 * Attempt to log in a user
 * @param string $identifier Email or username
 * @param string $password Plain text password
 * @return array ['success' => bool, 'message' => string, 'user' => array|null]
 */
function authLogin($identifier, $password) {
    // Validate inputs
    if (empty($identifier) || empty($password)) {
        return [
            'success' => false,
            'message' => 'Invalid credentials provided.',
            'user' => null
        ];
    }

    // Fetch user by email or username
    $sql = "SELECT * FROM users
            WHERE (email = :identifier1 OR username = :identifier2)
            AND status = 'active'
            LIMIT 1";

    $user = dbFetchOne($sql, [
        'identifier1' => $identifier,
        'identifier2' => $identifier
    ]);

    if (!$user) {
        // Generic error - don't reveal if user exists
        return [
            'success' => false,
            'message' => 'Invalid credentials provided.',
            'user' => null
        ];
    }

    // Verify password using Argon2id
    if (!password_verify($password, $user['password_hash'])) {
        // Log failed attempt
        error_log("Failed login attempt for: {$identifier}");

        return [
            'success' => false,
            'message' => 'Invalid credentials provided.',
            'user' => null
        ];
    }

    // Check if password needs rehashing (if algorithm/cost changed)
    if (password_needs_rehash($user['password_hash'], PASSWORD_ARGON2ID)) {
        $newHash = password_hash($password, PASSWORD_ARGON2ID);
        dbQuery(
            "UPDATE users SET password_hash = :hash WHERE id = :id",
            ['hash' => $newHash, 'id' => $user['id']]
        );
    }

    // Update last login timestamp
    dbQuery(
        "UPDATE users SET last_login = NOW() WHERE id = :id",
        ['id' => $user['id']]
    );

    // Store user data in session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();

    // Regenerate session ID on login
    session_regenerate_id(true);

    return [
        'success' => true,
        'message' => 'Login successful.',
        'user' => $user
    ];
}

/**
 * Log out current user
 */
function authLogout() {
    // Unset all session variables
    $_SESSION = [];

    // Delete session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // Destroy session
    session_destroy();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Require authentication - redirect to login if not authenticated
 * @param string $redirectTo URL to redirect to after login
 */
function requireAuth($redirectTo = '/my_site/auth/login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirectTo);
        exit;
    }

    // Check session timeout
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > SESSION_LIFETIME)) {
        authLogout();
        header('Location: ' . $redirectTo . '?timeout=1');
        exit;
    }
}

/**
 * Require specific role(s)
 * @param string|array $allowedRoles
 * @param string $redirectTo
 */
function requireRole($allowedRoles, $redirectTo = '/my_site/auth/login.php') {
    requireAuth($redirectTo);

    $allowedRoles = (array) $allowedRoles;
    $userRole = $_SESSION['user_role'] ?? '';

    if (!in_array($userRole, $allowedRoles, true)) {
        // Unauthorized - redirect to appropriate dashboard
        header('Location: ' . getRoleDashboard($userRole));
        exit;
    }
}

/**
 * Get dashboard URL for a role
 * @param string $role
 * @return string
 */
function getRoleDashboard($role) {
    $dashboards = [
        'manager' => '/my_site/views/manager/dashboard.php',
        'account' => '/my_site/views/account/dashboard.php',
        'ceo'     => '/my_site/views/ceo/dashboard.php',
        'admin'   => '/my_site/views/admin/dashboard.php'
    ];

    return $dashboards[$role] ?? '/my_site/auth/login.php';
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id'    => $_SESSION['user_id'] ?? null,
        'name'  => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? ''
    ];
}

?>
