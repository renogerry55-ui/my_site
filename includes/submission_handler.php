<?php
/**
 * Submission Handler
 * Processes daily submission with file uploads
 */

// Prevent direct access
if (!defined('APP_INIT')) {
    die('Direct access not permitted');
}

/**
 * Process daily submission
 * @param array $postData Form data
 * @param array $filesData Files data
 * @param int $managerId Manager user ID
 * @return array ['success' => bool, 'message' => string, 'submission_id' => int|null]
 */
function processSubmission($postData, $filesData, $managerId) {
    try {
        $pdo = getDB();
        $pdo->beginTransaction();

        // Validate required fields
        if (empty($postData['outlet_id']) || empty($postData['submission_date'])) {
            throw new Exception('Outlet and submission date are required.');
        }

        $outletId = intval($postData['outlet_id']);
        $submissionDate = $postData['submission_date'];

        // Verify outlet belongs to this manager
        $outlet = dbFetchOne(
            "SELECT * FROM outlets WHERE id = :id AND manager_id = :manager_id AND status = 'active'",
            ['id' => $outletId, 'manager_id' => $managerId]
        );

        if (!$outlet) {
            throw new Exception('Invalid outlet selected.');
        }

        // Check for duplicate submission (same outlet, same date)
        $existing = dbFetchOne(
            "SELECT id FROM daily_submissions WHERE outlet_id = :outlet_id AND submission_date = :date",
            ['outlet_id' => $outletId, 'date' => $submissionDate]
        );

        if ($existing) {
            throw new Exception('A submission for this outlet and date already exists.');
        }

        // Generate submission code
        $submissionCode = generateSubmissionCode($outletId, $submissionDate);

        // Get income values
        $berhadSales = floatval($postData['berhad_sales'] ?? 0);
        $mpCobaSales = floatval($postData['mp_coba_sales'] ?? 0);
        $mpPerdanaSales = floatval($postData['mp_perdana_sales'] ?? 0);
        $marketSales = floatval($postData['market_sales'] ?? 0);

        // Calculate total income
        $totalIncome = $berhadSales + $mpCobaSales + $mpPerdanaSales + $marketSales;

        // Prepare notes
        $notes = isset($postData['notes']) ? trim($postData['notes']) : null;

        // Insert daily submission (as draft first, will be submitted to HQ later)
        $sql = "INSERT INTO daily_submissions (
                    submission_code, outlet_id, manager_id, submission_date,
                    berhad_sales, mp_coba_sales, mp_perdana_sales, market_sales,
                    total_income, total_expenses, net_amount, status, notes
                ) VALUES (
                    :submission_code, :outlet_id, :manager_id, :submission_date,
                    :berhad_sales, :mp_coba_sales, :mp_perdana_sales, :market_sales,
                    :total_income, 0, :net_amount, 'draft', :notes
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'submission_code' => $submissionCode,
            'outlet_id' => $outletId,
            'manager_id' => $managerId,
            'submission_date' => $submissionDate,
            'berhad_sales' => $berhadSales,
            'mp_coba_sales' => $mpCobaSales,
            'mp_perdana_sales' => $mpPerdanaSales,
            'market_sales' => $marketSales,
            'total_income' => $totalIncome,
            'net_amount' => $totalIncome, // Will be updated after expenses
            'notes' => $notes
        ]);

        $submissionId = $pdo->lastInsertId();

        // Process expenses
        $totalExpenses = 0;
        $expenseTypes = ['mp_berhad', 'market'];

        foreach ($expenseTypes as $type) {
            if (isset($postData['expenses'][$type]) && is_array($postData['expenses'][$type])) {
                foreach ($postData['expenses'][$type] as $index => $expense) {
                    $categoryId = intval($expense['category_id'] ?? 0);
                    $amount = floatval($expense['amount'] ?? 0);
                    $description = isset($expense['description']) ? trim($expense['description']) : null;

                    if ($categoryId <= 0 || $amount <= 0) {
                        continue; // Skip invalid expenses
                    }

                    // Handle file upload - PHP structures nested file arrays differently
                    $receiptFile = null;

                    // Check if file was uploaded for this expense
                    if (isset($filesData['expenses']) &&
                        isset($filesData['expenses']['name'][$type][$index]['receipt']) &&
                        !empty($filesData['expenses']['name'][$type][$index]['receipt'])) {

                        $uploadResult = handleReceiptUpload(
                            $filesData['expenses']['name'][$type][$index]['receipt'],
                            $filesData['expenses']['tmp_name'][$type][$index]['receipt'],
                            $filesData['expenses']['size'][$type][$index]['receipt'],
                            $filesData['expenses']['error'][$type][$index]['receipt'],
                            $submissionCode
                        );

                        if (!$uploadResult['success']) {
                            throw new Exception("File upload failed for {$type} expense #{$index}: " . $uploadResult['message']);
                        }

                        $receiptFile = $uploadResult['filename'];
                    } else {
                        // No file uploaded - this is required
                        throw new Exception("Receipt/voucher is required for all expenses. Missing for {$type} expense #{$index}.");
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

        // Update submission with total expenses and net amount
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

        // Commit transaction
        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Submission created successfully!',
            'submission_id' => $submissionId,
            'submission_code' => $submissionCode
        ];

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Submission processing error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'submission_id' => null
        ];
    }
}

/**
 * Generate unique submission code
 * @param int $outletId
 * @param string $date
 * @return string
 */
function generateSubmissionCode($outletId, $date) {
    $datePart = date('Ymd', strtotime($date));
    $outletPart = str_pad($outletId, 3, '0', STR_PAD_LEFT);
    $randomPart = strtoupper(substr(md5(uniqid()), 0, 4));
    return "SUB-{$datePart}-{$outletPart}-{$randomPart}";
}

/**
 * Handle receipt file upload
 * @param string $filename Original filename
 * @param string $tmpName Temporary file path
 * @param int $fileSize File size in bytes
 * @param int $error Upload error code
 * @param string $submissionCode Submission code for folder organization
 * @return array ['success' => bool, 'message' => string, 'filename' => string|null]
 */
function handleReceiptUpload($filename, $tmpName, $fileSize, $error, $submissionCode) {
    // Check for upload errors
    if ($error !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'File upload failed. Please try again.',
            'filename' => null
        ];
    }

    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($fileSize > $maxSize) {
        return [
            'success' => false,
            'message' => 'File size exceeds 5MB limit.',
            'filename' => null
        ];
    }

    // Get file extension
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];

    if (!in_array($extension, $allowedExtensions)) {
        return [
            'success' => false,
            'message' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.',
            'filename' => null
        ];
    }

    // Validate file type (MIME)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpName);
    finfo_close($finfo);

    $allowedMimes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'application/pdf'
    ];

    if (!in_array($mimeType, $allowedMimes)) {
        return [
            'success' => false,
            'message' => 'Invalid file content.',
            'filename' => null
        ];
    }

    // Generate unique filename
    $newFilename = $submissionCode . '_' . uniqid() . '.' . $extension;

    // Upload directory
    $uploadDir = __DIR__ . '/../uploads/receipts/';

    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            return [
                'success' => false,
                'message' => 'Failed to create upload directory.',
                'filename' => null
            ];
        }
    }

    // Check if directory is writable
    if (!is_writable($uploadDir)) {
        // Try to make it writable
        chmod($uploadDir, 0777);
        if (!is_writable($uploadDir)) {
            return [
                'success' => false,
                'message' => 'Upload directory is not writable.',
                'filename' => null
            ];
        }
    }

    $uploadPath = $uploadDir . $newFilename;

    // Move uploaded file
    if (move_uploaded_file($tmpName, $uploadPath)) {
        // Set file permissions
        chmod($uploadPath, 0644);

        return [
            'success' => true,
            'message' => 'File uploaded successfully.',
            'filename' => $newFilename
        ];
    } else {
        error_log("Failed to move uploaded file from {$tmpName} to {$uploadPath}");
        return [
            'success' => false,
            'message' => 'Failed to save file. Please check server permissions.',
            'filename' => null
        ];
    }
}

?>
