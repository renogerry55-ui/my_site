<?php
/**
 * Manager - Edit Draft Submission
 */

require_once __DIR__ . '/../../includes/init.php';
requireRole('manager');

$user = getCurrentUser();
$error = '';
$success = '';

// Get submission ID
$submissionId = intval($_GET['id'] ?? 0);

if ($submissionId <= 0) {
    header('Location: view_history.php');
    exit;
}

// Fetch submission with outlet and expenses
$submission = dbFetchOne("
    SELECT ds.*, o.outlet_name
    FROM daily_submissions ds
    INNER JOIN outlets o ON ds.outlet_id = o.id
    WHERE ds.id = :id AND ds.manager_id = :manager_id
", ['id' => $submissionId, 'manager_id' => $user['id']]);

if (!$submission) {
    $error = 'Submission not found or you do not have permission to edit it.';
} elseif ($submission['status'] !== 'draft') {
    $error = 'Only DRAFT submissions can be edited. This submission has status: ' . strtoupper($submission['status']);
}

// Fetch existing expenses
$expenses = dbFetchAll("
    SELECT e.*, ec.category_name, ec.category_type
    FROM expenses e
    INNER JOIN expense_categories ec ON e.expense_category_id = ec.id
    WHERE e.submission_id = :submission_id
    ORDER BY ec.category_type, e.id
", ['submission_id' => $submissionId]);

// Organize expenses by type
$mpBerhadExpenses = [];
$marketExpenses = [];
foreach ($expenses as $expense) {
    if ($expense['category_type'] === 'mp_berhad') {
        $mpBerhadExpenses[] = $expense;
    } else {
        $marketExpenses[] = $expense;
    }
}

// Fetch manager's outlets
$outlets = dbFetchAll(
    "SELECT * FROM outlets WHERE manager_id = :manager_id AND status = 'active' ORDER BY outlet_name",
    ['manager_id' => $user['id']]
);

// Fetch expense categories
$mpBerhadCategories = dbFetchAll(
    "SELECT * FROM expense_categories WHERE category_type = 'mp_berhad' AND status = 'active' ORDER BY category_name"
);

$marketCategories = dbFetchAll(
    "SELECT * FROM expense_categories WHERE category_type = 'market' AND status = 'active' ORDER BY category_name"
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Submission - Manager</title>
    <link rel="stylesheet" href="../../assets/css/manager_form.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .header {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: #000;
            padding: 20px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
            margin: 0;
        }

        .header-nav {
            display: flex;
            gap: 15px;
        }

        .header-nav a {
            color: #000;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            background: rgba(0,0,0,0.1);
            transition: background 0.3s;
        }

        .header-nav a:hover {
            background: rgba(0,0,0,0.2);
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .edit-notice {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .edit-notice h2 {
            color: #856404;
            margin: 0 0 10px 0;
        }

        .edit-notice p {
            margin: 5px 0;
            color: #856404;
        }

        .btn-back {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }

        .btn-back:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>✏️ Edit Draft Submission</h1>
            <div class="header-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="view_history.php">Back to History</a>
                <a href="/my_site/auth/logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
                <br><br>
                <a href="view_history.php" class="btn-back">← Back to History</a>
            </div>
        <?php else: ?>
            <div class="edit-notice">
                <h2>📝 Editing Draft Submission</h2>
                <p><strong>Submission Code:</strong> <?php echo htmlspecialchars($submission['submission_code']); ?></p>
                <p><strong>Outlet:</strong> <?php echo htmlspecialchars($submission['outlet_name']); ?></p>
                <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($submission['submission_date'])); ?></p>
                <p><strong>Status:</strong> DRAFT (Pending to Send to HQ)</p>
                <p style="margin-top: 15px; font-size: 14px;">
                    ⚠️ You can edit this submission because it's still in DRAFT status. Once you submit it to HQ, it can no longer be edited.
                </p>
            </div>

            <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2 style="color: #ff9800; margin-bottom: 20px;">Edit Submission Details</h2>

                <p style="margin-bottom: 30px; padding: 15px; background: #e7f3ff; border-radius: 8px; border-left: 4px solid #667eea;">
                    <strong>📊 Current Totals:</strong><br>
                    Income: RM <?php echo number_format($submission['total_income'], 2); ?> |
                    Expenses: RM <?php echo number_format($submission['total_expenses'], 2); ?> |
                    Net: RM <?php echo number_format($submission['net_amount'], 2); ?><br><br>
                    MP/BERHAD Expenses: <?php echo count($mpBerhadExpenses); ?> |
                    Market Expenses: <?php echo count($marketExpenses); ?>
                </p>

                <p style="color: #666; font-size: 14px; margin-bottom: 20px;">
                    To edit this submission, please use the full edit form. This feature is currently under development.
                </p>

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <a href="view_history.php" class="btn-back">← Back to History</a>
                    <a href="view_details.php?id=<?php echo $submission['id']; ?>" style="background: #667eea; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none;">
                        View Full Details
                    </a>
                </div>

                <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <h3 style="color: #333; margin-bottom: 15px;">Current Expenses:</h3>

                    <?php if (!empty($mpBerhadExpenses)): ?>
                        <h4 style="color: #28a745; margin: 15px 0 10px 0;">MP/BERHAD Expenses</h4>
                        <ul style="list-style: none; padding: 0;">
                            <?php foreach ($mpBerhadExpenses as $exp): ?>
                                <li style="padding: 10px; margin: 5px 0; background: white; border-radius: 5px; border-left: 3px solid #28a745;">
                                    <strong><?php echo htmlspecialchars($exp['category_name']); ?>:</strong>
                                    RM <?php echo number_format($exp['amount'], 2); ?>
                                    <?php if ($exp['description']): ?>
                                        <br><small style="color: #666;"><?php echo htmlspecialchars($exp['description']); ?></small>
                                    <?php endif; ?>
                                    <?php if ($exp['receipt_file']): ?>
                                        <br><small style="color: #667eea;">📎 Receipt: <?php echo htmlspecialchars($exp['receipt_file']); ?></small>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!empty($marketExpenses)): ?>
                        <h4 style="color: #28a745; margin: 15px 0 10px 0;">Market Expenses</h4>
                        <ul style="list-style: none; padding: 0;">
                            <?php foreach ($marketExpenses as $exp): ?>
                                <li style="padding: 10px; margin: 5px 0; background: white; border-radius: 5px; border-left: 3px solid #28a745;">
                                    <strong><?php echo htmlspecialchars($exp['category_name']); ?>:</strong>
                                    RM <?php echo number_format($exp['amount'], 2); ?>
                                    <?php if ($exp['description']): ?>
                                        <br><small style="color: #666;"><?php echo htmlspecialchars($exp['description']); ?></small>
                                    <?php endif; ?>
                                    <?php if ($exp['receipt_file']): ?>
                                        <br><small style="color: #667eea;">📎 Receipt: <?php echo htmlspecialchars($exp['receipt_file']); ?></small>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 8px; border: 1px solid #ffc107;">
                    <p style="color: #856404; font-weight: 600;">💡 Coming Soon:</p>
                    <p style="color: #856404; margin-top: 10px;">
                        Full inline editing capabilities are being developed. For now, you can:
                    </p>
                    <ul style="color: #856404; margin-left: 20px; margin-top: 10px;">
                        <li>View all submission details</li>
                        <li>Delete this draft and create a new submission</li>
                        <li>Contact your administrator for manual edits</li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
