<?php
/**
 * Debug Submission Issues
 */

require_once __DIR__ . '/includes/init.php';
requireRole('manager');

$user = getCurrentUser();

echo "<h1>Debugging Submission System</h1>";
echo "<hr>";

// 1. Check if user has outlets
echo "<h2>1. Checking Outlets</h2>";
$outlets = dbFetchAll(
    "SELECT * FROM outlets WHERE manager_id = :manager_id AND status = 'active'",
    ['manager_id' => $user['id']]
);
echo "<p>Found " . count($outlets) . " active outlets for manager ID: {$user['id']}</p>";
if (empty($outlets)) {
    echo "<p style='color: red;'><strong>❌ NO OUTLETS FOUND! This is likely the problem.</strong></p>";
    echo "<p>You need to have outlets assigned to your manager account.</p>";
} else {
    echo "<ul>";
    foreach ($outlets as $outlet) {
        echo "<li>{$outlet['outlet_name']} (ID: {$outlet['id']}, Code: {$outlet['outlet_code']})</li>";
    }
    echo "</ul>";
}

echo "<hr>";

// 2. Check expense categories
echo "<h2>2. Checking Expense Categories</h2>";
$mpBerhadCategories = dbFetchAll(
    "SELECT * FROM expense_categories WHERE category_type = 'mp_berhad' AND status = 'active'"
);
$marketCategories = dbFetchAll(
    "SELECT * FROM expense_categories WHERE category_type = 'market' AND status = 'active'"
);
echo "<p>MP/BERHAD Categories: " . count($mpBerhadCategories) . "</p>";
echo "<p>Market Categories: " . count($marketCategories) . "</p>";

if (empty($mpBerhadCategories) && empty($marketCategories)) {
    echo "<p style='color: red;'><strong>❌ NO EXPENSE CATEGORIES FOUND!</strong></p>";
} else {
    echo "<p style='color: green;'>✅ Expense categories are available</p>";
}

echo "<hr>";

// 3. Check database table structure
echo "<h2>3. Checking Database Table Structure</h2>";
$pdo = getDB();
$stmt = $pdo->query("SHOW COLUMNS FROM daily_submissions");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor(); // Close cursor before next query

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Default</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";

// 4. Check recent submissions
echo "<h2>4. Checking Recent Submissions in Database</h2>";
$recentSubmissions = dbFetchAll(
    "SELECT * FROM daily_submissions WHERE manager_id = :manager_id ORDER BY created_at DESC LIMIT 5",
    ['manager_id' => $user['id']]
);

echo "<p>Found " . count($recentSubmissions) . " recent submissions for this manager</p>";
if (!empty($recentSubmissions)) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Code</th><th>Outlet ID</th><th>Date</th><th>Status</th><th>Created</th></tr>";
    foreach ($recentSubmissions as $sub) {
        echo "<tr>";
        echo "<td>{$sub['id']}</td>";
        echo "<td>{$sub['submission_code']}</td>";
        echo "<td>{$sub['outlet_id']}</td>";
        echo "<td>{$sub['submission_date']}</td>";
        echo "<td><strong>{$sub['status']}</strong></td>";
        echo "<td>{$sub['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No submissions found yet.</p>";
}

echo "<hr>";

// 5. Check PHP error log
echo "<h2>5. PHP Configuration</h2>";
echo "<p>Error Reporting: " . error_reporting() . "</p>";
echo "<p>Display Errors: " . ini_get('display_errors') . "</p>";
echo "<p>Upload Max Filesize: " . ini_get('upload_max_filesize') . "</p>";
echo "<p>Post Max Size: " . ini_get('post_max_size') . "</p>";
echo "<p>Max File Uploads: " . ini_get('max_file_uploads') . "</p>";

echo "<hr>";

// 6. Test database connection
echo "<h2>6. Test Database Write</h2>";
try {
    // Get a fresh PDO instance
    $testPdo = getDB();

    echo "<p style='color: green;'>✅ Database connection is working</p>";

    // Try a test insert
    $testCode = 'TEST-' . time();
    $testSql = "INSERT INTO daily_submissions (
                    submission_code, outlet_id, manager_id, submission_date,
                    berhad_sales, mp_coba_sales, mp_perdana_sales, market_sales,
                    total_income, total_expenses, net_amount, status
                ) VALUES (
                    :code, :outlet_id, :manager_id, :date,
                    100, 50, 50, 25,
                    225, 0, 225, 'draft'
                )";

    if (!empty($outlets)) {
        $stmt = $testPdo->prepare($testSql);
        $result = $stmt->execute([
            'code' => $testCode,
            'outlet_id' => $outlets[0]['id'],
            'manager_id' => $user['id'],
            'date' => date('Y-m-d')
        ]);

        if ($result) {
            $testId = $testPdo->lastInsertId();
            echo "<p style='color: green;'>✅ TEST INSERT SUCCESSFUL! (ID: {$testId})</p>";

            // Clean up test record
            $deleteStmt = $testPdo->prepare("DELETE FROM daily_submissions WHERE id = :id");
            $deleteStmt->execute(['id' => $testId]);
            echo "<p>Test record cleaned up.</p>";
        } else {
            echo "<p style='color: red;'>❌ TEST INSERT FAILED!</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Cannot test insert - no outlets available</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><a href='views/manager/submit_expenses.php'>Try submitting again →</a></p>";
echo "<p><a href='views/manager/dashboard.php'>Back to Dashboard</a></p>";
?>
