<?php
/**
 * Update Submission Handler
 * Updates existing DRAFT submission with new data
 */

// Prevent direct access
if (!defined('APP_INIT')) {
    die('Direct access not permitted');
}

/**
 * Update existing submission
 * @param int $submissionId Submission ID to update
 * @param array $postData Form data
 * @param array $filesData Files data
 * @param int $managerId Manager user ID
 * @return array ['success' => bool, 'message' => string]
 */
function updateSubmission($submissionId, $postData, $filesData, $managerId) {
    try {
        $pdo = getDB();
        $pdo->beginTransaction();

        // Verify submission exists and belongs to manager
        $existing = dbFetchOne(
            "SELECT * FROM daily_submissions
             WHERE id = :id AND manager_id = :manager_id AND status = 'draft'",
            ['id' => $submissionId, 'manager_id' => $managerId]
        );

        if (!$existing) {
            throw new Exception('Submission not found or cannot be edited.');
        }

        // Get income values
        $berhadSales = floatval($postData['berhad_sales'] ?? 0);
        $mpCobaSales = floatval($postData['mp_coba_sales'] ?? 0);
        $mpPerdanaSales = floatval($postData['mp_perdana_sales'] ?? 0);
        $marketSales = floatval($postData['market_sales'] ?? 0);

        // Calculate total income
        $totalIncome = $berhadSales + $mpCobaSales + $mpPerdanaSales + $marketSales;

        // Prepare notes
        $notes = isset($postData['notes']) ? trim($postData['notes']) : null;

        // Update submission basic info
        $sql = "UPDATE daily_submissions SET
                    berhad_sales = :berhad_sales,
                    mp_coba_sales = :mp_coba_sales,
                    mp_perdana_sales = :mp_perdana_sales,
                    market_sales = :market_sales,
                    total_income = :total_income,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'berhad_sales' => $berhadSales,
            'mp_coba_sales' => $mpCobaSales,
            'mp_perdana_sales' => $mpPerdanaSales,
            'market_sales' => $marketSales,
            'total_income' => $totalIncome,
            'notes' => $notes,
            'id' => $submissionId
        ]);

        // Delete old expenses
        $pdo->exec("DELETE FROM expenses WHERE submission_id = {$submissionId}");

        // Process new expenses (same logic as create)
        $totalExpenses = 0;
        $expenseTypes = ['mp_berhad', 'market'];

        foreach ($expenseTypes as $type) {
            if (isset($postData['expenses'][$type]) && is_array($postData['expenses'][$type])) {
                foreach ($postData['expenses'][$type] as $index => $expense) {
                    $categoryId = intval($expense['category_id'] ?? 0);
                    $amount = floatval($expense['amount'] ?? 0);
                    $description = isset($expense['description']) ? trim($expense['description']) : null;

                    if ($categoryId <= 0 || $amount <= 0) {
                        continue;
                    }

                    $receiptFile = null;

                    // Check if new file was uploaded
                    if (isset($filesData['expenses']) &&
                        isset($filesData['expenses']['name'][$type][$index]['receipt']) &&
                        !empty($filesData['expenses']['name'][$type][$index]['receipt'])) {

                        $uploadResult = handleReceiptUpload(
                            $filesData['expenses']['name'][$type][$index]['receipt'],
                            $filesData['expenses']['tmp_name'][$type][$index]['receipt'],
                            $filesData['expenses']['size'][$type][$index]['receipt'],
                            $filesData['expenses']['error'][$type][$index]['receipt'],
                            $existing['submission_code']
                        );

                        if (!$uploadResult['success']) {
                            throw new Exception("File upload failed for {$type} expense #{$index}: " . $uploadResult['message']);
                        }

                        $receiptFile = $uploadResult['filename'];
                    } else {
                        // Check if keeping existing file
                        if (isset($expense['keep_receipt']) && !empty($expense['keep_receipt'])) {
                            $receiptFile = $expense['keep_receipt'];
                        } else {
                            throw new Exception("Receipt/voucher is required for all expenses. Missing for {$type} expense #{$index}.");
                        }
                    }

                    // Insert expense
                    $expenseSql = "INSERT INTO expenses (
                                    submission_id, expense_category_id, amount,
                                    description, receipt_file
                                   ) VALUES (
                                    :submission_id, :category_id, :amount,
                                    :description, :receipt_file
                                   )";

                    $expenseStmt = $pdo->prepare($expenseSql);
                    $expenseStmt->execute([
                        'submission_id' => $submissionId,
                        'category_id' => $categoryId,
                        'amount' => $amount,
                        'description' => $description,
                        'receipt_file' => $receiptFile
                    ]);

                    $totalExpenses += $amount;
                }
            }
        }

        // Update submission totals
        $netAmount = $totalIncome - $totalExpenses;
        $updateSql = "UPDATE daily_submissions
                      SET total_expenses = :total_expenses, net_amount = :net_amount
                      WHERE id = :id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            'total_expenses' => $totalExpenses,
            'net_amount' => $netAmount,
            'id' => $submissionId
        ]);

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Submission updated successfully!',
            'submission_id' => $submissionId,
            'submission_code' => $existing['submission_code']
        ];

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Update submission error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
?>
