<?php
/**
 * Debug - Check Expense Categories
 */

require_once __DIR__ . '/includes/init.php';
requireRole('manager');

// Fetch expense categories
$mpBerhadCategories = dbFetchAll(
    "SELECT * FROM expense_categories WHERE category_type = 'mp_berhad' AND status = 'active' ORDER BY category_name"
);

$marketCategories = dbFetchAll(
    "SELECT * FROM expense_categories WHERE category_type = 'market' AND status = 'active' ORDER BY category_name"
);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Categories</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        h2 { color: #333; }
        .count { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🐛 Debug Expense Categories</h1>

    <div class="section">
        <h2>MP/BERHAD Categories</h2>
        <p>Count: <span class="count"><?php echo count($mpBerhadCategories); ?></span></p>
        <pre><?php print_r($mpBerhadCategories); ?></pre>
    </div>

    <div class="section">
        <h2>Market Categories</h2>
        <p>Count: <span class="count"><?php echo count($marketCategories); ?></span></p>
        <pre><?php print_r($marketCategories); ?></pre>
    </div>

    <div class="section">
        <h2>JavaScript Test</h2>
        <button type="button" onclick="testFunction()" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Click Me to Test JavaScript
        </button>
        <p id="result" style="margin-top: 10px; font-weight: bold;"></p>
    </div>

    <script>
        console.log('🔧 JavaScript loaded successfully!');

        const mpBerhadCategories = <?php echo json_encode($mpBerhadCategories); ?>;
        const marketCategories = <?php echo json_encode($marketCategories); ?>;

        console.log('MP/BERHAD Categories:', mpBerhadCategories);
        console.log('Market Categories:', marketCategories);

        function testFunction() {
            alert('JavaScript is working!');
            document.getElementById('result').textContent = '✅ JavaScript is working perfectly!';
            document.getElementById('result').style.color = '#28a745';
        }

        // Test if categories are valid
        if (!Array.isArray(mpBerhadCategories)) {
            console.error('❌ mpBerhadCategories is not an array!');
        }
        if (!Array.isArray(marketCategories)) {
            console.error('❌ marketCategories is not an array!');
        }

        console.log('✅ All checks complete');
    </script>

    <p><a href="views/manager/submit_expenses.php">← Back to Submit Expenses</a></p>
</body>
</html>
