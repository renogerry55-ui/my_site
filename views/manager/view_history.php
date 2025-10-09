<?php
/**
 * Manager - View Submission History
 */

require_once __DIR__ . '/../../includes/init.php';
requireRole('manager');

$user = getCurrentUser();

// Get success message from redirect
$successMessage = '';
if (isset($_GET['success']) && isset($_GET['code'])) {
    $successMessage = 'Submission created successfully! Code: ' . htmlspecialchars($_GET['code']);
}

// Fetch manager's submissions with outlet info
$submissions = dbFetchAll("
    SELECT
        ds.*,
        o.outlet_name,
        o.outlet_code,
        (SELECT COUNT(*) FROM expenses WHERE submission_id = ds.id) as expense_count
    FROM daily_submissions ds
    INNER JOIN outlets o ON ds.outlet_id = o.id
    WHERE ds.manager_id = :manager_id
    ORDER BY ds.submission_date DESC, ds.created_at DESC
    LIMIT 100
", ['manager_id' => $user['id']]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission History - Manager</title>
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
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
        }

        .header-nav {
            display: flex;
            gap: 15px;
        }

        .header-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            background: rgba(255,255,255,0.2);
            transition: background 0.3s;
        }

        .header-nav a:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .page-header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-new {
            background: #28a745;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-new:hover {
            background: #218838;
        }

        .submissions-grid {
            display: grid;
            gap: 20px;
        }

        .submission-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .submission-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-title {
            flex: 1;
        }

        .card-title h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .card-title .meta {
            color: #666;
            font-size: 13px;
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-draft {
            background: #e2e3e5;
            color: #383d41;
        }

        .status-submitted {
            background: #cfe2ff;
            color: #084298;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-verified {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .status-revised {
            background: #d1ecf1;
            color: #0c5460;
        }

        .card-body {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-group {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
        }

        .info-group label {
            display: block;
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .info-group .value {
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }

        .value.positive {
            color: #28a745;
        }

        .value.negative {
            color: #dc3545;
        }

        .card-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #666;
        }

        .btn-view {
            background: #667eea;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-view:hover {
            background: #5568d3;
        }

        .empty-state {
            background: white;
            border-radius: 10px;
            padding: 60px 30px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 15px;
        }

        .empty-state p {
            color: #999;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Submission History</h1>
            <div class="header-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="submit_expenses.php">New Submission</a>
                <a href="/my_site/auth/logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($successMessage): ?>
            <div class="alert-success"><?php echo $successMessage; ?></div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <h2>Your Submissions</h2>
                <p style="color: #666; margin-top: 5px;">Total: <?php echo count($submissions); ?> submission(s)</p>
            </div>
            <a href="submit_expenses.php" class="btn-new">+ New Submission</a>
        </div>

        <?php if (empty($submissions)): ?>
            <div class="empty-state">
                <h3>No Submissions Yet</h3>
                <p>You haven't submitted any daily reports yet.</p>
                <a href="submit_expenses.php" class="btn-new">Create Your First Submission</a>
            </div>
        <?php else: ?>
            <div class="submissions-grid">
                <?php foreach ($submissions as $sub): ?>
                    <div class="submission-card">
                        <div class="card-header">
                            <div class="card-title">
                                <h3><?php echo htmlspecialchars($sub['outlet_name']); ?></h3>
                                <div class="meta">
                                    <?php echo htmlspecialchars($sub['submission_code']); ?>
                                    • <?php echo date('d M Y', strtotime($sub['submission_date'])); ?>
                                    • <?php echo $sub['expense_count']; ?> expense(s)
                                </div>
                            </div>
                            <span class="status-badge status-<?php echo $sub['status']; ?>">
                                <?php
                                    $statusLabels = [
                                        'draft' => 'Pending to Send to HQ',
                                        'submitted' => 'Sent to HQ',
                                        'pending' => 'Pending Account Approval',
                                        'verified' => 'Verified',
                                        'rejected' => 'Rejected',
                                        'revised' => 'Revised'
                                    ];
                                    echo $statusLabels[$sub['status']] ?? ucfirst($sub['status']);
                                ?>
                            </span>
                        </div>

                        <div class="card-body">
                            <div class="info-group">
                                <label>Total Income</label>
                                <div class="value">RM <?php echo number_format($sub['total_income'], 2); ?></div>
                            </div>

                            <div class="info-group">
                                <label>Total Expenses</label>
                                <div class="value">RM <?php echo number_format($sub['total_expenses'], 2); ?></div>
                            </div>

                            <div class="info-group">
                                <label>Net Amount</label>
                                <div class="value <?php echo $sub['net_amount'] >= 0 ? 'positive' : 'negative'; ?>">
                                    RM <?php echo number_format($sub['net_amount'], 2); ?>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <span>
                                Submitted: <?php echo date('d M Y, g:i A', strtotime($sub['created_at'])); ?>
                            </span>
                            <div style="display: flex; gap: 10px;">
                                <?php if ($sub['status'] === 'draft'): ?>
                                    <a href="edit_submission.php?id=<?php echo $sub['id']; ?>" class="btn-view" style="background: #ffc107; color: #000;">
                                        ✏️ Edit
                                    </a>
                                <?php endif; ?>
                                <a href="view_details.php?id=<?php echo $sub['id']; ?>" class="btn-view">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
