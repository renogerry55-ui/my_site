<?php
/**
 * Account - Verify Manager Submissions
 * Displays pending submissions grouped by manager with outlet breakdown
 */

require_once __DIR__ . '/../../includes/init.php';
requireRole('account');

$user = getCurrentUser();

// Fetch all pending submissions grouped by manager and outlet
$pendingRows = dbFetchAll(
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
        o.outlet_name,
        o.outlet_code,
        u.name AS manager_name,
        u.email AS manager_email
    FROM daily_submissions ds
    INNER JOIN outlets o ON ds.outlet_id = o.id
    INNER JOIN users u ON ds.manager_id = u.id
    WHERE ds.status = 'pending'
    ORDER BY u.name ASC, ds.submission_date ASC, o.outlet_name ASC"
);

$managers = [];
$submissionIds = [];

if ($pendingRows) {
    foreach ($pendingRows as $row) {
        $managerId = (int) $row['manager_id'];
        $submissionId = (int) $row['id'];

        if (!isset($managers[$managerId])) {
            $managers[$managerId] = [
                'manager_id'     => $managerId,
                'manager_name'   => $row['manager_name'],
                'manager_email'  => $row['manager_email'],
                'outlet_count'   => 0,
                'outlet_codes'   => [],
                'total_income'   => 0,
                'total_expenses' => 0,
                'total_net'      => 0,
                'earliest_date'  => $row['submission_date'],
                'latest_date'    => $row['submission_date'],
                'submissions'    => []
            ];
        }

        if (!in_array($row['outlet_code'], $managers[$managerId]['outlet_codes'], true)) {
            $managers[$managerId]['outlet_codes'][] = $row['outlet_code'];
            $managers[$managerId]['outlet_count']++;
        }

        $managers[$managerId]['total_income'] += (float) $row['total_income'];
        $managers[$managerId]['total_expenses'] += (float) $row['total_expenses'];
        $managers[$managerId]['total_net'] += (float) $row['net_amount'];
        $managers[$managerId]['earliest_date'] = min($managers[$managerId]['earliest_date'], $row['submission_date']);
        $managers[$managerId]['latest_date'] = max($managers[$managerId]['latest_date'], $row['submission_date']);

        $managers[$managerId]['submissions'][$submissionId] = [
            'id'               => $submissionId,
            'batch_code'       => $row['batch_code'],
            'submission_date'  => $row['submission_date'],
            'outlet_id'        => (int) $row['outlet_id'],
            'outlet_name'      => $row['outlet_name'],
            'outlet_code'      => $row['outlet_code'],
            'berhad_sales'     => (float) $row['berhad_sales'],
            'mp_coba_sales'    => (float) $row['mp_coba_sales'],
            'mp_perdana_sales' => (float) $row['mp_perdana_sales'],
            'market_sales'     => (float) $row['market_sales'],
            'total_income'     => (float) $row['total_income'],
            'total_expenses'   => (float) $row['total_expenses'],
            'net_amount'       => (float) $row['net_amount'],
            'expenses'         => []
        ];

        $submissionIds[] = $submissionId;
    }
}

$submissionIds = array_unique($submissionIds);

if (!empty($submissionIds)) {
    $placeholders = implode(',', array_fill(0, count($submissionIds), '?'));
    $expenseRows = dbFetchAll(
        "SELECT
            e.submission_id,
            ec.category_name,
            e.amount,
            e.description
        FROM expenses e
        INNER JOIN expense_categories ec ON e.expense_category_id = ec.id
        WHERE e.submission_id IN ($placeholders)
        ORDER BY ec.category_name ASC, e.id ASC",
        $submissionIds
    );

    if ($expenseRows) {
        foreach ($expenseRows as $expense) {
            $submissionId = (int) $expense['submission_id'];
            foreach ($managers as &$manager) {
                if (isset($manager['submissions'][$submissionId])) {
                    $manager['submissions'][$submissionId]['expenses'][] = [
                        'category'    => $expense['category_name'],
                        'amount'      => (float) $expense['amount'],
                        'description' => $expense['description']
                    ];
                    break;
                }
            }
            unset($manager);
        }
    }
}

// Sort managers by name for consistent display
if (!empty($managers)) {
    $managers = array_values($managers);
    usort($managers, function ($a, $b) {
        return strcasecmp($a['manager_name'], $b['manager_name']);
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Submissions - <?php echo htmlspecialchars(APP_NAME); ?></title>
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

        .nav-link:hover,
        .logout-btn:hover,
        .nav-link.active {
            background-color: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px 40px;
        }

        .page-header {
            background: white;
            border-radius: 10px;
            padding: 25px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .page-header h2 {
            margin-bottom: 10px;
            color: #11998e;
        }

        .manager-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }

        .manager-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .manager-header {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }

        .manager-header h3 {
            color: #11998e;
            margin-bottom: 6px;
        }

        .manager-meta {
            font-size: 13px;
            color: #666;
            line-height: 1.4;
        }

        .summary-metrics {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 150px;
        }

        .summary-item {
            background: #f8fdfb;
            border: 1px solid #d2f5e8;
            border-radius: 8px;
            padding: 10px 12px;
        }

        .summary-item span {
            display: block;
            font-size: 12px;
            color: #11998e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-item strong {
            font-size: 16px;
            color: #0b6b60;
        }

        .toggle-details {
            align-self: flex-start;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            border: none;
            border-radius: 20px;
            padding: 8px 18px;
            font-size: 13px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .toggle-details:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(17,153,142,0.25);
        }

        .manager-details {
            display: none;
            border-top: 1px solid #e6f7f1;
            padding-top: 15px;
            margin-top: 5px;
        }

        .manager-details.active {
            display: block;
        }

        .outlet-card {
            background: #f8fdfb;
            border: 1px solid #d2f5e8;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .outlet-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 12px;
        }

        .outlet-header h4 {
            color: #0b6b60;
        }

        .outlet-header span {
            font-size: 12px;
            color: #555;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .metric {
            background: white;
            border-radius: 8px;
            padding: 10px;
            border: 1px solid #e6f7f1;
        }

        .metric span {
            display: block;
            font-size: 11px;
            color: #11998e;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .metric strong {
            font-size: 15px;
            color: #0b6b60;
        }

        .expenses-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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

        .empty-state {
            background: white;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            color: #666;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }

        .empty-state h3 {
            color: #11998e;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .manager-header {
                flex-direction: column;
            }

            .summary-metrics {
                flex-direction: row;
                flex-wrap: wrap;
            }
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
                <a href="/my_site/auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>Pending Manager Submissions</h2>
            <p>Review all pending submissions grouped by manager. Click "View Details" to see individual outlet sales and expenses.</p>
        </div>

        <?php if (empty($managers)) : ?>
            <div class="empty-state">
                <h3>No Pending Submissions</h3>
                <p>Managers have not submitted any pending reports for review.</p>
            </div>
        <?php else : ?>
            <div class="manager-grid">
                <?php foreach ($managers as $manager) : ?>
                    <div class="manager-card" data-manager-id="<?php echo (int) $manager['manager_id']; ?>">
                        <div class="manager-header">
                            <div>
                                <h3><?php echo htmlspecialchars($manager['manager_name']); ?></h3>
                                <div class="manager-meta">
                                    <div>Email: <?php echo htmlspecialchars($manager['manager_email']); ?></div>
                                    <div>Pending Submissions: <?php echo count($manager['submissions']); ?></div>
                                    <div>Outlets Involved: <?php echo (int) $manager['outlet_count']; ?></div>
                                    <div>Submission Range: <?php echo htmlspecialchars(date('M j, Y', strtotime($manager['earliest_date']))); ?> &ndash; <?php echo htmlspecialchars(date('M j, Y', strtotime($manager['latest_date']))); ?></div>
                                </div>
                            </div>
                            <div class="summary-metrics">
                                <div class="summary-item">
                                    <span>Total Income</span>
                                    <strong>RM <?php echo number_format($manager['total_income'], 2); ?></strong>
                                </div>
                                <div class="summary-item">
                                    <span>Total Expenses</span>
                                    <strong>RM <?php echo number_format($manager['total_expenses'], 2); ?></strong>
                                </div>
                                <div class="summary-item">
                                    <span>Net Amount</span>
                                    <strong>RM <?php echo number_format($manager['total_net'], 2); ?></strong>
                                </div>
                            </div>
                        </div>

                        <button class="toggle-details" type="button">View Details</button>

                        <div class="manager-details">
                            <?php foreach ($manager['submissions'] as $submission) : ?>
                                <div class="outlet-card">
                                    <div class="outlet-header">
                                        <h4><?php echo htmlspecialchars($submission['outlet_name']); ?> (<?php echo htmlspecialchars($submission['outlet_code']); ?>)</h4>
                                        <span><?php echo htmlspecialchars(date('F j, Y', strtotime($submission['submission_date']))); ?><?php if (!empty($submission['batch_code'])) : ?> &middot; Batch: <?php echo htmlspecialchars($submission['batch_code']); ?><?php endif; ?></span>
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

                                    <div class="expenses-section">
                                        <h5 style="color:#0b6b60; margin-bottom:6px;">Expenses</h5>
                                        <?php if (!empty($submission['expenses'])) : ?>
                                            <table class="expenses-table">
                                                <thead>
                                                    <tr>
                                                        <th>Category</th>
                                                        <th>Description</th>
                                                        <th style="width:120px; text-align:right;">Amount (RM)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($submission['expenses'] as $expense) : ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($expense['category']); ?></td>
                                                            <td><?php echo htmlspecialchars($expense['description'] ?? ''); ?></td>
                                                            <td style="text-align:right;"><?php echo number_format($expense['amount'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php else : ?>
                                            <p style="font-size:13px; color:#555;">No expenses recorded for this submission.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.querySelectorAll('.toggle-details').forEach(function(button) {
            button.addEventListener('click', function() {
                var details = this.nextElementSibling;
                if (!details) {
                    return;
                }

                var isActive = details.classList.contains('active');
                details.classList.toggle('active');
                this.textContent = isActive ? 'View Details' : 'Hide Details';
            });
        });
    </script>
</body>
</html>
