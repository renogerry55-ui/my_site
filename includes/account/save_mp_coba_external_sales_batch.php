<?php
/**
 * Save MP COBA External Sales Data (Batch)
 * Handles batch upload of external sales data for multiple outlets under a manager
 */

require_once __DIR__ . '/../init.php';
requireRole('account');

header('Content-Type: application/json; charset=UTF-8');

$user = getCurrentUser();
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'csrf_token' => csrfGenerate()
];

try {
    // Validate CSRF
    if (!csrfValidatePost()) {
        throw new Exception('Security validation failed. Please refresh the page and try again.');
    }

    // Get POST data
    $managerId = filter_input(INPUT_POST, 'manager_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $structuredData = $_POST['structured_data'] ?? '';

    if (!$managerId) {
        throw new Exception('Missing or invalid manager identifier.');
    }

    if (empty($structuredData)) {
        throw new Exception('No external sales data provided.');
    }

    // Parse structured data (JSON array of rows)
    $parsedData = json_decode($structuredData, true);
    if (!is_array($parsedData) || empty($parsedData)) {
        throw new Exception('Invalid external sales data format.');
    }

    // Get all pending MP COBA submissions for this manager
    $submissions = dbFetchAll(
        "SELECT
            ds.id,
            ds.outlet_id,
            ds.mp_coba_sales,
            o.outlet_name,
            o.outlet_code
        FROM daily_submissions ds
        INNER JOIN outlets o ON ds.outlet_id = o.id
        WHERE ds.manager_id = :manager_id
          AND ds.status = 'pending'
          AND ds.mp_coba_sales > 0
        ORDER BY o.outlet_name ASC",
        ['manager_id' => $managerId]
    );

    if (empty($submissions)) {
        throw new Exception('No pending MP COBA submissions found for this manager.');
    }

    // Build mapping of outlet name to external data
    // Column indices: [0] Agent, [1] Outlet Name, [2] Level, [3] Deposit Count, [4] Total Deposit, [5] Withdraw Count, [6] Total Withdraw, [7] Total
    $externalDataMap = [];
    foreach ($parsedData as $rowIndex => $row) {
        if (!is_array($row) || count($row) < 8) {
            continue; // Skip invalid rows
        }

        $outletName = trim((string) ($row[1] ?? ''));
        if (empty($outletName)) {
            continue; // Skip rows without outlet name
        }

        // Use lowercase for case-insensitive matching
        $outletNameKey = mb_strtolower($outletName);

        // Store the row data mapped by outlet name
        if (!isset($externalDataMap[$outletNameKey])) {
            $externalDataMap[$outletNameKey] = [];
        }

        $externalDataMap[$outletNameKey][] = [
            'row_index' => $rowIndex,
            'agent_identifier' => trim((string) ($row[0] ?? '')),
            'name' => $outletName,
            'level' => trim((string) ($row[2] ?? '')),
            'deposit_count' => trim((string) ($row[3] ?? '')),
            'total_deposit' => trim((string) ($row[4] ?? '')),
            'withdraw_count' => trim((string) ($row[5] ?? '')),
            'total_withdraw' => trim((string) ($row[6] ?? '')),
            'total' => trim((string) ($row[7] ?? '')),
            'total_deposit_num' => parseAmount(trim((string) ($row[4] ?? ''))),
            'total_withdraw_num' => parseAmount(trim((string) ($row[6] ?? '')))
        ];
    }

    // Start transaction
    $pdo = getDB();
    $pdo->beginTransaction();

    $savedCount = 0;
    $comparisonResults = [];

    foreach ($submissions as $submission) {
        $submissionId = (int) $submission['id'];
        $outletCode = $submission['outlet_code'];
        $outletName = $submission['outlet_name'];
        $outletNameKey = mb_strtolower(trim($outletName));
        $claimedMpCobaSales = (float) $submission['mp_coba_sales'];

        // Delete existing external data for this submission
        dbQuery(
            "DELETE FROM mp_coba_external_sales_data WHERE submission_id = :id",
            ['id' => $submissionId]
        );

        // Find matching external data by outlet name
        $matchingRows = $externalDataMap[$outletNameKey] ?? [];
        $externalSalesTotal = 0.0;
        $externalExpensesTotal = 0.0;

        // Get manager's submitted expenses for this submission
        // Query expenses where category_name = 'MP COBA'
        $expenseData = dbFetchOne(
            "SELECT COALESCE(SUM(e.amount), 0) as total_expenses
            FROM expenses e
            INNER JOIN expense_categories ec ON e.expense_category_id = ec.id
            WHERE e.submission_id = :submission_id
              AND UPPER(ec.category_name) = 'MP COBA'",
            ['submission_id' => $submissionId]
        );
        $submittedExpenses = (float) ($expenseData['total_expenses'] ?? 0.0);

        if (empty($matchingRows)) {
            // No matching external data found for this outlet
            $comparisonResults[] = [
                'submission_id' => $submissionId,
                'outlet_name' => $outletName,
                'outlet_code' => $outletCode,
                'submitted_sales' => $claimedMpCobaSales,
                'external_sales' => 0,
                'sales_difference' => -$claimedMpCobaSales,
                'sales_matches' => false,
                'submitted_expenses' => $submittedExpenses,
                'external_expenses' => 0,
                'expenses_difference' => -$submittedExpenses,
                'expenses_matches' => false,
                'matches' => false,
                'not_found' => true,
                'row_count' => 0
            ];
            continue; // Skip to next submission
        }

        // Insert external data rows for this outlet
        foreach ($matchingRows as $rowData) {
            $externalSalesTotal += $rowData['total_deposit_num'];
            $externalExpensesTotal += $rowData['total_withdraw_num'];

            dbQuery(
                "INSERT INTO mp_coba_external_sales_data (
                    submission_id, row_index, agent_identifier, name, level,
                    deposit_count, total_deposit, withdraw_count, total_withdraw,
                    total, saved_by, created_at
                ) VALUES (
                    :submission_id, :row_index, :agent_identifier, :name, :level,
                    :deposit_count, :total_deposit, :withdraw_count, :total_withdraw,
                    :total, :saved_by, NOW()
                )",
                [
                    'submission_id' => $submissionId,
                    'row_index' => $rowData['row_index'],
                    'agent_identifier' => $rowData['agent_identifier'],
                    'name' => $rowData['name'],
                    'level' => $rowData['level'],
                    'deposit_count' => $rowData['deposit_count'],
                    'total_deposit' => $rowData['total_deposit'],
                    'withdraw_count' => $rowData['withdraw_count'],
                    'total_withdraw' => $rowData['total_withdraw'],
                    'total' => $rowData['total'],
                    'saved_by' => $user['id']
                ]
            );
        }

        $savedCount++;

        // Calculate comparisons for both sales and expenses
        $salesDifference = $externalSalesTotal - $claimedMpCobaSales;
        $expensesDifference = $externalExpensesTotal - $submittedExpenses;
        $salesMatches = abs($salesDifference) <= 0.01; // Tolerance of 1 cent
        $expensesMatches = abs($expensesDifference) <= 0.01;
        $matches = $salesMatches && $expensesMatches; // Both must match

        $comparisonResults[] = [
            'submission_id' => $submissionId,
            'outlet_name' => $outletName,
            'outlet_code' => $outletCode,
            'submitted_sales' => $claimedMpCobaSales,
            'external_sales' => $externalSalesTotal,
            'sales_difference' => $salesDifference,
            'sales_matches' => $salesMatches,
            'submitted_expenses' => $submittedExpenses,
            'external_expenses' => $externalExpensesTotal,
            'expenses_difference' => $expensesDifference,
            'expenses_matches' => $expensesMatches,
            'matches' => $matches,
            'not_found' => false,
            'row_count' => count($matchingRows)
        ];
    }

    // Commit transaction
    $pdo->commit();

    $response['success'] = true;
    $response['message'] = "External sales data saved successfully for {$savedCount} outlet(s).";
    $response['data'] = [
        'saved_count' => $savedCount,
        'comparisons' => $comparisonResults,
        'saved_at_display' => date('F j, Y g:i A'),
        'saved_by' => $user['name']
    ];

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $response['message'] = $e->getMessage();
    error_log('MP COBA batch external sales save error: ' . $e->getMessage());
}

echo json_encode($response);

/**
 * Parse amount from string (handles currency symbols, commas, etc.)
 */
function parseAmount($value) {
    if ($value == null) {
        return 0.0;
    }

    $stringValue = trim((string) $value);
    if (empty($stringValue)) {
        return 0.0;
    }

    // Remove currency symbols and spaces
    $normalized = preg_replace('/[^0-9,.-]/', '', $stringValue);

    if (empty($normalized) || $normalized === '-' || $normalized === '.' || $normalized === ',') {
        return 0.0;
    }

    // Handle comma as decimal separator (European format) vs thousands separator
    $commaCount = substr_count($normalized, ',');
    $dotCount = substr_count($normalized, '.');

    if ($commaCount && $dotCount) {
        // Has both - assume comma is thousands separator
        $normalized = str_replace(',', '', $normalized);
    } elseif ($commaCount && !$dotCount) {
        // Only comma - could be decimal separator
        // Check if comma is in last 3 positions (likely decimal)
        $commaPos = strrpos($normalized, ',');
        if (strlen($normalized) - $commaPos <= 3) {
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '', $normalized);
        }
    }

    $parsed = floatval($normalized);

    return is_finite($parsed) ? $parsed : 0.0;
}
?>
