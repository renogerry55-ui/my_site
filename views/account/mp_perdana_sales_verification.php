<?php
/**
 * Account - MP PERDANA Sales Verification
 * Focused view for reviewing MP PERDANA income stream submissions.
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
        ds.mp_perdana_sales,
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
    WHERE ds.status = 'pending' AND ds.mp_perdana_sales > 0
    ORDER BY u.name ASC, ds.submission_date ASC, o.outlet_name ASC"
);

$managers = [];
$submissionIds = [];
$overall = [
    'total_mp_perdana_sales'      => 0.0,
    'total_mp_perdana_expenses'=> 0.0,
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
                'total_mp_perdana'     => 0.0,
                'total_mp_perdana_expenses'  => 0.0,
                'submissions'      => [],
            ];
        }

        if (!isset($overallOutletIds[$outletId])) {
            $overallOutletIds[$outletId] = true;
        }

        $managers[$managerId]['total_mp_perdana'] += (float) $row['mp_perdana_sales'];
        $overall['total_mp_perdana_sales'] += (float) $row['mp_perdana_sales'];
        $overall['submission_count']++;

        $managers[$managerId]['submissions'][$submissionId] = [
            'id'              => $submissionId,
            'outlet_id'       => $outletId,
            'outlet_name'     => $row['outlet_name'],
            'outlet_code'     => $row['outlet_code'],
            'submission_date' => $row['submission_date'],
            'batch_code'      => $row['batch_code'],
            'mp_perdana_sales'    => (float) $row['mp_perdana_sales'],
            'total_income'    => (float) $row['total_income'],
            'total_expenses'  => (float) $row['total_expenses'],
            'net_amount'      => (float) $row['net_amount'],
            'mp_perdana_expenses' => 0.0,
            'mp_perdana_net_amount' => (float) $row['mp_perdana_sales'], // Will be recalculated after expenses loaded
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
          AND UPPER(ec.category_name) = 'MP PERDANA'",
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

                $manager['submissions'][$submissionId]['mp_perdana_expenses'] += $amount;
                $manager['total_mp_perdana_expenses'] += $amount;
                $overall['total_mp_perdana_expenses'] += $amount;

                // Calculate MP PERDANA-focused net amount: MP PERDANA Sales - MP PERDANA Expenses
                $manager['submissions'][$submissionId]['mp_perdana_net_amount'] =
                    $manager['submissions'][$submissionId]['mp_perdana_sales'] -
                    $manager['submissions'][$submissionId]['mp_perdana_expenses'];

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
    <title>MP PERDANA Sales Verification - <?php echo htmlspecialchars(APP_NAME); ?></title>
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
            background: linear-gradient(135deg, #ea580c 0%, #f59e0b 100%);
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
            color: #ea580c;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .summary-card {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        .summary-card span {
            display: block;
            font-size: 12px;
            color: #ea580c;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .summary-card strong {
            display: block;
            font-size: 22px;
            color: #c2410c;
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
            color: #ea580c;
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
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 13px;
            color: #c2410c;
        }

        .submission-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .submission-table th,
        .submission-table td {
            border: 1px solid #fed7aa;
            padding: 10px 12px;
            font-size: 13px;
            text-align: left;
        }

        .submission-table th {
            background: #ffedd5;
            color: #c2410c;
        }

        .submission-table td strong {
            color: #c2410c;
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
            box-shadow: 0 4px 10px rgba(234,88,12,0.25);
        }

        .btn-secondary {
            background: #ffffff;
            border: 1px solid #ea580c;
            color: #ea580c;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ea580c 0%, #f59e0b 100%);
            color: white;
        }

        .submission-table-wrapper {
            margin-top: 20px;
        }

        .submission-table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .submission-table-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
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
            color: #ea580c;
            margin-bottom: 10px;
        }

        .upload-section {
            background: white;
            border-radius: 10px;
            padding: 20px 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .upload-section h3 {
            color: #ea580c;
            margin-bottom: 12px;
            font-size: 18px;
        }

        .upload-section p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .upload-controls {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .upload-controls textarea {
            flex: 1;
            min-width: 300px;
            min-height: 120px;
            padding: 12px;
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            resize: vertical;
        }

        .upload-controls textarea:focus {
            outline: none;
            border-color: #ea580c;
            border-style: solid;
        }

        .upload-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .comparison-status {
            margin-top: 12px;
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 14px;
            display: none;
        }

        .comparison-status.show {
            display: block;
        }

        .comparison-status.loading {
            background: #ecfdf5;
            border: 1px solid #10b981;
            color: #047857;
        }

        .comparison-status.success {
            background: #dcfce7;
            border: 1px solid #16a34a;
            color: #166534;
        }

        .comparison-status.error {
            background: #fee2e2;
            border: 1px solid #dc2626;
            color: #b91c1c;
        }

        .comparison-row {
            background: #fff7ed;
            border-left: 4px solid #fed7aa;
            padding: 12px 15px;
            margin-top: 10px;
            border-radius: 4px;
            font-size: 13px;
        }

        .comparison-row.match {
            border-left-color: #10b981;
            background: #ecfdf5;
        }

        .comparison-row.mismatch {
            border-left-color: #ef4444;
            background: #fef2f2;
        }

        .comparison-row.missing {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }

        .comparison-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 8px;
        }

        .comparison-badge.match {
            background: #10b981;
            color: white;
        }

        .comparison-badge.mismatch {
            background: #ef4444;
            color: white;
        }

        .comparison-badge.missing {
            background: #f59e0b;
            color: white;
        }

        .comparison-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 8px;
        }

        .comparison-detail-item {
            font-size: 12px;
            color: #555;
        }

        .comparison-detail-item strong {
            color: #c2410c;
        }

        @media (max-width: 768px) {
            .manager-header {
                flex-direction: column;
            }

            .upload-controls {
                flex-direction: column;
            }

            .upload-controls textarea {
                min-width: 100%;
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
                <a href="berhad_sales_verification.php" class="nav-link">Berhad Sales</a>
                <a href="mp_perdana_sales_verification.php" class="nav-link active">MP PERDANA Sales</a>
                <a href="/my_site/auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>MP PERDANA Sales Verification</h2>
            <p>Upload external sales data once for each manager, then review all outlets with automatic comparison results.</p>
        </div>

        <?php if (empty($managers)) : ?>
            <div class="empty-state">
                <h3>No MP PERDANA Sales Pending</h3>
                <p>All submitted MP PERDANA sales have been processed. Check back when managers send new reports.</p>
                <div class="actions" style="justify-content: center; margin-top: 20px;">
                    <a href="verify_submission.php" class="btn btn-secondary">Back to Pending Submissions</a>
                    <a href="dashboard.php" class="btn btn-primary">Return to Dashboard</a>
                </div>
            </div>
        <?php else : ?>
            <div class="summary-grid">
                <div class="summary-card">
                    <span>Pending MP PERDANA Sales</span>
                    <strong>RM <?php echo number_format($overall['total_mp_perdana_sales'], 2); ?></strong>
                </div>
                <div class="summary-card">
                    <span>MP PERDANA Player claimed</span>
                    <strong>RM <?php echo number_format($overall['total_mp_perdana_expenses'], 2); ?></strong>
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
                                    <div>MP PERDANA Submissions: <?php echo count($manager['submissions']); ?></div>
                                </div>
                            </div>
                            <div class="manager-summary">
                                <div class="manager-summary-item">
                                    Total MP PERDANA Sales<br><strong>RM <?php echo number_format($manager['total_mp_perdana'], 2); ?></strong>
                                </div>
                                <div class="manager-summary-item">
                                    MP PERDANA Player claimed<br><strong>RM <?php echo number_format($manager['total_mp_perdana_expenses'], 2); ?></strong>
                                </div>
            </div>
                        </div>

                        <div class="submission-table-wrapper">
                            <div class="submission-table-header">
                                <div class="submission-table-title">Submission Details</div>
                            </div>
                            <!-- Upload External Sales Data -->
                            <div class="upload-section" data-manager-id="<?php echo (int) $manager['manager_id']; ?>">
                                <h3>📤 Upload External Sales Data</h3>
                                <p>Paste the raw export data from the external sales portal. The system will automatically parse and compare against all outlets for this manager.</p>

                                <!-- Template Table -->
                                <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                                    <h4 style="color: #c2410c; margin-bottom: 10px; font-size: 15px;">External Sales Template</h4>
                                    <p style="font-size: 13px; color: #555; margin-bottom: 12px;">Use this format when pasting your data. The table below will auto-populate as you paste.</p>
                                    <div style="overflow-x: auto;">
                                        <table class="external-sales-template-table" style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden;">
                                            <thead>
                                                <tr>
                                                    <th style="padding: 10px; background: #fed7aa; color: #c2410c; font-size: 12px; font-weight: 600; border: 1px solid #fdba74;">Agent</th>
                                                    <th style="padding: 10px; background: #fed7aa; color: #c2410c; font-size: 12px; font-weight: 600; border: 1px solid #fdba74;">Outlet</th>
                                                    <th style="padding: 10px; background: #fed7aa; color: #c2410c; font-size: 12px; font-weight: 600; border: 1px solid #fdba74;">Level</th>
                                                    <th style="padding: 10px; background: #fed7aa; color: #c2410c; font-size: 12px; font-weight: 600; border: 1px solid #fdba74;">Deposit Count</th>
                                                    <th style="padding: 10px; background: #fed7aa; color: #c2410c; font-size: 12px; font-weight: 600; border: 1px solid #fdba74;">Total Deposit</th>
                                                    <th style="padding: 10px; background: #fed7aa; color: #c2410c; font-size: 12px; font-weight: 600; border: 1px solid #fdba74;">Withdraw Count</th>
                                                    <th style="padding: 10px; background: #fed7aa; color: #c2410c; font-size: 12px; font-weight: 600; border: 1px solid #fdba74;">Total Withdraw</th>
                                                    <th style="padding: 10px; background: #fed7aa; color: #c2410c; font-size: 12px; font-weight: 600; border: 1px solid #fdba74;">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody class="template-tbody">
                                                <tr>
                                                    <td colspan="8" style="padding: 20px; text-align: center; color: #666; font-size: 13px; border: 1px solid #e5e7eb;">
                                                        Paste your external sales data below to see it populate here...
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="upload-controls">
                                    <textarea
                                        class="external-data-input"
                                        placeholder="Paste tab-separated or CSV data here...&#10;Example:&#10;Agent123    John Doe    Level1    5    15000.00    2    5000.00    10000.00"
                                        data-manager-id="<?php echo (int) $manager['manager_id']; ?>"></textarea>
                                    <div class="upload-buttons">
                                        <button type="button" class="btn btn-primary compare-btn">📊 Compare Data</button>
                                        <!-- Save & Verify button temporarily disabled - will be reactivated later -->
                                        <!-- <button type="button" class="btn btn-secondary save-btn" disabled>💾 Save & Verify</button> -->
                                    </div>
                                </div>
                                <div class="comparison-status"></div>
                            </div>

                            <!-- Submission Details Table -->
                            <table class="submission-table">
                                <thead>
                                    <tr>
                                        <th>Outlet</th>
                                        <th>Submission Date</th>
                                        <th>MP PERDANA Sales (RM)</th>
                                        <th>MP PERDANA Player claimed (RM)</th>
                                        <th>Net Amount (RM)</th>
                                        <th style="width: 120px;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($manager['submissions'] as $submission) : ?>
                                        <tr data-submission-id="<?php echo (int) $submission['id']; ?>"
                                            data-outlet-code="<?php echo htmlspecialchars($submission['outlet_code']); ?>"
                                            data-mp-perdana-sales="<?php echo $submission['mp_perdana_sales']; ?>"
                                            data-mp-perdana-expenses="<?php echo $submission['mp_perdana_expenses']; ?>">
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
                                            <td>RM <?php echo number_format($submission['mp_perdana_sales'], 2); ?></td>
                                            <td>RM <?php echo number_format($submission['mp_perdana_expenses'], 2); ?></td>
                                            <td>RM <?php echo number_format($submission['mp_perdana_net_amount'], 2); ?></td>
                                            <td class="status-cell">
                                                <span class="comparison-badge">Pending</span>
                                            </td>
                                        </tr>
                                        <tr class="comparison-row-container" style="display: none;">
                                            <td colspan="6">
                                                <div class="comparison-row">
                                                    <div class="comparison-details"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="actions">
                <a href="verify_submission.php" class="btn btn-secondary">Back to Pending Submissions</a>
                <a href="dashboard.php" class="btn btn-primary">Return to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = '<?php echo csrfGenerate(); ?>';
            const csrfName = '<?php echo CSRF_TOKEN_NAME; ?>';

            // Handle all manager cards
            document.querySelectorAll('.manager-card').forEach(function(managerCard) {
                const managerId = managerCard.querySelector('[data-manager-id]').dataset.managerId;
                const textarea = managerCard.querySelector('.external-data-input');
                const compareBtn = managerCard.querySelector('.compare-btn');
                const saveBtn = managerCard.querySelector('.save-btn');
                const statusDiv = managerCard.querySelector('.comparison-status');
                const submissionRows = managerCard.querySelectorAll('tr[data-submission-id]');

                let comparisonResults = null;
                let parsedData = null;
                const templateTbody = managerCard.querySelector('.template-tbody');

                // Parse CSV/TSV data
                function parseData(rawData) {
                    const lines = rawData.trim().split('\n').filter(line => line.trim());
                    if (lines.length === 0) return [];

                    // Detect delimiter
                    const firstLine = lines[0];
                    let delimiter = '\t';
                    if (firstLine.includes('\t')) delimiter = '\t';
                    else if (firstLine.includes(',')) delimiter = ',';
                    else if (firstLine.includes(';')) delimiter = ';';

                    return lines.map(line => {
                        return line.split(delimiter).map(cell => cell.trim());
                    });
                }

                // Update template table with parsed data
                function updateTemplateTable(data) {
                    if (!data || data.length === 0) {
                        templateTbody.innerHTML = `
                            <tr>
                                <td colspan="8" style="padding: 20px; text-align: center; color: #666; font-size: 13px; border: 1px solid #e5e7eb;">
                                    Paste your external sales data below to see it populate here...
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    let rowsHtml = '';
                    const maxRows = Math.min(data.length, 10); // Show max 10 rows in preview

                    for (let i = 0; i < maxRows; i++) {
                        const row = data[i];
                        if (row.length < 8) continue; // Skip incomplete rows

                        rowsHtml += `
                            <tr>
                                <td style="padding: 8px; font-size: 12px; border: 1px solid #e5e7eb;">${row[0] || ''}</td>
                                <td style="padding: 8px; font-size: 12px; border: 1px solid #e5e7eb;">${row[1] || ''}</td>
                                <td style="padding: 8px; font-size: 12px; border: 1px solid #e5e7eb;">${row[2] || ''}</td>
                                <td style="padding: 8px; font-size: 12px; border: 1px solid #e5e7eb;">${row[3] || ''}</td>
                                <td style="padding: 8px; font-size: 12px; border: 1px solid #e5e7eb;">${row[4] || ''}</td>
                                <td style="padding: 8px; font-size: 12px; border: 1px solid #e5e7eb;">${row[5] || ''}</td>
                                <td style="padding: 8px; font-size: 12px; border: 1px solid #e5e7eb;">${row[6] || ''}</td>
                                <td style="padding: 8px; font-size: 12px; border: 1px solid #e5e7eb;">${row[7] || ''}</td>
                            </tr>
                        `;
                    }

                    if (data.length > maxRows) {
                        rowsHtml += `
                            <tr>
                                <td colspan="8" style="padding: 8px; text-align: center; color: #666; font-size: 12px; border: 1px solid #e5e7eb; font-style: italic;">
                                    ... and ${data.length - maxRows} more row(s)
                                </td>
                            </tr>
                        `;
                    }

                    templateTbody.innerHTML = rowsHtml;
                }

                // Listen to textarea input for real-time preview
                textarea.addEventListener('input', function() {
                    const rawData = textarea.value.trim();
                    if (!rawData) {
                        updateTemplateTable(null);
                        return;
                    }

                    try {
                        const parsed = parseData(rawData);
                        updateTemplateTable(parsed);
                    } catch (error) {
                        console.error('Parse error:', error);
                    }
                });

                // Parse amount from string
                function parseAmount(value) {
                    if (!value) return 0;
                    const cleaned = String(value).replace(/[^0-9.-]/g, '');
                    return parseFloat(cleaned) || 0;
                }

                // Format currency
                function formatCurrency(amount) {
                    return 'RM ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                }

                // Compare data
                compareBtn.addEventListener('click', function() {
                    const rawData = textarea.value.trim();
                    if (!rawData) {
                        showStatus('error', 'Please paste external sales data first.');
                        return;
                    }

                    showStatus('loading', 'Parsing and comparing data...');
                    compareBtn.disabled = true;

                    // Simulate processing delay for UX
                    setTimeout(function() {
                        parsedData = parseData(rawData);
                        if (parsedData.length === 0) {
                            showStatus('error', 'No valid data found. Please check the format.');
                            compareBtn.disabled = false;
                            return;
                        }

                        // Build mapping of outlet name to external data
                        const externalDataMap = {};
                        parsedData.forEach(row => {
                            if (row.length >= 8) {
                                const outletName = (row[1] || '').trim().toLowerCase();
                                const totalDeposit = parseAmount(row[4]);
                                const totalWithdraw = parseAmount(row[6]);

                                if (outletName) {
                                    externalDataMap[outletName] = {
                                        agent: row[0],
                                        outletName: row[1],
                                        level: row[2],
                                        depositCount: row[3],
                                        totalDeposit: totalDeposit,
                                        withdrawCount: row[5],
                                        totalWithdraw: totalWithdraw,
                                        total: row[7],
                                        rawRow: row
                                    };
                                }
                            }
                        });

                        // Compare against each outlet by matching outlet name
                        comparisonResults = [];
                        let allMatch = true;
                        let matchCount = 0;
                        let notFoundCount = 0;

                        submissionRows.forEach(function(row) {
                            const submissionId = row.dataset.submissionId;
                            const outletCode = row.dataset.outletCode;
                            const outletNameElement = row.querySelector('td:first-child strong');
                            const outletName = outletNameElement ? outletNameElement.textContent.trim().toLowerCase() : '';
                            const submittedSales = parseFloat(row.dataset.mpPerdanaSales);
                            const submittedExpenses = parseFloat(row.dataset.mpPerdanaExpenses);

                            // Find matching external data by outlet name
                            const externalData = externalDataMap[outletName];

                            if (!externalData) {
                                // Outlet not found in external data
                                notFoundCount++;
                                allMatch = false;

                                comparisonResults.push({
                                    submissionId: submissionId,
                                    outletCode: outletCode,
                                    outletName: outletName,
                                    submittedSales: submittedSales,
                                    externalSales: 0,
                                    salesDifference: -submittedSales,
                                    submittedExpenses: submittedExpenses,
                                    externalExpenses: 0,
                                    expensesDifference: -submittedExpenses,
                                    matches: false,
                                    notFound: true
                                });

                                updateRowComparison(row, {
                                    submittedSales: submittedSales,
                                    externalSales: 0,
                                    salesDifference: -submittedSales,
                                    submittedExpenses: submittedExpenses,
                                    externalExpenses: 0,
                                    expensesDifference: -submittedExpenses,
                                    matches: false,
                                    notFound: true
                                });
                            } else {
                                // Compare both sales and expenses
                                const externalSales = externalData.totalDeposit;
                                const externalExpenses = externalData.totalWithdraw;
                                const salesDifference = externalSales - submittedSales;
                                const expensesDifference = externalExpenses - submittedExpenses;
                                const salesMatch = Math.abs(salesDifference) <= 0.01;
                                const expensesMatch = Math.abs(expensesDifference) <= 0.01;
                                const matches = salesMatch && expensesMatch;

                                if (matches) matchCount++;
                                else allMatch = false;

                                comparisonResults.push({
                                    submissionId: submissionId,
                                    outletCode: outletCode,
                                    outletName: outletName,
                                    submittedSales: submittedSales,
                                    externalSales: externalSales,
                                    salesDifference: salesDifference,
                                    salesMatch: salesMatch,
                                    submittedExpenses: submittedExpenses,
                                    externalExpenses: externalExpenses,
                                    expensesDifference: expensesDifference,
                                    expensesMatch: expensesMatch,
                                    matches: matches,
                                    notFound: false
                                });

                                updateRowComparison(row, {
                                    submittedSales: submittedSales,
                                    externalSales: externalSales,
                                    salesDifference: salesDifference,
                                    salesMatch: salesMatch,
                                    submittedExpenses: submittedExpenses,
                                    externalExpenses: externalExpenses,
                                    expensesDifference: expensesDifference,
                                    expensesMatch: expensesMatch,
                                    matches: matches,
                                    notFound: false
                                });
                            }
                        });

                        let message = '';
                        if (allMatch) {
                            message = `✅ All ${matchCount} outlet(s) match! You can now save and verify.`;
                        } else {
                            const parts = [];
                            if (matchCount > 0) parts.push(`${matchCount} matched`);
                            if (notFoundCount > 0) parts.push(`${notFoundCount} not found in external data`);
                            const mismatchCount = submissionRows.length - matchCount - notFoundCount;
                            if (mismatchCount > 0) parts.push(`${mismatchCount} amount mismatch`);
                            message = `⚠️ ${parts.join(', ')}. Review issues before saving.`;
                        }

                        showStatus(allMatch ? 'success' : 'error', message);
                        // saveBtn.disabled = !allMatch; // Commented out - Save & Verify disabled
                        compareBtn.disabled = false;
                    }, 1500);
                });

                // Update row comparison display
                function updateRowComparison(row, result) {
                    const statusCell = row.querySelector('.status-cell');
                    const badge = statusCell.querySelector('.comparison-badge');
                    const comparisonContainer = row.nextElementSibling;
                    const detailsDiv = comparisonContainer.querySelector('.comparison-details');

                    // Update badge
                    if (result.notFound) {
                        badge.className = 'comparison-badge missing';
                        badge.textContent = '⚠ Not Found';
                    } else {
                        badge.className = 'comparison-badge ' + (result.matches ? 'match' : 'mismatch');
                        badge.textContent = result.matches ? '✓ Match' : '✗ Mismatch';
                    }

                    // Update comparison row
                    comparisonContainer.querySelector('.comparison-row').className =
                        'comparison-row ' + (result.notFound ? 'missing' : result.matches ? 'match' : 'mismatch');
                    comparisonContainer.style.display = 'table-row';

                    if (result.notFound) {
                        detailsDiv.innerHTML = `
                            <div class="comparison-detail-item" style="grid-column: 1 / -1;">
                                <strong>⚠ Outlet not found in external data</strong><br>
                                <span style="color: #666;">Please ensure the outlet name matches exactly in the external data (column 2).</span>
                            </div>
                        `;
                    } else {
                        // Show both sales and expenses comparison
                        const salesIcon = result.salesMatch ? '✓' : '✗';
                        const expensesIcon = result.expensesMatch ? '✓' : '✗';

                        detailsDiv.innerHTML = `
                            <div class="comparison-detail-item" style="grid-column: 1 / -1; border-bottom: 1px solid #fed7aa; padding-bottom: 8px; margin-bottom: 8px;">
                                <strong style="color: #c2410c;">💰 Sales Comparison ${salesIcon}</strong>
                            </div>
                            <div class="comparison-detail-item">
                                <strong>Sales Submitted:</strong> ${formatCurrency(result.submittedSales)}
                            </div>
                            <div class="comparison-detail-item">
                                <strong>Sales External:</strong> ${formatCurrency(result.externalSales)}
                            </div>
                            <div class="comparison-detail-item">
                                <strong>Sales Difference:</strong> ${formatCurrency(Math.abs(result.salesDifference))}
                                ${result.salesDifference > 0 ? '(External higher)' : result.salesDifference < 0 ? '(External lower)' : ''}
                            </div>
                            <div class="comparison-detail-item" style="grid-column: 1 / -1; border-bottom: 1px solid #fed7aa; padding-bottom: 8px; margin-bottom: 8px; margin-top: 8px;">
                                <strong style="color: #c2410c;">💸 Expenses Comparison ${expensesIcon}</strong>
                            </div>
                            <div class="comparison-detail-item">
                                <strong>Expenses Submitted:</strong> ${formatCurrency(result.submittedExpenses)}
                            </div>
                            <div class="comparison-detail-item">
                                <strong>Expenses External:</strong> ${formatCurrency(result.externalExpenses)}
                            </div>
                            <div class="comparison-detail-item">
                                <strong>Expenses Difference:</strong> ${formatCurrency(Math.abs(result.expensesDifference))}
                                ${result.expensesDifference > 0 ? '(External higher)' : result.expensesDifference < 0 ? '(External lower)' : ''}
                            </div>
                        `;
                    }
                }

                // Save data functionality - temporarily disabled
                /* COMMENTED OUT - Save & Verify button disabled for now
                saveBtn.addEventListener('click', function() {
                    if (!comparisonResults || !parsedData) {
                        showStatus('error', 'Please compare data first.');
                        return;
                    }

                    showStatus('loading', 'Saving external sales data...');
                    saveBtn.disabled = true;
                    compareBtn.disabled = true;

                    const formData = new URLSearchParams();
                    formData.append('manager_id', managerId);
                    formData.append('structured_data', JSON.stringify(parsedData));
                    formData.append(csrfName, csrfToken);

                    fetch('/my_site/includes/account/save_mp_perdana_external_sales_batch.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: formData.toString()
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showStatus('success', data.message + ' Page will reload in 2 seconds...');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showStatus('error', data.message || 'Failed to save data.');
                            saveBtn.disabled = false;
                            compareBtn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showStatus('error', 'An error occurred while saving.');
                        saveBtn.disabled = false;
                        compareBtn.disabled = false;
                    });
                });
                */

                // Show status message
                function showStatus(type, message) {
                    statusDiv.className = 'comparison-status show ' + type;
                    statusDiv.textContent = message;
                }
            });
        });
    </script>
</body>
</html>
