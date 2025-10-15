<?php
/**
 * Account - Approve Expenses
 * Approves uncategorized expenses for a submission
 */

require_once __DIR__ . '/../init.php';
requireRole('account');

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    if (!csrfValidatePost()) {
        throw new Exception('Security validation failed.');
    }

    $submissionId = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if (!$submissionId) {
        throw new Exception('Invalid submission ID.');
    }

    $user = getCurrentUser();

    // Verify submission exists and is pending
    $submission = dbFetchOne(
        "SELECT id, submission_code, status FROM daily_submissions WHERE id = :id",
        ['id' => $submissionId]
    );

    if (!$submission) {
        throw new Exception('Submission not found.');
    }

    if ($submission['status'] !== 'pending') {
        throw new Exception('Submission is not in pending status.');
    }

    // Update expenses to approved status
    $updatedCount = dbExecute(
        "UPDATE expenses e
         INNER JOIN expense_categories ec ON e.expense_category_id = ec.id
         SET e.approval_status = 'approved',
             e.approved_by = :account_id,
             e.approved_at = NOW()
         WHERE e.submission_id = :submission_id
           AND UPPER(ec.category_name) = 'UNCATEGORIZED'",
        [
            'account_id' => $user['id'],
            'submission_id' => $submissionId
        ]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Expenses approved successfully! (' . $updatedCount . ' expense record(s) updated)',
        'submission_code' => $submission['submission_code']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
