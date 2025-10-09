<?php
/**
 * Check for Duplicate Submission (AJAX)
 */

require_once __DIR__ . '/init.php';
requireRole('manager');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $outletId = intval($_POST['outlet_id'] ?? 0);
    $submissionDate = $_POST['submission_date'] ?? '';
    $user = getCurrentUser();

    if ($outletId <= 0 || empty($submissionDate)) {
        echo json_encode(['exists' => false]);
        exit;
    }

    // Check if submission exists for this outlet and date
    $existing = dbFetchOne(
        "SELECT id, status, submission_code
         FROM daily_submissions
         WHERE outlet_id = :outlet_id
         AND submission_date = :date
         AND manager_id = :manager_id",
        [
            'outlet_id' => $outletId,
            'date' => $submissionDate,
            'manager_id' => $user['id']
        ]
    );

    if ($existing) {
        echo json_encode([
            'exists' => true,
            'submission_id' => $existing['id'],
            'submission_code' => $existing['submission_code'],
            'status' => $existing['status']
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>
