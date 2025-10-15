<?php
/**
 * Account - Expenses Verification
 * Review uncategorized expenses submitted by managers
 */

require_once __DIR__ . '/../../includes/init.php';
requireRole('account');

$user = getCurrentUser();
$managerId = filter_input(INPUT_GET, 'manager_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$errors = [];
$manager = null;
$submissions = [];

if (!$managerId) {
    $errors[] = 'Missing or invalid manager identifier.';
} else {
    // Fetch manager details
    $manager = dbFetchOne(
        "SELECT id, name, email FROM users WHERE id = :id AND role = 'manager'",
        ['id' => $managerId]
    );

    if (!$manager) {
        $errors[] = 'Manager not found.';
    }
}

if (empty($errors) && $manager) {
    // Fetch all pending submissions with uncategorized expenses
    $submissionRows = dbFetchAll(
        "SELECT DISTINCT
            ds.id,
            ds.submission_code,
            ds.outlet_id,
            ds.submission_date,
            ds.total_expenses,
            ds.batch_code,
            o.outlet_name,
            o.outlet_code
        FROM daily_submissions ds
        INNER JOIN outlets o ON ds.outlet_id = o.id
        INNER JOIN expenses e ON e.submission_id = ds.id
        INNER JOIN expense_categories ec ON e.expense_category_id = ec.id
        WHERE ds.manager_id = :manager_id
          AND ds.status = 'pending'
          AND UPPER(ec.category_name) = 'UNCATEGORIZED'
        ORDER BY ds.submission_date ASC, o.outlet_name ASC",
        ['manager_id' => $managerId]
    );

    if ($submissionRows) {
        foreach ($submissionRows as $row) {
            $submissionId = (int) $row['id'];

            // Fetch expense details for this submission
            $expenses = dbFetchAll(
                "SELECT
                    e.id,
                    e.amount,
                    e.description,
                    e.receipt_file,
                    ec.category_name
                FROM expenses e
                INNER JOIN expense_categories ec ON e.expense_category_id = ec.id
                WHERE e.submission_id = :submission_id
                  AND UPPER(ec.category_name) = 'UNCATEGORIZED'",
                ['submission_id' => $submissionId]
            );

            // Parse receipt files (stored as JSON array)
            $receiptFiles = [];
            if (!empty($expenses)) {
                foreach ($expenses as $expense) {
                    $receiptFileData = $expense['receipt_file'];
                    $decoded = json_decode($receiptFileData, true);
                    if (is_array($decoded)) {
                        $receiptFiles = array_merge($receiptFiles, $decoded);
                    } elseif (!empty($receiptFileData)) {
                        // Single file (old format)
                        $receiptFiles[] = $receiptFileData;
                    }
                }
            }

            $submissions[] = [
                'id' => $submissionId,
                'submission_code' => $row['submission_code'],
                'outlet_id' => (int) $row['outlet_id'],
                'outlet_name' => $row['outlet_name'],
                'outlet_code' => $row['outlet_code'],
                'submission_date' => $row['submission_date'],
                'total_expenses' => (float) $row['total_expenses'],
                'batch_code' => $row['batch_code'],
                'receipt_files' => $receiptFiles,
                'expense_details' => $expenses
            ];
        }
    }
}

$totalExpensesAmount = array_sum(array_column($submissions, 'total_expenses'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses Verification - <?php echo htmlspecialchars(APP_NAME); ?></title>
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
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .breadcrumbs {
            font-size: 14px;
            opacity: 0.9;
        }

        .breadcrumbs a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
        }

        .breadcrumbs span {
            margin: 0 4px;
            opacity: 0.7;
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

        .alert {
            padding: 16px 18px;
            background-color: #fee2e2;
            color: #7f1d1d;
            border: 1px solid #fecaca;
            border-radius: 8px;
            margin: 20px 0;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .summary-card {
            background: #fff3e0;
            border: 1px solid #ffe0b2;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        .summary-card span {
            display: block;
            font-size: 12px;
            color: #e65100;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .summary-card strong {
            display: block;
            font-size: 22px;
            color: #f57c00;
        }

        .submission-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            padding: 20px 22px;
            margin-bottom: 20px;
        }

        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .submission-header h3 {
            color: #11998e;
            margin-bottom: 6px;
        }

        .submission-meta {
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }

        .expense-info {
            background: #fff3e0;
            border: 1px solid #ffe0b2;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .expense-info-title {
            font-size: 14px;
            font-weight: 600;
            color: #e65100;
            margin-bottom: 8px;
        }

        .expense-info-amount {
            font-size: 24px;
            font-weight: 700;
            color: #f57c00;
        }

        .receipts-section {
            margin-top: 20px;
        }

        .receipts-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .receipts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .receipt-item {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }

        .receipt-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-color: #11998e;
        }

        .receipt-preview {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .receipt-pdf-icon {
            width: 100%;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            border-radius: 4px;
            margin-bottom: 8px;
            font-size: 48px;
            color: white;
        }

        .receipt-name {
            font-size: 12px;
            color: #666;
            word-break: break-all;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
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
            font-weight: 600;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
            color: white;
        }

        .btn-secondary {
            background: #ffffff;
            border: 1px solid #11998e;
            color: #11998e;
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

        /* Modal for image preview */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #fff;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }

        .modal-close:hover {
            color: #bbb;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><?php echo htmlspecialchars(APP_NAME); ?> &mdash; Account</h1>
            <div class="breadcrumbs">
                <a href="dashboard.php">Dashboard</a>
                <span>›</span>
                <a href="verify_submission.php">Pending Submissions</a>
                <span>›</span>
                <strong>Expenses Verification</strong>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($errors)) : ?>
            <div class="alert">
                <strong>Unable to load expenses verification.</strong>
                <ul>
                    <?php foreach ($errors as $error) : ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="actions" style="margin-top:16px; border-top: none; padding-top: 0;">
                    <a class="btn btn-secondary" href="verify_submission.php">Back to Pending Submissions</a>
                </div>
            </div>
        <?php elseif ($manager) : ?>
            <div class="page-header">
                <h2>Expenses Verification - <?php echo htmlspecialchars($manager['name']); ?></h2>
                <p>Review all uncategorized expenses and receipts submitted by this manager. Approve if receipts match the submitted amount, or request resubmission if discrepancies are found.</p>
            </div>

            <?php if (empty($submissions)) : ?>
                <div class="empty-state">
                    <h3>No Pending Expenses</h3>
                    <p>This manager has no uncategorized expenses pending verification.</p>
                    <div class="actions" style="justify-content: center; margin-top: 20px; border-top: none; padding-top: 0;">
                        <a href="verify_submission.php" class="btn btn-secondary">Back to Pending Submissions</a>
                    </div>
                </div>
            <?php else : ?>
                <div class="summary-grid">
                    <div class="summary-card">
                        <span>Manager</span>
                        <strong><?php echo htmlspecialchars($manager['name']); ?></strong>
                    </div>
                    <div class="summary-card">
                        <span>Total Expenses</span>
                        <strong>RM <?php echo number_format($totalExpensesAmount, 2); ?></strong>
                    </div>
                    <div class="summary-card">
                        <span>Pending Submissions</span>
                        <strong><?php echo count($submissions); ?></strong>
                    </div>
                    <div class="summary-card">
                        <span>Total Receipts</span>
                        <strong><?php echo array_sum(array_map(function($s) { return count($s['receipt_files']); }, $submissions)); ?></strong>
                    </div>
                </div>

                <?php foreach ($submissions as $submission) : ?>
                    <div class="submission-card" data-submission-id="<?php echo (int) $submission['id']; ?>">
                        <div class="submission-header">
                            <div>
                                <h3><?php echo htmlspecialchars($submission['outlet_name']); ?> (<?php echo htmlspecialchars($submission['outlet_code']); ?>)</h3>
                                <div class="submission-meta">
                                    <div><strong>Submission Code:</strong> <?php echo htmlspecialchars($submission['submission_code']); ?></div>
                                    <div><strong>Date:</strong> <?php echo htmlspecialchars(date('F j, Y', strtotime($submission['submission_date']))); ?></div>
                                    <?php if (!empty($submission['batch_code'])) : ?>
                                        <div><strong>Batch:</strong> <?php echo htmlspecialchars($submission['batch_code']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="expense-info">
                            <div class="expense-info-title">Total Expenses Submitted</div>
                            <div class="expense-info-amount">RM <?php echo number_format($submission['total_expenses'], 2); ?></div>
                        </div>

                        <div class="receipts-section">
                            <div class="receipts-title">Uploaded Receipts (<?php echo count($submission['receipt_files']); ?> file<?php echo count($submission['receipt_files']) !== 1 ? 's' : ''; ?>)</div>

                            <?php if (!empty($submission['receipt_files'])) : ?>
                                <div class="receipts-grid">
                                    <?php foreach ($submission['receipt_files'] as $receiptFile) : ?>
                                        <?php
                                            $filePath = '/my_site/uploads/receipts/' . htmlspecialchars($receiptFile);
                                            $fileExt = strtolower(pathinfo($receiptFile, PATHINFO_EXTENSION));
                                            $isPdf = $fileExt === 'pdf';
                                        ?>
                                        <div class="receipt-item" data-file="<?php echo $filePath; ?>">
                                            <?php if ($isPdf) : ?>
                                                <div class="receipt-pdf-icon">📄</div>
                                            <?php else : ?>
                                                <img src="<?php echo $filePath; ?>" alt="Receipt" class="receipt-preview" loading="lazy">
                                            <?php endif; ?>
                                            <div class="receipt-name"><?php echo htmlspecialchars($receiptFile); ?></div>
                                            <a href="<?php echo $filePath; ?>" target="_blank" style="font-size: 12px; color: #11998e; text-decoration: none; display: block; margin-top: 5px;">Open in new tab →</a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <p style="color: #666;">No receipts uploaded for this submission.</p>
                            <?php endif; ?>
                        </div>

                        <div class="actions">
                            <button class="btn btn-success approve-btn" data-submission-id="<?php echo (int) $submission['id']; ?>">✓ Approve Expenses</button>
                            <button class="btn btn-danger resubmit-btn" data-submission-id="<?php echo (int) $submission['id']; ?>">✗ Request Resubmit</button>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="actions" style="margin-top: 30px;">
                    <a href="verify_submission.php" class="btn btn-secondary">Back to Pending Submissions</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Image Preview Modal -->
    <div id="imageModal" class="modal">
        <span class="modal-close">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <script>
        // Image preview modal
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        const closeModal = document.querySelector('.modal-close');

        document.querySelectorAll('.receipt-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target.tagName === 'A') return; // Don't trigger if clicking the link

                const filePath = this.dataset.file;
                const isPdf = filePath.toLowerCase().endsWith('.pdf');

                if (!isPdf) {
                    modal.classList.add('active');
                    modalImg.src = filePath;
                }
            });
        });

        closeModal.addEventListener('click', function() {
            modal.classList.remove('active');
        });

        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });

        // Approve button handler
        document.querySelectorAll('.approve-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const submissionId = this.dataset.submissionId;

                if (!confirm('Are you sure you want to approve these expenses? This action confirms that the receipts match the submitted amount.')) {
                    return;
                }

                // TODO: Implement approve endpoint
                fetch('/my_site/includes/account/approve_expenses.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        submission_id: submissionId,
                        <?php echo csrfFieldName(); ?>: '<?php echo csrfGenerate(); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Expenses approved successfully!');
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to approve expenses.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while approving expenses.');
                });
            });
        });

        // Resubmit button handler
        document.querySelectorAll('.resubmit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const submissionId = this.dataset.submissionId;

                const reason = prompt('Please provide a reason for requesting resubmission:');
                if (!reason || reason.trim() === '') {
                    alert('Reason is required to request resubmission.');
                    return;
                }

                // TODO: Implement resubmit endpoint
                fetch('/my_site/includes/account/reject_expenses.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        submission_id: submissionId,
                        reason: reason.trim(),
                        <?php echo csrfFieldName(); ?>: '<?php echo csrfGenerate(); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Resubmission requested successfully!');
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to request resubmission.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while requesting resubmission.');
                });
            });
        });
    </script>
</body>
</html>
