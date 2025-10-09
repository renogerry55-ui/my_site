<?php
/**
 * Login Page
 * Handles user authentication for all roles
 */

require_once __DIR__ . '/../includes/init.php';

// If already logged in, redirect to role dashboard
if (isLoggedIn()) {
    $dashboard = getRoleDashboard($_SESSION['user_role']);
    header('Location: ' . $dashboard);
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!csrfValidatePost()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = authLogin($identifier, $password);

        if ($result['success']) {
            // Redirect to role-specific dashboard
            $dashboard = getRoleDashboard($result['user']['role']);
            header('Location: ' . $dashboard);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Check for session timeout
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please log in again.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            padding: 40px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background-color: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            width: 100%;
            padding: 12px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .login-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 13px;
            color: #666;
        }

        .test-credentials {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
            font-size: 12px;
        }

        .test-credentials h4 {
            color: #495057;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .test-credentials ul {
            list-style: none;
        }

        .test-credentials li {
            padding: 5px 0;
            color: #6c757d;
        }

        .test-credentials strong {
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><?php echo htmlspecialchars(APP_NAME); ?></h1>
            <p>Please sign in to continue</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label for="identifier">Email or Username</label>
                <input
                    type="text"
                    id="identifier"
                    name="identifier"
                    required
                    autofocus
                    autocomplete="username"
                    value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn">Sign In</button>
        </form>

        <div class="test-credentials">
            <h4>Test Credentials (Dev Only):</h4>
            <ul>
                <li><strong>Manager:</strong> manager@mysite.com</li>
                <li><strong>Account:</strong> account@mysite.com</li>
                <li><strong>CEO:</strong> ceo@mysite.com</li>
                <li><strong>Admin:</strong> admin@mysite.com</li>
                <li style="margin-top: 8px; border-top: 1px solid #dee2e6; padding-top: 8px;">
                    <strong>Password (all):</strong> Password!234
                </li>
            </ul>
        </div>

        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(APP_NAME); ?>
        </div>
    </div>
</body>
</html>
