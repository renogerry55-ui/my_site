<?php
/**
 * CSRF Token Generation and Validation
 */

// Prevent direct access
if (!defined('APP_INIT')) {
    die('Direct access not permitted');
}

/**
 * Generate CSRF token
 * @return string
 */
function csrfGenerate() {
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }

    // Clean old tokens
    $_SESSION['csrf_tokens'] = array_filter(
        $_SESSION['csrf_tokens'],
        function($tokenData) {
            return $tokenData['expires'] > time();
        }
    );

    // Generate new token
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_tokens'][$token] = [
        'expires' => time() + CSRF_TOKEN_EXPIRY
    ];

    return $token;
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function csrfVerify($token) {
    if (empty($token) || !isset($_SESSION['csrf_tokens'][$token])) {
        return false;
    }

    $tokenData = $_SESSION['csrf_tokens'][$token];

    // Check if token expired
    if ($tokenData['expires'] < time()) {
        unset($_SESSION['csrf_tokens'][$token]);
        return false;
    }

    // Token is valid - remove it (one-time use)
    unset($_SESSION['csrf_tokens'][$token]);
    return true;
}

/**
 * Get CSRF input field HTML
 * @return string
 */
function csrfField() {
    $token = csrfGenerate();
    return sprintf(
        '<input type="hidden" name="%s" value="%s">',
        htmlspecialchars(CSRF_TOKEN_NAME),
        htmlspecialchars($token)
    );
}

/**
 * Validate CSRF token from POST request
 * @return bool
 */
function csrfValidatePost() {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    return csrfVerify($token);
}

?>
