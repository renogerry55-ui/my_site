<?php
/**
 * Account - Outlet Verification Page
 * Displays detailed information for a single submission/outlet to prepare verification.
 */

require_once __DIR__ . '/../../includes/init.php';
requireRole('account');

$submissionId = filter_input(INPUT_GET, 'submission_id', FILTER_VALIDATE_INT);

if (!$submissionId) {
    http_response_code(400);
    echo 'Invalid submission selected.';
    exit;
}

$submission = dbFetchOne(
    "SELECT
        ds.id,
        ds.manager_id,
        ds.outlet_id,
        ds.submission_date,
        ds.batch_code,
        ds.berhad_sales,
        ds.mp_coba_sales,
        ds.mp_perdana_sales,
        ds.market_sales,
        ds.total_income,
        ds.total_expenses,
        ds.net_amount,
        ds.status,
        o.outlet_name,
        o.outlet_code,
        u.name  AS manager_name,
        u.email AS manager_email
    FROM daily_submissions ds
    INNER JOIN outlets o ON ds.outlet_id = o.id
    INNER JOIN users u ON ds.manager_id = u.id
    WHERE ds.id = ?",
    [$submissionId]
);

if (!$submission) {
    http_response_code(404);
    echo 'Submission not found.';
    exit;
}

$expenseRows = dbFetchAll(
    "SELECT
        ec.category_name,
        ec.category_type,
        e.amount,
        e.description
    FROM expenses e
    INNER JOIN expense_categories ec ON e.expense_category_id = ec.id
    WHERE e.submission_id = ?
    ORDER BY ec.category_name ASC, e.id ASC",
    [$submissionId]
);

$expenses = [
    'mp_berhad' => [],
    'market'    => [],
    'other'     => []
];

$expenseTotals = [
    'mp_berhad' => 0,
    'market'    => 0,
    'other'     => 0
];

if ($expenseRows) {
    foreach ($expenseRows as $row) {
        $type = $row['category_type'] ?? 'other';
        if (!isset($expenses[$type])) {
            $expenses[$type] = [];
            $expenseTotals[$type] = 0;
        }

        $expenses[$type][] = $row;
        $expenseTotals[$type] += (float) $row['amount'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outlet Verification - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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
            gap: 20px;
        }

        .header h1 {
            font-size: 24px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-link,
        .logout-btn {
            color: white;
            padding: 8px 18px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            background-color: rgba(255,255,255,0.18);
            transition: background-color 0.3s, color 0.3s;
        }

        .nav-link.active,
        .nav-link:hover,
        .logout-btn:hover {
            background-color: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px 40px;
        }

        .breadcrumb {
            font-size: 13px;
            color: #0b6b60;
            margin-bottom: 15px;
        }

        .breadcrumb a {
            color: #0b6b60;
            text-decoration: none;
        }

        .breadcrumb span {
            margin: 0 6px;
        }

        .page-title {
            margin-bottom: 25px;
        }

        .page-title h2 {
            color: #0b6b60;
            margin-bottom: 8px;
        }

        .page-title p {
            color: #555;
        }

        .verification-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            padding: 25px;
        }

        .verification-header {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 20px;
        }

        .verification-header h3 {
            color: #0b6b60;
            font-size: 22px;
        }

        .verification-header span {
            color: #666;
            font-size: 14px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            text-transform: uppercase;
            background: #e6f7f1;
            color: #0b6b60;
            font-weight: 600;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 25px;
        }

        .metric {
            background: #f8fdfb;
            border: 1px solid #d2f5e8;
            border-radius: 10px;
            padding: 15px;
        }

        .metric span {
            display: block;
            font-size: 12px;
            color: #11998e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .metric strong {
            font-size: 18px;
            color: #0b6b60;
        }

        .section-title {
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #0b6b60;
            margin-bottom: 10px;
        }

        .detail-grid {
            display: grid;
            gap: 6px;
            margin-bottom: 25px;
            color: #555;
        }

        .detail-grid div strong {
            color: #333;
        }

        .expense-groups {
            display: grid;
            gap: 20px;
        }

        @media (min-width: 768px) {
            .expense-groups {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .expense-group {
            background: #f2fcf8;
            border: 1px solid #d2f5e8;
            border-radius: 10px;
            padding: 15px;
        }

        .expense-group h4 {
            color: #0b6b60;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .expenses-table {
            width: 100%;
            border-collapse: collapse;
        }

        .expenses-table th,
        .expenses-table td {
            padding: 8px 10px;
            border: 1px solid #d2f5e8;
            font-size: 13px;
            text-align: left;
        }

        .expenses-table th {
            background: #e6f7f1;
            color: #0b6b60;
        }

        .expense-total {
            text-align: right;
            font-size: 13px;
            margin-top: 10px;
            font-weight: 600;
            color: #0b6b60;
        }

        .actions {
            margin-top: 30px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(17,153,142,0.25);
        }

        .btn-secondary {
            background: #ffffff;
            border: 1px solid #11998e;
            color: #11998e;
        }

        .btn-primary {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }

        .note {
            background: #fff7e6;
            border: 1px solid #ffe0a3;
            border-radius: 8px;
            padding: 12px 15px;
            margin-top: 20px;
            color: #9a6b00;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><?php echo htmlspecialchars(APP_NAME); ?> &mdash; Account</h1>
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="verify_submission.php" class="nav-link active">Verify Submissions</a>
                <a href="berhad_sales_verification.php" class="nav-link">Berhad Sales</a>
                <a href="/my_site/auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="breadcrumb">
            <a href="verify_submission.php">&larr; Back to Pending Submissions</a>
            <span>/</span>
            <span>Outlet Verification</span>
        </div>

        <div class="page-title">
            <h2>Outlet Verification</h2>
            <p>Review the detailed submission information before completing the verification workflow.</p>
        </div>

        <div class="verification-card">
            <div class="verification-header">
                <h3><?php echo htmlspecialchars($submission['outlet_name']); ?> (<?php echo htmlspecialchars($submission['outlet_code']); ?>)</h3>
                <span>Submission Date: <?php echo htmlspecialchars(date('F j, Y', strtotime($submission['submission_date']))); ?><?php if (!empty($submission['batch_code'])) : ?> &middot; Batch: <?php echo htmlspecialchars($submission['batch_code']); ?><?php endif; ?></span>
                <span>Manager: <?php echo htmlspecialchars($submission['manager_name']); ?> (<?php echo htmlspecialchars($submission['manager_email']); ?>)</span>
                <span class="status-badge">Status: <?php echo htmlspecialchars(ucfirst($submission['status'])); ?></span>
            </div>

            <div class="metrics-grid">
                <div class="metric">
                    <span>Berhad Sales</span>
                    <strong>RM <?php echo number_format($submission['berhad_sales'], 2); ?></strong>
                </div>
                <div class="metric">
                    <span>MP Coba Sales</span>
                    <strong>RM <?php echo number_format($submission['mp_coba_sales'], 2); ?></strong>
                </div>
                <div class="metric">
                    <span>MP Perdana Sales</span>
                    <strong>RM <?php echo number_format($submission['mp_perdana_sales'], 2); ?></strong>
                </div>
                <div class="metric">
                    <span>Market Sales</span>
                    <strong>RM <?php echo number_format($submission['market_sales'], 2); ?></strong>
                </div>
                <div class="metric">
                    <span>Total Income</span>
                    <strong>RM <?php echo number_format($submission['total_income'], 2); ?></strong>
                </div>
                <div class="metric">
                    <span>Total Expenses</span>
                    <strong>RM <?php echo number_format($submission['total_expenses'], 2); ?></strong>
                </div>
                <div class="metric">
                    <span>Net Amount</span>
                    <strong>RM <?php echo number_format($submission['net_amount'], 2); ?></strong>
                </div>
            </div>

            <div class="detail-section">
                <div class="section-title">Submission Details</div>
                <div class="detail-grid">
                    <div><strong>Submission ID:</strong> <?php echo (int) $submission['id']; ?></div>
                    <div><strong>Outlet ID:</strong> <?php echo (int) $submission['outlet_id']; ?></div>
                    <div><strong>Manager ID:</strong> <?php echo (int) $submission['manager_id']; ?></div>
                </div>
            </div>

            <div class="section-title">Expenses Breakdown</div>
            <?php
                $hasExpenses = false;
                foreach ($expenses as $expenseList) {
                    if (!empty($expenseList)) {
                        $hasExpenses = true;
                        break;
                    }
                }
            ?>
            <?php if ($hasExpenses) : ?>
                <div class="expense-groups">
                    <?php foreach ($expenses as $type => $expenseList) : ?>
                        <?php if (empty($expenseList)) { continue; } ?>
                        <div class="expense-group">
                            <?php
                                $labels = [
                                    'mp_berhad' => 'MP/Berhad Expenses',
                                    'market'    => 'Market Expenses',
                                    'other'     => 'Other Expenses'
                                ];
                                $label = $labels[$type] ?? 'Expenses';
                            ?>
                            <h4><?php echo htmlspecialchars($label); ?></h4>
                            <table class="expenses-table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th style="width:130px; text-align:right;">Amount (RM)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expenseList as $expense) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                                            <td><?php echo htmlspecialchars($expense['description'] ?? ''); ?></td>
                                            <td style="text-align:right;">RM <?php echo number_format((float) $expense['amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div class="expense-total">Total: RM <?php echo number_format($expenseTotals[$type] ?? 0, 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p style="color:#555; font-size:13px;">No expenses were recorded for this submission.</p>
            <?php endif; ?>

            <div class="note">
                Verification actions will be available in the next step. Review the outlet information to prepare for approval or follow-up actions.
            </div>

            <div class="actions">
                <a href="verify_submission.php" class="btn btn-secondary">Back to Pending List</a>
                <a href="#" class="btn btn-primary" aria-disabled="true">Verification Actions Coming Soon</a>
            </div>
        </div>
    </div>
</body>
</html>
