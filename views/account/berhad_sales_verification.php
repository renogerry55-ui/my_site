<?php
/**
 * Account - Berhad Sales Verification
 * Focused view for reviewing Berhad income stream submissions.
 */

require_once __DIR__ . '/../../includes/init.php';
requireRole('account');

$user = getCurrentUser();

$pendingRows = dbFetchAll(
    "SELECT
        ds.id,
        ds.manager_id,
        ds.outlet_id,
        ds.submission_date,
        ds.batch_code,
        ds.berhad_sales,
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
    WHERE ds.status = 'pending' AND ds.berhad_sales > 0
    ORDER BY u.name ASC, ds.submission_date ASC, o.outlet_name ASC"
);

$managers = [];
$submissionIds = [];
$overall = [
    'total_berhad_sales'      => 0.0,
    'total_mp_berhad_expenses'=> 0.0,
    'submission_count'        => 0,
];
$overallOutletIds = [];

if ($pendingRows) {
    foreach ($pendingRows as $row) {
        $managerId = (int) $row['manager_id'];
        $submissionId = (int) $row['id'];
        $outletId = (int) $row['outlet_id'];

        if (!isset($managers[$managerId])) {
            $managers[$managerId] = [
                'manager_id'       => $managerId,
                'manager_name'     => $row['manager_name'],
                'manager_email'    => $row['manager_email'],
                'total_berhad'     => 0.0,
                'total_mp_berhad'  => 0.0,
                'submissions'      => [],
            ];
        }

        if (!isset($overallOutletIds[$outletId])) {
            $overallOutletIds[$outletId] = true;
        }

        $managers[$managerId]['total_berhad'] += (float) $row['berhad_sales'];
        $overall['total_berhad_sales'] += (float) $row['berhad_sales'];
        $overall['submission_count']++;

        $managers[$managerId]['submissions'][$submissionId] = [
            'id'              => $submissionId,
            'outlet_id'       => $outletId,
            'outlet_name'     => $row['outlet_name'],
            'outlet_code'     => $row['outlet_code'],
            'submission_date' => $row['submission_date'],
            'batch_code'      => $row['batch_code'],
            'berhad_sales'    => (float) $row['berhad_sales'],
            'total_income'    => (float) $row['total_income'],
            'total_expenses'  => (float) $row['total_expenses'],
            'net_amount'      => (float) $row['net_amount'],
            'mp_berhad_expenses' => 0.0,
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
            ec.category_type,
            e.amount
        FROM expenses e
        INNER JOIN expense_categories ec ON e.expense_category_id = ec.id
        WHERE e.submission_id IN ($placeholders)
          AND ec.category_type = 'mp_berhad'
          AND UPPER(ec.category_name) = 'BERHAD'",
        $submissionIds
    );

    if ($expenseRows) {
        foreach ($expenseRows as $expense) {
            $submissionId = (int) $expense['submission_id'];
            $amount = (float) $expense['amount'];

            foreach ($managers as &$manager) {
                if (!isset($manager['submissions'][$submissionId])) {
                    continue;
                }

                $manager['submissions'][$submissionId]['mp_berhad_expenses'] += $amount;
                $manager['total_mp_berhad'] += $amount;
                $overall['total_mp_berhad_expenses'] += $amount;
                break;
            }
            unset($manager);
        }
    }
}

if (!empty($managers)) {
    $managers = array_values($managers);
    usort($managers, function ($a, $b) {
        return strcasecmp($a['manager_name'], $b['manager_name']);
    });
}

$overall['manager_count'] = count($managers);
$overall['outlet_count'] = count($overallOutletIds);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berhad Sales Verification - <?php echo htmlspecialchars(APP_NAME); ?></title>
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

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .summary-card {
            background: #f8fdfb;
            border: 1px solid #d2f5e8;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        .summary-card span {
            display: block;
            font-size: 12px;
            color: #11998e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .summary-card strong {
            display: block;
            font-size: 22px;
            color: #0b6b60;
        }

        .manager-list {
            display: grid;
            gap: 20px;
        }

        .manager-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            padding: 20px 22px;
        }

        .manager-header {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
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

        .manager-summary {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }

        .manager-summary-item {
            background: #f2fcf8;
            border: 1px solid #d2f5e8;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 13px;
            color: #0b6b60;
        }

        .submission-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .submission-table th,
        .submission-table td {
            border: 1px solid #d2f5e8;
            padding: 10px 12px;
            font-size: 13px;
            text-align: left;
        }

        .submission-table th {
            background: #e6f7f1;
            color: #0b6b60;
        }

        .submission-table td strong {
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
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><?php echo htmlspecialchars(APP_NAME); ?> &mdash; Account</h1>
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="verify_submission.php" class="nav-link">Verify Submissions</a>
                <a href="berhad_sales_verification.php" class="nav-link active">Berhad Sales</a>
                <a href="/my_site/auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>Berhad Sales Verification</h2>
            <p>Focus on the Berhad income stream for each pending outlet submission. Review the details below and open an outlet to continue the verification workflow.</p>
        </div>

        <?php if (empty($managers)) : ?>
            <div class="empty-state">
                <h3>No Berhad Sales Pending</h3>
                <p>All submitted Berhad sales have been processed. Check back when managers send new reports.</p>
                <div class="actions" style="justify-content: center; margin-top: 20px;">
                    <a href="verify_submission.php" class="btn btn-secondary">Back to Pending Submissions</a>
                    <a href="dashboard.php" class="btn btn-primary">Return to Dashboard</a>
                </div>
            </div>
        <?php else : ?>
            <div class="summary-grid">
                <div class="summary-card">
                    <span>Pending Berhad Sales</span>
                    <strong>RM <?php echo number_format($overall['total_berhad_sales'], 2); ?></strong>
                </div>
                <div class="summary-card">
                    <span>Berhad Player claimed</span>
                    <strong>RM <?php echo number_format($overall['total_mp_berhad_expenses'], 2); ?></strong>
                </div>
                <div class="summary-card">
                    <span>Managers Involved</span>
                    <strong><?php echo (int) $overall['manager_count']; ?></strong>
                </div>
                <div class="summary-card">
                    <span>Outlets Pending Verification</span>
                    <strong><?php echo (int) $overall['outlet_count']; ?></strong>
                </div>
                <div class="summary-card">
                    <span>Pending Submissions</span>
                    <strong><?php echo (int) $overall['submission_count']; ?></strong>
                </div>
            </div>

            <div class="manager-list">
                <?php foreach ($managers as $manager) : ?>
                    <div class="manager-card">
                        <div class="manager-header">
                            <div>
                                <h3><?php echo htmlspecialchars($manager['manager_name']); ?></h3>
                                <div class="manager-meta">
                                    <div>Email: <?php echo htmlspecialchars($manager['manager_email']); ?></div>
                                    <div>Berhad Submissions: <?php echo count($manager['submissions']); ?></div>
                                </div>
                            </div>
                            <div class="manager-summary">
                                <div class="manager-summary-item">
                                    Total Berhad Sales<br><strong>RM <?php echo number_format($manager['total_berhad'], 2); ?></strong>
                                </div>
                                <div class="manager-summary-item">
                                    Berhad Player claimed<br><strong>RM <?php echo number_format($manager['total_mp_berhad'], 2); ?></strong>
                                </div>
            </div>
                        </div>

                        <table class="submission-table">
                            <thead>
                                <tr>
                                    <th>Outlet</th>
                                    <th>Submission Date</th>
                                    <th>Berhad Sales (RM)</th>
                                    <th>Berhad Player claimed (RM)</th>
                                    <th>Net Amount (RM)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($manager['submissions'] as $submission) : ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($submission['outlet_name']); ?></strong><br>
                                            <span style="font-size:12px; color:#555;">Code: <?php echo htmlspecialchars($submission['outlet_code']); ?></span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars(date('F j, Y', strtotime($submission['submission_date']))); ?>
                                            <?php if (!empty($submission['batch_code'])) : ?>
                                                <br><span style="font-size:12px; color:#555;">Batch: <?php echo htmlspecialchars($submission['batch_code']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>RM <?php echo number_format($submission['berhad_sales'], 2); ?></td>
                                        <td>RM <?php echo number_format($submission['mp_berhad_expenses'], 2); ?></td>
                                        <td>RM <?php echo number_format($submission['net_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="actions">
                <a href="verify_submission.php" class="btn btn-secondary">Back to Pending Submissions</a>
                <a href="dashboard.php" class="btn btn-primary">Return to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
