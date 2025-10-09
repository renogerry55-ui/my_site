<?php
/**
 * Manager - Submit to HQ
 * Review all draft closings and submit to HQ/Account all at once
 */

require_once __DIR__ . '/../../includes/init.php';
requireRole('manager');

$user = getCurrentUser();
$success = '';
$error = '';

// Handle batch submission to HQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_to_hq'])) {
    if (!csrfValidatePost()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        try {
            $pdo = getDB();
            $pdo->beginTransaction();

            // Get all draft submissions for today
            $submissionDate = $_POST['submission_date'] ?? date('Y-m-d');

            $drafts = dbFetchAll(
                "SELECT * FROM daily_submissions
                 WHERE manager_id = :manager_id
                 AND submission_date = :date
                 AND status = 'draft'",
                ['manager_id' => $user['id'], 'date' => $submissionDate]
            );

            if (empty($drafts)) {
                throw new Exception('No draft submissions found to submit.');
            }

            // Generate batch code
            $batchCode = 'BATCH-' . date('Ymd') . '-' . $user['id'] . '-' . strtoupper(substr(md5(uniqid()), 0, 6));

            // Update all drafts to pending status (awaiting account approval)
            $stmt = $pdo->prepare(
                "UPDATE daily_submissions
                 SET status = 'pending',
                     batch_code = :batch_code,
                     submitted_to_hq_at = NOW()
                 WHERE manager_id = :manager_id
                 AND submission_date = :date
                 AND status = 'draft'"
            );

            $stmt->execute([
                'batch_code' => $batchCode,
                'manager_id' => $user['id'],
                'date' => $submissionDate
            ]);

            $affectedRows = $stmt->rowCount();

            $pdo->commit();

            $success = "Successfully submitted {$affectedRows} outlet closing(s) to HQ! All submissions are now pending account approval. Batch Code: {$batchCode}";

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Submission failed: ' . $e->getMessage();
        }
    }
}

// Get today's date or selected date
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Fetch draft submissions for the selected date
$draftSubmissions = dbFetchAll("
    SELECT
        ds.*,
        o.outlet_name,
        o.outlet_code,
        (SELECT COUNT(*) FROM expenses WHERE submission_id = ds.id) as expense_count
    FROM daily_submissions ds
    INNER JOIN outlets o ON ds.outlet_id = o.id
    WHERE ds.manager_id = :manager_id
    AND ds.submission_date = :date
    AND ds.status = 'draft'
    ORDER BY o.outlet_name
", ['manager_id' => $user['id'], 'date' => $selectedDate]);

// Calculate totals
$totalIncome = 0;
$totalExpenses = 0;
$totalNet = 0;

foreach ($draftSubmissions as $sub) {
    $totalIncome += $sub['total_income'];
    $totalExpenses += $sub['total_expenses'];
    $totalNet += $sub['net_amount'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit to HQ - Manager</title>
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

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .page-header h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
        }

        .date-selector {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .date-selector input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .date-selector button {
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }

        .summary-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .summary-card .value {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }

        .summary-card.income {
            border-left-color: #28a745;
        }

        .summary-card.income .value {
            color: #28a745;
        }

        .summary-card.expenses {
            border-left-color: #dc3545;
        }

        .summary-card.expenses .value {
            color: #dc3545;
        }

        .submissions-table {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            padding: 15px 12px;
            border-bottom: 1px solid #dee2e6;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .btn-submit-hq {
            width: 100%;
            padding: 15px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-submit-hq:hover {
            background: #218838;
        }

        .btn-submit-hq:disabled {
            background: #6c757d;
            cursor: not-allowed;
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

        .btn-primary {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
        }

        .amount {
            font-weight: 600;
        }

        .amount.positive {
            color: #28a745;
        }

        .amount.negative {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📤 Submit to HQ</h1>
            <div class="header-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="submit_expenses.php">New Closing</a>
                <a href="view_history.php">History</a>
                <a href="/my_site/auth/logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="page-header">
            <h2>Review & Submit to HQ</h2>
            <p>Review all outlet closings that are pending to be sent to HQ. After submission, all closings will be sent to HQ/Account for approval.</p>

            <div class="date-selector">
                <label>Select Date:</label>
                <form method="GET">
                    <input type="date" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>">
                    <button type="submit">Load Closings</button>
                </form>
            </div>
        </div>

        <?php if (empty($draftSubmissions)): ?>
            <div class="empty-state">
                <h3>No Pending Closings for <?php echo date('d M Y', strtotime($selectedDate)); ?></h3>
                <p>All outlet closings must be submitted individually first before you can send them to HQ. Pending closings will appear here.</p>
                <a href="submit_expenses.php" class="btn-primary">Submit Outlet Closing</a>

                <div style="margin-top: 30px; padding-top: 30px; border-top: 2px solid #f0f0f0;">
                    <p style="color: #999; font-size: 13px;">
                        <strong>Debug Info:</strong><br>
                        Looking for submissions with:<br>
                        - Manager ID: <?php echo $user['id']; ?><br>
                        - Date: <?php echo $selectedDate; ?><br>
                        - Status: 'draft'<br>
                        <?php
                        // Check all submissions for this manager regardless of status
                        $allSubs = dbFetchAll("
                            SELECT submission_date, status, COUNT(*) as count
                            FROM daily_submissions
                            WHERE manager_id = :manager_id
                            GROUP BY submission_date, status
                            ORDER BY submission_date DESC
                        ", ['manager_id' => $user['id']]);

                        if (!empty($allSubs)) {
                            echo "<br>Found submissions:<br>";
                            foreach ($allSubs as $s) {
                                echo "- Date: {$s['submission_date']}, Status: {$s['status']}, Count: {$s['count']}<br>";
                            }
                        } else {
                            echo "<br>No submissions found at all for this manager.";
                        }
                        ?>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <!-- Summary Cards -->
            <div class="summary-grid">
                <div class="summary-card">
                    <h3>Total Outlets</h3>
                    <div class="value"><?php echo count($draftSubmissions); ?></div>
                </div>
                <div class="summary-card income">
                    <h3>Total Income</h3>
                    <div class="value">RM <?php echo number_format($totalIncome, 2); ?></div>
                </div>
                <div class="summary-card expenses">
                    <h3>Total Expenses</h3>
                    <div class="value">RM <?php echo number_format($totalExpenses, 2); ?></div>
                </div>
                <div class="summary-card">
                    <h3>Net Amount</h3>
                    <div class="value <?php echo $totalNet >= 0 ? 'positive' : 'negative'; ?>">
                        RM <?php echo number_format($totalNet, 2); ?>
                    </div>
                </div>
            </div>

            <!-- Submissions Table -->
            <div class="submissions-table">
                <h3 style="margin-bottom: 20px;">Outlet Closings for <?php echo date('d M Y', strtotime($selectedDate)); ?></h3>
                <table>
                    <thead>
                        <tr>
                            <th>Outlet</th>
                            <th>Code</th>
                            <th>Income</th>
                            <th>Expenses</th>
                            <th>Net Amount</th>
                            <th>Expense Items</th>
                            <th>Submitted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($draftSubmissions as $sub): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($sub['outlet_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sub['submission_code']); ?></td>
                                <td class="amount positive">RM <?php echo number_format($sub['total_income'], 2); ?></td>
                                <td class="amount negative">RM <?php echo number_format($sub['total_expenses'], 2); ?></td>
                                <td class="amount <?php echo $sub['net_amount'] >= 0 ? 'positive' : 'negative'; ?>">
                                    RM <?php echo number_format($sub['net_amount'], 2); ?>
                                </td>
                                <td><?php echo $sub['expense_count']; ?> items</td>
                                <td><?php echo date('g:i A', strtotime($sub['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Submit Form -->
            <form method="POST" onsubmit="return confirm('Are you sure you want to submit all <?php echo count($draftSubmissions); ?> outlet closings to HQ? Once submitted, all closings will be pending account approval. This action cannot be undone.');">
                <?php echo csrfField(); ?>
                <input type="hidden" name="submission_date" value="<?php echo htmlspecialchars($selectedDate); ?>">
                <button type="submit" name="submit_to_hq" class="btn-submit-hq">
                    📤 Submit All <?php echo count($draftSubmissions); ?> Closings to HQ (Account Approval)
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
