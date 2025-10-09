<?php
/**
 * Simple Test Submission (No JavaScript Validation)
 */

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/submission_handler.php';
requireRole('manager');

$user = getCurrentUser();

echo "<h1>Simple Submission Test</h1>";
echo "<p>This is a simplified form with no JavaScript validation to test if PHP submission works.</p>";
echo "<hr>";

// Fetch manager's outlets
$outlets = dbFetchAll(
    "SELECT * FROM outlets WHERE manager_id = :manager_id AND status = 'active' ORDER BY outlet_name",
    ['manager_id' => $user['id']]
);

// Fetch expense categories
$mpBerhadCategories = dbFetchAll(
    "SELECT * FROM expense_categories WHERE category_type = 'mp_berhad' AND status = 'active' ORDER BY category_name"
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: #e7f3ff; padding: 15px; margin: 20px 0; border: 2px solid #004085;'>";
    echo "<h3>POST Data Received!</h3>";
    echo "<pre>";
    echo "POST Variables:\n";
    print_r($_POST);
    echo "\nFILES Variables:\n";
    print_r($_FILES);
    echo "</pre>";
    echo "</div>";

    if (isset($_POST['submit_test'])) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border: 2px solid #155724;'>";
        echo "<h3>Calling processSubmission()...</h3>";

        try {
            $result = processSubmission($_POST, $_FILES, $user['id']);

            echo "<pre>";
            print_r($result);
            echo "</pre>";

            if ($result['success']) {
                echo "<p style='color: green; font-size: 20px;'><strong>✅ SUCCESS!</strong></p>";
                echo "<p>Submission Code: {$result['submission_code']}</p>";
                echo "<p><a href='views/manager/view_history.php'>View History</a></p>";
            } else {
                echo "<p style='color: red; font-size: 20px;'><strong>❌ FAILED!</strong></p>";
                echo "<p>Error: {$result['message']}</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>EXCEPTION: " . $e->getMessage() . "</p>";
        }

        echo "</div>";
    }
}

?>

<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
    input, select { padding: 8px; margin: 5px 0; width: 100%; max-width: 400px; }
    button { padding: 12px 30px; background: #28a745; color: white; border: none; font-size: 16px; cursor: pointer; margin: 20px 0; }
    button:hover { background: #218838; }
    label { display: block; margin-top: 15px; font-weight: bold; }
</style>

<form method="POST" enctype="multipart/form-data">
    <?php echo csrfField(); ?>

    <h2>Basic Info</h2>

    <label>Outlet:</label>
    <select name="outlet_id" required>
        <option value="">-- Select Outlet --</option>
        <?php foreach ($outlets as $outlet): ?>
            <option value="<?php echo $outlet['id']; ?>">
                <?php echo htmlspecialchars($outlet['outlet_name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Date:</label>
    <input type="date" name="submission_date" value="<?php echo date('Y-m-d'); ?>" required>

    <h2>Income (RM)</h2>

    <label>Berhad Sales:</label>
    <input type="number" name="berhad_sales" step="0.01" value="100.00" required>

    <label>MP Coba Sales:</label>
    <input type="number" name="mp_coba_sales" step="0.01" value="50.00" required>

    <label>MP Perdana Sales:</label>
    <input type="number" name="mp_perdana_sales" step="0.01" value="50.00" required>

    <label>Market Sales:</label>
    <input type="number" name="market_sales" step="0.01" value="25.00" required>

    <h2>Expense #1 (MP/BERHAD)</h2>

    <label>Category:</label>
    <select name="expenses[mp_berhad][0][category_id]" required>
        <option value="">-- Select Category --</option>
        <?php foreach ($mpBerhadCategories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>">
                <?php echo htmlspecialchars($cat['category_name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Amount (RM):</label>
    <input type="number" name="expenses[mp_berhad][0][amount]" step="0.01" value="30.00" required>

    <label>Description (Optional):</label>
    <input type="text" name="expenses[mp_berhad][0][description]" placeholder="Test expense">

    <label>Receipt/Voucher File (JPG, PNG, PDF):</label>
    <input type="file" name="expenses[mp_berhad][0][receipt]" accept="image/*,.pdf" required>
    <p style="color: #666; font-size: 12px;">You must upload a file (any JPG, PNG, or PDF)</p>

    <label>Notes (Optional):</label>
    <input type="text" name="notes" placeholder="Test submission">

    <button type="submit" name="submit_test">Submit Test</button>
</form>

<hr>
<p><a href="views/manager/dashboard.php">Back to Dashboard</a></p>
