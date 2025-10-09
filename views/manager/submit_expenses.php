<?php
/**
 * Manager - Submit Daily Expenses
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/submission_handler.php';
requireRole('manager');

$user = getCurrentUser();

$success = '';
$error = '';
$debugInfo = [];

// Fetch manager's outlets
$outlets = dbFetchAll(
    "SELECT * FROM outlets WHERE manager_id = :manager_id AND status = 'active' ORDER BY outlet_name",
    ['manager_id' => $user['id']]
);

// Fetch expense categories
$mpBerhadCategories = dbFetchAll(
    "SELECT * FROM expense_categories WHERE category_type = 'mp_berhad' AND status = 'active' ORDER BY category_name"
) ?: [];

$marketCategories = dbFetchAll(
    "SELECT * FROM expense_categories WHERE category_type = 'market' AND status = 'active' ORDER BY category_name"
) ?: [];

// Debug: Check if we have categories
$debugInfo[] = "MP/BERHAD categories: " . count($mpBerhadCategories);
$debugInfo[] = "Market categories: " . count($marketCategories);

if (empty($mpBerhadCategories) && empty($marketCategories)) {
    $error = "⚠️ CRITICAL ERROR: No expense categories found in database! Please add expense categories before creating submissions.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_daily'])) {
    $debugInfo[] = "✓ Form submitted via POST";
    $debugInfo[] = "✓ submit_daily button detected";

    if (!csrfValidatePost()) {
        $error = 'Security validation failed. Please try again.';
        $debugInfo[] = "✗ CSRF validation FAILED";
    } else {
        $debugInfo[] = "✓ CSRF validation passed";

        try {
            // Validate that at least one expense was added
            $hasExpenses = false;
            if (isset($_POST['expenses'])) {
                foreach ($_POST['expenses'] as $type => $expenses) {
                    if (!empty($expenses)) {
                        $hasExpenses = true;
                        break;
                    }
                }
            }

            $debugInfo[] = "Expenses found: " . ($hasExpenses ? "YES" : "NO");

            if (!$hasExpenses) {
                $error = 'Please add at least one expense before submitting.';
            } else {
                $debugInfo[] = "Calling processSubmission()...";
                $result = processSubmission($_POST, $_FILES, $user['id']);
                $debugInfo[] = "processSubmission() returned: " . ($result['success'] ? "SUCCESS" : "FAILED");

                if ($result['success']) {
                    $success = $result['message'] . ' (Code: ' . $result['submission_code'] . ')';
                    $debugInfo[] = "✓ Redirecting to view_history.php";
                    // Clear form by redirecting
                    header('Location: view_history.php?success=1&code=' . urlencode($result['submission_code']));
                    exit;
                } else {
                    $error = $result['message'];
                    $debugInfo[] = "✗ Error: " . $result['message'];
                }
            }
        } catch (Exception $e) {
            $error = 'Submission error: ' . $e->getMessage();
            $debugInfo[] = "✗ EXCEPTION: " . $e->getMessage();
            error_log('Submission exception: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Daily Expenses - Manager</title>
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

        .form-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }

        #mpBerhadExpenses,
        #marketExpenses {
            width: 100%;
            max-width: 100%;
        }

        .form-card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .expense-row .form-row {
            grid-template-columns: 1fr 1fr;
        }

        @media (max-width: 768px) {
            .expense-row .form-row {
                grid-template-columns: 1fr;
            }
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
            border-color: #667eea;
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
            border-left: 4px solid #667eea;
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

        .expenses-section {
            margin-top: 30px;
        }

        .expense-row {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #28a745;
            max-width: 100%;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .expense-row.collapsed {
            background: #e9ecef;
        }

        .expense-row.collapsed .expense-body {
            display: none;
        }

        .expense-row-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0;
            cursor: pointer;
            user-select: none;
        }

        .expense-row.collapsed .expense-row-header {
            margin-bottom: 0;
        }

        .expense-row:not(.collapsed) .expense-row-header {
            margin-bottom: 15px;
        }

        .expense-row-header h4 {
            color: #28a745;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .expense-row-header h4 .toggle-icon {
            font-size: 12px;
            transition: transform 0.3s;
        }

        .expense-row.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }

        .expense-summary {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
            display: none;
        }

        .expense-row.collapsed .expense-summary {
            display: block;
        }

        .expense-body {
            transition: all 0.3s ease;
        }

        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            z-index: 10;
            position: relative;
        }

        .btn-remove:hover {
            background: #c82333;
        }

        .expense-row-header:hover {
            background: rgba(40, 167, 69, 0.05);
            border-radius: 5px;
            padding: 5px;
            margin: -5px;
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

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type="file"] {
            width: 100%;
            padding: 15px;
            border: 2px dashed #dc3545;
            border-radius: 5px;
            cursor: pointer;
            background: #fff5f5;
            font-size: 14px;
        }

        .file-input-wrapper input[type="file"]:hover {
            border-color: #c82333;
            background: #ffe6e6;
        }

        .file-input-wrapper input[type="file"]:valid {
            border-color: #28a745;
            background: #f0fff0;
        }

        .file-upload-label {
            font-weight: 600;
            color: #dc3545;
        }

        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
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
            border-bottom: 1px solid rgba(255,255,255,0.2);
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
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 30px;
            transition: background 0.3s;
        }

        .btn-submit:hover {
            background: #218838;
        }

        .section-divider {
            margin: 40px 0;
            border-top: 2px dashed #ddd;
        }

        /* Loading Overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-content {
            background: white;
            padding: 40px 60px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-content h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .loading-content p {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Outlet Closing </h1>
            <div class="header-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="view_history.php">View History</a>
                <a href="/my_site/auth/logout.php">Logout</a>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <h3>Processing Submission...</h3>
            <p>Please wait while we upload your files and save the data.</p>
        </div>
    </div>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($debugInfo)): ?>
            <div class="alert" style="background: #e7f3ff; color: #004085; border: 1px solid #b3d7ff;">
                <strong>🐛 Debug Info:</strong><br>
                <?php foreach ($debugInfo as $info): ?>
                    <?php echo htmlspecialchars($info); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="submissionForm">
            <?php echo csrfField(); ?>

            <!-- BASIC INFO -->
            <div class="form-card">
                <h2>📋 Submission Information</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label>Select Outlet <span class="required">*</span></label>
                        <select name="outlet_id" required>
                            <option value="">-- Choose Outlet --</option>
                            <?php foreach ($outlets as $outlet): ?>
                                <option value="<?php echo $outlet['id']; ?>">
                                    <?php echo htmlspecialchars($outlet['outlet_name']); ?> (<?php echo htmlspecialchars($outlet['outlet_code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Submission Date <span class="required">*</span></label>
                        <input type="date" name="submission_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <!-- Duplicate Warning -->
                <div id="duplicateWarning" style="display: none; margin-top: 15px; padding: 15px; background: #fff3cd; border: 2px solid #856404; border-radius: 8px; color: #856404;">
                    <strong>⚠️ Warning:</strong> <span id="duplicateMessage"></span>
                </div>
            </div>

            <!-- INCOME SECTION -->
            <div class="form-card">
                <h2>💰 Income Streams</h2>

                <div class="income-grid">
                    <!-- Berhad Sales -->
                    <div class="income-item">
                        <label>Berhad Sales (RM) <span class="required">*</span></label>
                        <input type="number" name="berhad_sales" step="0.01" min="0" value="0.00" class="income-input" required>
                    </div>

                    <!-- MP Sales - Coba -->
                    <div class="income-item">
                        <label>MP Sales - Coba (RM) <span class="required">*</span></label>
                        <input type="number" name="mp_coba_sales" step="0.01" min="0" value="0.00" class="income-input" required>
                    </div>

                    <!-- MP Sales - Perdana -->
                    <div class="income-item">
                        <label>MP Sales - Perdana (RM) <span class="required">*</span></label>
                        <input type="number" name="mp_perdana_sales" step="0.01" min="0" value="0.00" class="income-input" required>
                    </div>

                    <!-- Market Sales -->
                    <div class="income-item">
                        <label>Market Sales (RM) <span class="required">*</span></label>
                        <input type="number" name="market_sales" step="0.01" min="0" value="0.00" class="income-input" required>
                    </div>
                </div>

                <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 5px;">
                    <strong>Total Income: RM <span id="totalIncome">0.00</span></strong>
                </div>
            </div>

            <div class="section-divider"></div>

            <!-- EXPENSES SECTION - MP/BERHAD -->
            <div class="form-card">
                <h2>📊 Expenses - MP / BERHAD</h2>
                <p style="color: #666; font-size: 13px; margin-bottom: 15px;">
                    💡 <em>Tip: Click on expense header to collapse/expand. Previous expenses auto-minimize when adding new ones.</em>
                </p>
                <div id="mpBerhadExpenses">
                    <!-- Expenses will be added dynamically -->
                </div>
                <button type="button" class="btn-add" onclick="addExpense('mp_berhad')">+ Add MP/BERHAD Expense</button>
            </div>

            <!-- EXPENSES SECTION - MARKET -->
            <div class="form-card">
                <h2>🏪 Expenses - Market</h2>
                <p style="color: #666; font-size: 13px; margin-bottom: 15px;">
                    💡 <em>Tip: Click on expense header to collapse/expand. Previous expenses auto-minimize when adding new ones.</em>
                </p>
                <div id="marketExpenses">
                    <!-- Expenses will be added dynamically -->
                </div>
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
                    <textarea name="notes" rows="4" placeholder="Add any additional notes or remarks here..."></textarea>
                </div>
            </div>

            <button type="submit" name="submit_daily" class="btn-submit" onclick="console.log('🖱️ Submit button clicked!');">Submit Daily Report</button>
        </form>
    </div>

    <script>
        console.log('🔧 JavaScript loaded');

        let mpBerhadCounter = 0;
        let marketCounter = 0;

        // Expense categories from PHP
        const mpBerhadCategories = <?php echo json_encode($mpBerhadCategories ?: []); ?>;
        const marketCategories = <?php echo json_encode($marketCategories ?: []); ?>;

        console.log('📊 MP/BERHAD Categories:', mpBerhadCategories);
        console.log('🏪 Market Categories:', marketCategories);

        // Add expense row
        function addExpense(type) {
            console.log('🎯 addExpense() called with type:', type);
            alert('Button clicked! Type: ' + type);

            const container = type === 'mp_berhad' ? document.getElementById('mpBerhadExpenses') : document.getElementById('marketExpenses');
            const categories = type === 'mp_berhad' ? mpBerhadCategories : marketCategories;
            const counter = type === 'mp_berhad' ? mpBerhadCounter++ : marketCounter++;

            console.log('Container:', container);
            console.log('Categories:', categories);
            console.log('Counter:', counter);

            // Collapse all previous expenses in this container
            const existingExpenses = container.querySelectorAll('.expense-row');
            existingExpenses.forEach(row => {
                if (!row.classList.contains('collapsed')) {
                    row.classList.add('collapsed');
                    updateExpenseSummary(row);
                }
            });

            const expenseRow = document.createElement('div');
            expenseRow.className = 'expense-row';
            expenseRow.id = `expense_${type}_${counter}`;

            let categoryOptions = '<option value="">-- Select Category --</option>';
            categories.forEach(cat => {
                categoryOptions += `<option value="${cat.id}">${cat.category_name}</option>`;
            });

            expenseRow.innerHTML = `
                <div class="expense-row-header" onclick="toggleExpense('${type}', ${counter})">
                    <div>
                        <h4>
                            <span class="toggle-icon">▼</span>
                            ${type === 'mp_berhad' ? 'MP/BERHAD' : 'Market'} Expense #${counter + 1}
                        </h4>
                        <div class="expense-summary"></div>
                    </div>
                    <button type="button" class="btn-remove" onclick="event.stopPropagation(); removeExpense('${type}', ${counter})">Remove</button>
                </div>
                <div class="expense-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category <span class="required">*</span></label>
                            <select name="expenses[${type}][${counter}][category_id]" class="expense-category" required>
                                ${categoryOptions}
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Amount (RM) <span class="required">*</span></label>
                            <input type="number" name="expenses[${type}][${counter}][amount]" step="0.01" min="0.01" class="expense-input" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description (Optional)</label>
                        <input type="text" name="expenses[${type}][${counter}][description]" placeholder="Brief description of this expense">
                    </div>
                    <div class="form-group">
                        <label class="file-upload-label">📎 Upload Receipt / Payment Voucher <span class="required">*</span></label>
                        <div class="file-input-wrapper">
                            <input type="file" name="expenses[${type}][${counter}][receipt]" accept="image/*,.pdf" required>
                        </div>
                        <small style="color: #dc3545; font-weight: 600;">⚠️ Required: JPG, PNG, or PDF (Max 5MB)</small>
                    </div>
                </div>
            `;

            container.appendChild(expenseRow);

            // Add event listeners
            const amountInput = expenseRow.querySelector('.expense-input');
            const categorySelect = expenseRow.querySelector('.expense-category');
            const fileInput = expenseRow.querySelector('input[type="file"]');

            amountInput.addEventListener('input', function() {
                calculateTotals();
                updateExpenseSummary(expenseRow);
            });

            categorySelect.addEventListener('change', function() {
                updateExpenseSummary(expenseRow);
            });

            // File upload feedback
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const fileWrapper = this.parentElement;
                    const smallText = fileWrapper.nextElementSibling;

                    if (this.files.length > 0) {
                        const fileName = this.files[0].name;
                        const fileSize = (this.files[0].size / 1024 / 1024).toFixed(2);

                        // Change border to green and show success message
                        this.style.borderColor = '#28a745';
                        this.style.background = '#f0fff0';

                        if (smallText) {
                            smallText.style.color = '#28a745';
                            smallText.innerHTML = `✅ File selected: ${fileName} (${fileSize} MB)`;
                        }
                    } else {
                        // Reset to warning state
                        this.style.borderColor = '#dc3545';
                        this.style.background = '#fff5f5';

                        if (smallText) {
                            smallText.style.color = '#dc3545';
                            smallText.innerHTML = '⚠️ Required: JPG, PNG, or PDF (Max 5MB)';
                        }
                    }
                });
            }
        }

        // Toggle expense expand/collapse
        function toggleExpense(type, counter) {
            const row = document.getElementById(`expense_${type}_${counter}`);
            if (row) {
                row.classList.toggle('collapsed');
                if (row.classList.contains('collapsed')) {
                    updateExpenseSummary(row);
                }
            }
        }

        // Update expense summary when collapsed
        function updateExpenseSummary(row) {
            const summaryDiv = row.querySelector('.expense-summary');
            const categorySelect = row.querySelector('.expense-category');
            const amountInput = row.querySelector('.expense-input');

            if (summaryDiv && categorySelect && amountInput) {
                const hasSelectedOption =
                    categorySelect.selectedIndex !== undefined &&
                    categorySelect.selectedIndex !== null &&
                    categorySelect.selectedIndex >= 0 &&
                    categorySelect.options &&
                    categorySelect.options.length > categorySelect.selectedIndex;
                const categoryText = hasSelectedOption
                    ? categorySelect.options[categorySelect.selectedIndex].text
                    : 'No category';
                const amount = parseFloat(amountInput.value) || 0;

                if (categorySelect.value && amount > 0) {
                    summaryDiv.textContent = `${categoryText} - RM ${amount.toFixed(2)}`;
                } else if (categorySelect.value) {
                    summaryDiv.textContent = `${categoryText} - No amount`;
                } else {
                    summaryDiv.textContent = 'Click to expand and fill details';
                }
            }
        }

        // Remove expense row
        function removeExpense(type, counter) {
            const row = document.getElementById(`expense_${type}_${counter}`);
            if (row) {
                row.remove();
                calculateTotals();
            }
        }

        // Calculate totals
        function calculateTotals() {
            // Calculate total income
            const incomeInputs = document.querySelectorAll('.income-input');
            let totalIncome = 0;
            incomeInputs.forEach(input => {
                totalIncome += parseFloat(input.value) || 0;
            });

            // Calculate total expenses
            const expenseInputs = document.querySelectorAll('.expense-input');
            let totalExpenses = 0;
            expenseInputs.forEach(input => {
                totalExpenses += parseFloat(input.value) || 0;
            });

            // Calculate net
            const netAmount = totalIncome - totalExpenses;

            // Update display
            document.getElementById('totalIncome').textContent = totalIncome.toFixed(2);
            document.getElementById('summaryIncome').textContent = totalIncome.toFixed(2);
            document.getElementById('summaryExpenses').textContent = totalExpenses.toFixed(2);
            document.getElementById('summaryNet').textContent = netAmount.toFixed(2);
        }

        // Check for duplicate submission
        function checkDuplicateSubmission() {
            const outletSelect = document.querySelector('select[name="outlet_id"]');
            const dateInput = document.querySelector('input[name="submission_date"]');
            const warningDiv = document.getElementById('duplicateWarning');
            const messageSpan = document.getElementById('duplicateMessage');

            if (!outletSelect.value || !dateInput.value) {
                warningDiv.style.display = 'none';
                return;
            }

            // Make AJAX request
            fetch('/my_site/includes/check_duplicate_submission.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `outlet_id=${outletSelect.value}&submission_date=${dateInput.value}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    const outletName = outletSelect.options[outletSelect.selectedIndex].text;
                    const dateFormatted = new Date(dateInput.value).toLocaleDateString('en-MY', {
                        year: 'numeric', month: 'short', day: 'numeric'
                    });

                    if (data.status === 'draft') {
                        messageSpan.innerHTML = 'A submission for <strong>' + outletName + '</strong> on <strong>' + dateFormatted + '</strong> already exists as DRAFT. ' +
                            '<a href="edit_submission.php?id=' + data.submission_id + '" style="color: #856404; text-decoration: underline; font-weight: bold;">Click here to edit it</a> ' +
                            'instead of creating a new one.';
                    } else {
                        messageSpan.innerHTML = 'A submission for <strong>' + outletName + '</strong> on <strong>' + dateFormatted + '</strong> already exists with status: <strong>' + data.status.toUpperCase() + '</strong>. ' +
                            'You cannot create another submission for the same outlet and date.';
                    }
                    warningDiv.style.display = 'block';
                } else {
                    warningDiv.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error checking duplicate:', error);
            });
        }

        // Add event listeners to income inputs
        console.log('JavaScript file loaded');

        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOMContentLoaded fired');

            // Add duplicate check listeners
            const outletSelect = document.querySelector('select[name="outlet_id"]');
            const dateInput = document.querySelector('input[name="submission_date"]');

            if (outletSelect) {
                outletSelect.addEventListener('change', checkDuplicateSubmission);
            }
            if (dateInput) {
                dateInput.addEventListener('change', checkDuplicateSubmission);
            }

            const incomeInputs = document.querySelectorAll('.income-input');
            incomeInputs.forEach(input => {
                input.addEventListener('input', calculateTotals);
            });

            // Initial calculation
            calculateTotals();

            // Form submission with loading
            const form = document.getElementById('submissionForm');

            if (!form) {
                console.error('ERROR: Form not found!');
            } else {
                console.log('Form found, attaching submit listener');

                form.addEventListener('submit', function(e) {
                    console.log('Form submit event triggered');

                    // Validate form has expenses
                    const mpExpenses = document.getElementById('mpBerhadExpenses').children.length;
                    const marketExpenses = document.getElementById('marketExpenses').children.length;

                    console.log('MP expenses:', mpExpenses, 'Market expenses:', marketExpenses);

                    if (mpExpenses === 0 && marketExpenses === 0) {
                        e.preventDefault();
                        alert('Please add at least one expense before submitting.');
                        console.log('Form submission prevented - no expenses');
                        return false;
                    }

                    // TEMPORARY: Skip detailed validation for testing
                    console.log('Skipping detailed validation - allowing submission');
                    return true;

                    /* DETAILED VALIDATION - COMMENTED OUT FOR NOW */
                    /*

                    // Validate each expense and collect incomplete ones
                    let incompleteExpenseRows = [];
                    let errorMessages = [];
                    const allExpenseRows = document.querySelectorAll('.expense-row');

                    allExpenseRows.forEach((row, index) => {
                        const categorySelect = row.querySelector('.expense-category');
                        const amountInput = row.querySelector('.expense-input');
                        const fileInput = row.querySelector('input[type="file"]');
                        const expenseHeader = row.querySelector('.expense-row-header h4');
                        const expenseName = expenseHeader ? expenseHeader.textContent.trim() : `Expense #${index + 1}`;

                        let errors = [];

                        // Check category
                        if (categorySelect && !categorySelect.value) {
                            errors.push('Missing category');
                            categorySelect.style.border = '2px solid #dc3545';
                            categorySelect.style.background = '#fff5f5';
                        } else if (categorySelect) {
                            categorySelect.style.border = '';
                            categorySelect.style.background = '';
                        }

                        // Check amount
                        if (amountInput && (!amountInput.value || parseFloat(amountInput.value) <= 0)) {
                            errors.push('Missing or invalid amount');
                            amountInput.style.border = '2px solid #dc3545';
                            amountInput.style.background = '#fff5f5';
                        } else if (amountInput) {
                            amountInput.style.border = '';
                            amountInput.style.background = '';
                        }

                        // Check receipt
                        if (fileInput && fileInput.files.length === 0) {
                            errors.push('Missing receipt/voucher');
                            fileInput.style.border = '2px solid #dc3545';
                            fileInput.style.background = '#fff5f5';
                        } else if (fileInput) {
                            fileInput.style.border = '';
                            fileInput.style.background = '';
                        }

                        // If this expense has errors, mark it
                        if (errors.length > 0) {
                            incompleteExpenseRows.push(row);
                            errorMessages.push(`${expenseName}: ${errors.join(', ')}`);
                        }
                    });

                    if (incompleteExpenseRows.length > 0) {
                        e.preventDefault();

                        // Expand all incomplete expense rows
                        incompleteExpenseRows.forEach(row => {
                            row.classList.remove('collapsed');
                        });

                        // Scroll to the first incomplete expense
                        if (incompleteExpenseRows[0]) {
                            incompleteExpenseRows[0].scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });

                            // Flash the first incomplete expense
                            incompleteExpenseRows[0].style.transition = 'all 0.3s';
                            incompleteExpenseRows[0].style.border = '3px solid #dc3545';
                            incompleteExpenseRows[0].style.boxShadow = '0 0 20px rgba(220, 53, 69, 0.5)';

                            setTimeout(() => {
                                incompleteExpenseRows[0].style.border = '';
                                incompleteExpenseRows[0].style.boxShadow = '';
                            }, 2000);
                        }

                        const message = 'Please Complete All Expense Details!\n\n' +
                                       `Found ${incompleteExpenseRows.length} incomplete expense(s):\n\n` +
                                       errorMessages.map((msg, i) => `${i + 1}. ${msg}`).join('\n') +
                                       '\n\nIncomplete expenses have been expanded and highlighted.\nPlease scroll up to complete them.';

                        alert(message);
                        console.log('Form submission prevented - incomplete expenses:', errorMessages);
                        return false;
                    }

                    */

                    console.log('All validations passed, showing loading overlay');

                    // Show loading overlay
                    document.getElementById('loadingOverlay').classList.add('active');

                    // Disable submit button to prevent double submission
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Submitting...';
                    }

                    console.log('Form submitting to server...');
                    // Form will submit normally
                });

                console.log('Form submit listener attached successfully');
            }

            console.log('DOMContentLoaded complete');
        });

        console.log('JavaScript execution complete');
    </script>
</body>
</html>
