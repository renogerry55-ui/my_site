<?php
/**
 * Manager - Edit Draft Submission (Full Form)
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/submission_handler.php';
require_once __DIR__ . '/../../includes/update_submission.php';
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

// Fetch submission
$submission = dbFetchOne("
    SELECT ds.*, o.outlet_name, o.outlet_code
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
$existingExpenses = [];
if ($submission) {
    $existingExpenses = dbFetchAll("
        SELECT e.*, ec.category_name, ec.category_type
        FROM expenses e
        INNER JOIN expense_categories ec ON e.expense_category_id = ec.id
        WHERE e.submission_id = :submission_id
        ORDER BY ec.category_type, e.id
    ", ['submission_id' => $submissionId]);
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_submission']) && !$error) {
    if (!csrfValidatePost()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        try {
            $result = updateSubmission($submissionId, $_POST, $_FILES, $user['id']);

            if ($result['success']) {
                $success = $result['message'] . ' (Code: ' . $result['submission_code'] . ')';
                // Refresh submission data
                $submission = dbFetchOne("
                    SELECT ds.*, o.outlet_name, o.outlet_code
                    FROM daily_submissions ds
                    INNER JOIN outlets o ON ds.outlet_id = o.id
                    WHERE ds.id = :id
                ", ['id' => $submissionId]);

                $existingExpenses = dbFetchAll("
                    SELECT e.*, ec.category_name, ec.category_type
                    FROM expenses e
                    INNER JOIN expense_categories ec ON e.expense_category_id = ec.id
                    WHERE e.submission_id = :submission_id
                    ORDER BY ec.category_type, e.id
                ", ['submission_id' => $submissionId]);
            } else {
                $error = $result['message'];
            }
        } catch (Exception $e) {
            $error = 'Update error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Submission - Manager</title>
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

        .edit-notice {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .edit-notice h2 {
            color: #856404;
            margin-bottom: 10px;
        }

        .edit-notice p {
            margin: 5px 0;
            color: #856404;
        }

        .form-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .form-card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ffc107;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group label .required {
            color: #e74c3c;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ffc107;
        }

        .income-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        @media (max-width: 768px) {
            .income-grid {
                grid-template-columns: 1fr;
            }
        }

        .income-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }

        .income-item label {
            font-size: 14px;
            color: #666;
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
        }

        .income-item input {
            margin-top: 5px;
            font-size: 18px;
            font-weight: 700;
        }

        .expense-row {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #28a745;
        }

        .expense-row-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .expense-row-header h4 {
            color: #28a745;
        }

        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }

        .btn-remove:hover {
            background: #c82333;
        }

        .btn-add {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-add:hover {
            background: #218838;
        }

        .existing-receipt {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            border: 1px solid #667eea;
        }

        .file-choice {
            margin-top: 10px;
        }

        .file-choice label {
            display: block;
            margin: 5px 0;
        }

        .summary-card {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: #000;
            padding: 25px;
            border-radius: 10px;
            margin-top: 30px;
        }

        .summary-card h3 {
            margin-bottom: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .summary-row:last-child {
            border-bottom: none;
            font-size: 20px;
            font-weight: bold;
            margin-top: 10px;
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: #ff9800;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 30px;
        }

        .btn-submit:hover {
            background: #f57c00;
        }

        .btn-back {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
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
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="edit-notice">
                <h2>📝 Editing Draft Submission</h2>
                <p><strong>Submission Code:</strong> <?php echo htmlspecialchars($submission['submission_code']); ?></p>
                <p><strong>Outlet:</strong> <?php echo htmlspecialchars($submission['outlet_name']); ?> (<?php echo htmlspecialchars($submission['outlet_code']); ?>)</p>
                <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($submission['submission_date'])); ?></p>
                <p><strong>Status:</strong> DRAFT (Pending to Send to HQ)</p>
                <p style="margin-top: 15px; font-size: 14px;">
                    ⚠️ You can edit this submission because it's still in DRAFT status. After editing, click "Update Submission" to save changes.
                </p>
            </div>

            <form method="POST" enctype="multipart/form-data" id="editForm">
                <?php echo csrfField(); ?>

                <!-- INCOME SECTION -->
                <div class="form-card">
                    <h2>💰 Income Streams</h2>

                    <div class="income-grid">
                        <div class="income-item">
                            <label>Berhad Sales (RM) <span class="required">*</span></label>
                            <input type="number" name="berhad_sales" step="0.01" min="0"
                                   value="<?php echo number_format($submission['berhad_sales'], 2, '.', ''); ?>"
                                   class="income-input" required>
                        </div>

                        <div class="income-item">
                            <label>MP Sales - Coba (RM) <span class="required">*</span></label>
                            <input type="number" name="mp_coba_sales" step="0.01" min="0"
                                   value="<?php echo number_format($submission['mp_coba_sales'], 2, '.', ''); ?>"
                                   class="income-input" required>
                        </div>

                        <div class="income-item">
                            <label>MP Sales - Perdana (RM) <span class="required">*</span></label>
                            <input type="number" name="mp_perdana_sales" step="0.01" min="0"
                                   value="<?php echo number_format($submission['mp_perdana_sales'], 2, '.', ''); ?>"
                                   class="income-input" required>
                        </div>

                        <div class="income-item">
                            <label>Market Sales (RM) <span class="required">*</span></label>
                            <input type="number" name="market_sales" step="0.01" min="0"
                                   value="<?php echo number_format($submission['market_sales'], 2, '.', ''); ?>"
                                   class="income-input" required>
                        </div>
                    </div>

                    <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 5px;">
                        <strong>Total Income: RM <span id="totalIncome">0.00</span></strong>
                    </div>
                </div>

                <!-- EXPENSES SECTION - MP/BERHAD -->
                <div class="form-card">
                    <h2>📊 Expenses - MP / BERHAD</h2>
                    <div id="mpBerhadExpenses"></div>
                    <button type="button" class="btn-add" onclick="addExpense('mp_berhad')">+ Add MP/BERHAD Expense</button>
                </div>

                <!-- EXPENSES SECTION - MARKET -->
                <div class="form-card">
                    <h2>🏪 Expenses - Market</h2>
                    <div id="marketExpenses"></div>
                    <button type="button" class="btn-add" onclick="addExpense('market')">+ Add Market Expense</button>
                </div>

                <!-- SUMMARY -->
                <div class="summary-card">
                    <h3>📈 Submission Summary</h3>
                    <div class="summary-row">
                        <span>Total Income:</span>
                        <span>RM <span id="summaryIncome">0.00</span></span>
                    </div>
                    <div class="summary-row">
                        <span>Total Expenses:</span>
                        <span>RM <span id="summaryExpenses">0.00</span></span>
                    </div>
                    <div class="summary-row">
                        <span>Net Amount:</span>
                        <span>RM <span id="summaryNet">0.00</span></span>
                    </div>
                </div>

                <!-- NOTES -->
                <div class="form-card">
                    <h2>📝 Additional Notes (Optional)</h2>
                    <div class="form-group">
                        <textarea name="notes" rows="4" placeholder="Add any additional notes or remarks here..."><?php echo htmlspecialchars($submission['notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div style="display: flex; gap: 15px;">
                    <a href="view_history.php" class="btn-back">← Cancel & Back</a>
                    <button type="submit" name="update_submission" class="btn-submit" style="flex: 1;">💾 Update Submission</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        let mpBerhadCounter = 0;
        let marketCounter = 0;

        const mpBerhadCategories = <?php echo json_encode($mpBerhadCategories); ?>;
        const marketCategories = <?php echo json_encode($marketCategories); ?>;

        // Load existing expenses
        const existingExpenses = <?php echo json_encode($existingExpenses); ?>;

        function addExpense(type, existingData = null) {
            const container = type === 'mp_berhad' ? document.getElementById('mpBerhadExpenses') : document.getElementById('marketExpenses');
            const categories = type === 'mp_berhad' ? mpBerhadCategories : marketCategories;
            const counter = type === 'mp_berhad' ? mpBerhadCounter++ : marketCounter++;

            const expenseRow = document.createElement('div');
            expenseRow.className = 'expense-row';
            expenseRow.id = `expense_${type}_${counter}`;

            let categoryOptions = '<option value="">-- Select Category --</option>';
            categories.forEach(cat => {
                const selected = existingData && cat.id == existingData.expense_category_id ? 'selected' : '';
                categoryOptions += `<option value="${cat.id}" ${selected}>${cat.category_name}</option>`;
            });

            const amountValue = existingData ? existingData.amount : '';
            const descValue = existingData ? existingData.description : '';
            const receiptFile = existingData ? existingData.receipt_file : '';

            let receiptHTML = '';
            if (receiptFile) {
                receiptHTML = `
                    <div class="existing-receipt">
                        <strong>📎 Current Receipt:</strong> ${receiptFile}
                        <div class="file-choice">
                            <label>
                                <input type="radio" name="expenses[${type}][${counter}][file_choice]" value="keep" checked>
                                Keep existing receipt
                            </label>
                            <label>
                                <input type="radio" name="expenses[${type}][${counter}][file_choice]" value="replace">
                                Replace with new file
                            </label>
                        </div>
                        <input type="hidden" name="expenses[${type}][${counter}][keep_receipt]" value="${receiptFile}">
                        <div id="newFile_${type}_${counter}" style="display: none; margin-top: 10px;">
                            <input type="file" name="expenses[${type}][${counter}][receipt]" accept="image/*,.pdf">
                        </div>
                    </div>
                `;
            } else {
                receiptHTML = `
                    <input type="file" name="expenses[${type}][${counter}][receipt]" accept="image/*,.pdf" required>
                    <small style="color: #dc3545;">⚠️ Required: JPG, PNG, or PDF (Max 5MB)</small>
                `;
            }

            expenseRow.innerHTML = `
                <div class="expense-row-header">
                    <h4>${type === 'mp_berhad' ? 'MP/BERHAD' : 'Market'} Expense #${counter + 1}</h4>
                    <button type="button" class="btn-remove" onclick="removeExpense('${type}', ${counter})">Remove</button>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Category <span class="required">*</span></label>
                        <select name="expenses[${type}][${counter}][category_id]" class="expense-category" required>
                            ${categoryOptions}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount (RM) <span class="required">*</span></label>
                        <input type="number" name="expenses[${type}][${counter}][amount]" step="0.01" min="0.01"
                               value="${amountValue}" class="expense-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description (Optional)</label>
                    <input type="text" name="expenses[${type}][${counter}][description]"
                           value="${descValue}" placeholder="Brief description">
                </div>
                <div class="form-group">
                    <label>📎 Receipt / Payment Voucher</label>
                    ${receiptHTML}
                </div>
            `;

            container.appendChild(expenseRow);

            // Add event listeners for file choice radio buttons
            if (receiptFile) {
                const radios = expenseRow.querySelectorAll('input[type="radio"]');
                radios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        const newFileDiv = document.getElementById(`newFile_${type}_${counter}`);
                        if (this.value === 'replace') {
                            newFileDiv.style.display = 'block';
                            newFileDiv.querySelector('input[type="file"]').required = true;
                        } else {
                            newFileDiv.style.display = 'none';
                            newFileDiv.querySelector('input[type="file"]').required = false;
                        }
                    });
                });
            }

            // Add input listener for totals
            const amountInput = expenseRow.querySelector('.expense-input');
            amountInput.addEventListener('input', calculateTotals);
        }

        function removeExpense(type, counter) {
            const row = document.getElementById(`expense_${type}_${counter}`);
            if (row) {
                row.remove();
                calculateTotals();
            }
        }

        function calculateTotals() {
            const incomeInputs = document.querySelectorAll('.income-input');
            let totalIncome = 0;
            incomeInputs.forEach(input => {
                totalIncome += parseFloat(input.value) || 0;
            });

            const expenseInputs = document.querySelectorAll('.expense-input');
            let totalExpenses = 0;
            expenseInputs.forEach(input => {
                totalExpenses += parseFloat(input.value) || 0;
            });

            const netAmount = totalIncome - totalExpenses;

            document.getElementById('totalIncome').textContent = totalIncome.toFixed(2);
            document.getElementById('summaryIncome').textContent = totalIncome.toFixed(2);
            document.getElementById('summaryExpenses').textContent = totalExpenses.toFixed(2);
            document.getElementById('summaryNet').textContent = netAmount.toFixed(2);
        }

        // Load existing expenses on page load
        document.addEventListener('DOMContentLoaded', function() {
            existingExpenses.forEach(expense => {
                addExpense(expense.category_type, expense);
            });

            // Add event listeners to income inputs
            const incomeInputs = document.querySelectorAll('.income-input');
            incomeInputs.forEach(input => {
                input.addEventListener('input', calculateTotals);
            });

            // Initial calculation
            calculateTotals();
        });
    </script>
</body>
</html>
