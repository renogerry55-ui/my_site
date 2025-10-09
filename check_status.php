<?php
/**
 * Debug Script - Check Database Status
 */

require_once __DIR__ . '/includes/init.php';
requireRole('manager');

$user = getCurrentUser();

// Check table structure
$pdo = getDB();
$stmt = $pdo->query("SHOW COLUMNS FROM daily_submissions LIKE 'status'");
$statusColumn = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Database Structure Check</h2>";
echo "<pre>";
echo "Status Column Type: " . $statusColumn['Type'] . "\n";
echo "Default: " . $statusColumn['Default'] . "\n";
echo "</pre>";

// Check if batch_code column exists
$stmt = $pdo->query("SHOW COLUMNS FROM daily_submissions LIKE 'batch_code'");
$batchColumn = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<h3>Batch Code Column:</h3>";
echo "<pre>";
if ($batchColumn) {
    echo "EXISTS - Type: " . $batchColumn['Type'] . "\n";
} else {
    echo "DOES NOT EXIST!\n";
}
echo "</pre>";

// Check if submitted_to_hq_at column exists
$stmt = $pdo->query("SHOW COLUMNS FROM daily_submissions LIKE 'submitted_to_hq_at'");
$submittedColumn = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<h3>Submitted to HQ Column:</h3>";
echo "<pre>";
if ($submittedColumn) {
    echo "EXISTS - Type: " . $submittedColumn['Type'] . "\n";
} else {
    echo "DOES NOT EXIST!\n";
}
echo "</pre>";

// Check submissions for this manager
$submissions = dbFetchAll(
    "SELECT id, submission_code, outlet_id, submission_date, status, created_at
     FROM daily_submissions
     WHERE manager_id = :manager_id
     ORDER BY created_at DESC
     LIMIT 10",
    ['manager_id' => $user['id']]
);

echo "<h2>Your Submissions (Last 10)</h2>";
if (empty($submissions)) {
    echo "<p style='color: red;'>NO SUBMISSIONS FOUND!</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Code</th><th>Outlet ID</th><th>Date</th><th>Status</th><th>Created At</th></tr>";
    foreach ($submissions as $sub) {
        echo "<tr>";
        echo "<td>{$sub['id']}</td>";
        echo "<td>{$sub['submission_code']}</td>";
        echo "<td>{$sub['outlet_id']}</td>";
        echo "<td>{$sub['submission_date']}</td>";
        echo "<td><strong style='color: blue;'>{$sub['status']}</strong></td>";
        echo "<td>{$sub['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test query that submit_to_hq.php uses
$selectedDate = date('Y-m-d');
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

echo "<h2>Submit to HQ Query Test (Date: {$selectedDate})</h2>";
echo "<pre>";
echo "Looking for submissions with:\n";
echo "- Manager ID: {$user['id']}\n";
echo "- Date: {$selectedDate}\n";
echo "- Status: 'draft'\n\n";

if (empty($draftSubmissions)) {
    echo "<strong style='color: red;'>NO DRAFT SUBMISSIONS FOUND FOR TODAY!</strong>\n\n";

    // Check if there are ANY submissions for this date regardless of status
    $anySubmissions = dbFetchAll("
        SELECT ds.*, o.outlet_name
        FROM daily_submissions ds
        INNER JOIN outlets o ON ds.outlet_id = o.id
        WHERE ds.manager_id = :manager_id
        AND ds.submission_date = :date
    ", ['manager_id' => $user['id'], 'date' => $selectedDate]);

    if (!empty($anySubmissions)) {
        echo "BUT found submissions for today with OTHER statuses:\n";
        foreach ($anySubmissions as $s) {
            echo "- {$s['outlet_name']}: Status = '{$s['status']}'\n";
        }
    } else {
        echo "No submissions found for today at all.\n";
    }
} else {
    echo "<strong style='color: green;'>FOUND " . count($draftSubmissions) . " DRAFT SUBMISSIONS!</strong>\n\n";
    foreach ($draftSubmissions as $draft) {
        echo "- {$draft['outlet_name']} ({$draft['submission_code']})\n";
    }
}
echo "</pre>";
?>

<a href="views/manager/dashboard.php" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;">Back to Dashboard</a>
