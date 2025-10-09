<?php
/**
 * Manager Dashboard
 */

require_once __DIR__ . '/../../includes/init.php';

// Require authentication and manager role
requireRole('manager');

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
        }

        .logout-btn {
            background-color: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .welcome-card h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .user-info {
            color: #666;
            margin: 15px 0;
        }

        .user-info p {
            margin: 8px 0;
        }

        .role-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .link-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .link-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .link-card h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .link-card p {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><?php echo htmlspecialchars(APP_NAME); ?></h1>
            <a href="/my_site/auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome-card">
            <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
            <span class="role-badge"><?php echo htmlspecialchars($user['role']); ?></span>

            <div class="user-info">
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>User ID:</strong> <?php echo htmlspecialchars($user['id']); ?></p>
                <p><strong>Login Time:</strong> <?php echo date('F j, Y g:i A', $_SESSION['login_time']); ?></p>
            </div>
        </div>

        <h2 style="color: #333; margin-bottom: 20px;">Quick Links</h2>
        <div class="quick-links">
            <a href="submit_expenses.php" class="link-card">
                <h3>Outlet Closing</h3>
                <p>Submit daily closing expenses and reports</p>
            </a>

            <a href="submit_to_hq.php" class="link-card">
                <h3>📤 Submit to HQ</h3>
                <p>Review and submit all outlet closings to HQ/Account</p>
            </a>

            <a href="view_history.php" class="link-card">
                <h3>View History</h3>
                <p>View your submission history and status</p>
            </a>

            <a href="#" class="link-card">
                <h3>Reports</h3>
                <p>Generate and download reports</p>
            </a>
        </div>
    </div>
</body>
</html>
