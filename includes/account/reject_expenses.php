<?php
/**
 * Account - Reject Expenses / Request Resubmit
 * Rejects uncategorized expenses and sends submission back to manager
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
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    if (!$submissionId) {
        throw new Exception('Invalid submission ID.');
    }

    if (empty($reason)) {
        throw new Exception('Rejection reason is required.');
    }

    $user = getCurrentUser();
    $pdo = getDB();
    $pdo->beginTransaction();

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

    // Update expenses to rejected status
    dbExecute(
        "UPDATE expenses e
         INNER JOIN expense_categories ec ON e.expense_category_id = ec.id
         SET e.approval_status = 'rejected',
             e.approved_by = :account_id,
             e.rejection_reason = :reason,
             e.approved_at = NOW()
         WHERE e.submission_id = :submission_id
           AND UPPER(ec.category_name) = 'UNCATEGORIZED'",
        [
            'account_id' => $user['id'],
            'reason' => $reason,
            'submission_id' => $submissionId
        ]
    );

    // Update submission status to resubmit and add accountant notes
    dbExecute(
        "UPDATE daily_submissions
         SET status = 'resubmit',
             accountant_notes = CONCAT(COALESCE(accountant_notes, ''), :note),
             returned_to_manager_at = NOW()
         WHERE id = :id",
        [
            'note' => "\n[" . date('Y-m-d H:i:s') . "] Expenses rejected by " . $user['name'] . ": " . $reason,
            'id' => $submissionId
        ]
    );

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Expenses rejected. Submission sent back to manager for resubmission.',
        'submission_code' => $submission['submission_code']
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
