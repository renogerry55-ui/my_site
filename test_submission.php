<?php
/**
 * Test Submission Debug
 */

require_once __DIR__ . '/includes/init.php';
requireRole('manager');

$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Data:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    echo "<h2>FILES Data:</h2>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";

    echo "<h2>Processing Submission:</h2>";
    require_once __DIR__ . '/includes/submission_handler.php';
    $result = processSubmission($_POST, $_FILES, $user['id']);

    echo "<pre>";
    print_r($result);
    echo "</pre>";

    if ($result['success']) {
        echo "<p style='color: green; font-weight: bold;'>SUCCESS!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>FAILED: " . htmlspecialchars($result['message']) . "</p>";
    }
} else {
    echo "<p>POST a form to see debug output</p>";
    echo "<p><a href='views/manager/submit_expenses.php'>Go to Submit Expenses</a></p>";
}
?>
