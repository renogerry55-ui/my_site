<?php
require_once __DIR__ . '/../../includes/init.php';
requireRole('manager');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>✅ FORM SUBMITTED!</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Submit</title>
</head>
<body>
    <h1>Simple Form Test</h1>

    <form method="POST" id="testForm">
        <input type="text" name="test_field" value="test value">
        <button type="submit" name="submit_test">Test Submit Button</button>
    </form>

    <script>
        document.getElementById('testForm').addEventListener('submit', function(e) {
            console.log('Form submit event fired!');
            alert('Form is submitting!');
            // Don't prevent - let it submit
        });
    </script>

    <hr>
    <p><a href="submit_expenses.php">Back to Submit Expenses</a></p>
</body>
</html>
