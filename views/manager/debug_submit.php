<?php
/**
 * Debug Submission - See exactly what happens
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/submission_handler.php';
requireRole('manager');

$user = getCurrentUser();

echo "<h1>🔍 Submission Debug</h1>";
echo "<hr>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>1️⃣ POST Data Received:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    echo "<h2>2️⃣ FILES Data Received:</h2>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";

    echo "<h2>3️⃣ Processing Submission...</h2>";

    try {
        $result = processSubmission($_POST, $_FILES, $user['id']);

        echo "<pre>";
        print_r($result);
        echo "</pre>";

        if ($result['success']) {
            echo "<h3 style='color: green;'>✅ SUCCESS!</h3>";
            echo "<p>Submission ID: {$result['submission_id']}</p>";
            echo "<p>Submission Code: {$result['submission_code']}</p>";

            echo "<h3>4️⃣ Check Database:</h3>";

            // Check if submission exists in database
            $check = dbFetchOne(
                "SELECT * FROM daily_submissions WHERE id = :id",
                ['id' => $result['submission_id']]
            );

            if ($check) {
                echo "<p style='color: green;'>✅ Submission found in database!</p>";
                echo "<pre>";
                print_r($check);
                echo "</pre>";

                // Check expenses
                $expenses = dbFetchAll(
                    "SELECT * FROM expenses WHERE submission_id = :id",
                    ['id' => $result['submission_id']]
                );

                echo "<h4>Expenses ({count($expenses)}):</h4>";
                echo "<pre>";
                print_r($expenses);
                echo "</pre>";

            } else {
                echo "<p style='color: red;'>❌ Submission NOT found in database!</p>";
            }

        } else {
            echo "<h3 style='color: red;'>❌ FAILED!</h3>";
            echo "<p>Error: {$result['message']}</p>";
        }

    } catch (Exception $e) {
        echo "<h3 style='color: red;'>❌ EXCEPTION!</h3>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>";
        echo $e->getTraceAsString();
        echo "</pre>";
    }

    echo "<hr>";
    echo "<h2>5️⃣ Check PHP Error Log:</h2>";
    $errorLog = __DIR__ . '/../../logs/error.log';
    if (file_exists($errorLog)) {
        $lines = file($errorLog);
        $lastLines = array_slice($lines, -20);
        echo "<pre>";
        echo implode('', $lastLines);
        echo "</pre>";
    } else {
        echo "<p>No error log found.</p>";
    }

} else {
    echo "<p>No POST data. Please submit the form from submit_expenses.php</p>";
    echo "<p><a href='submit_expenses.php'>Go to Submit Expenses</a></p>";
}
?>
