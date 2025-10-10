<?php
/**
 * Save Berhad External Sales Data (AJAX)
 */

require_once __DIR__ . '/../init.php';
requireRole('account');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Please use POST.',
        'csrf_token' => csrfGenerate(),
    ]);
    exit;
}

if (!csrfValidatePost()) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Your session token is invalid or expired. Please reload the page and try again.',
        'csrf_token' => csrfGenerate(),
    ]);
    exit;
}

$submissionId = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$rawData = isset($_POST['raw_data']) ? trim((string) $_POST['raw_data']) : '';
$structuredRowsInput = $_POST['structured_rows'] ?? '';

if ($submissionId <= 0) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'A valid submission identifier is required to save external sales data.',
        'csrf_token' => csrfGenerate(),
    ]);
    exit;
}

$tableExists = dbFetchOne("SHOW TABLES LIKE 'berhad_external_sales_data'");

if (!$tableExists) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'The external sales storage table is unavailable. Please run the latest database migrations and try again.',
        'csrf_token' => csrfGenerate(),
    ]);
    exit;
}

$submission = dbFetchOne(
    "SELECT id FROM daily_submissions WHERE id = :submission_id LIMIT 1",
    ['submission_id' => $submissionId]
);

if (!$submission) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'The referenced submission could not be found.',
        'csrf_token' => csrfGenerate(),
    ]);
    exit;
}

$currentUser = getCurrentUser();

if (!$currentUser) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to complete this action.',
        'csrf_token' => csrfGenerate(),
    ]);
    exit;
}

/**
 * Helper: convert value to lowercase safely
 */
function toLowerCaseValue($value)
{
    $value = (string) $value;

    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value, 'UTF-8');
    }

    return strtolower($value);
}

/**
 * Helper: trim and truncate strings to a maximum length
 */
function sanitizeStringValue($value, $maxLength)
{
    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    if ($maxLength > 0) {
        if (function_exists('mb_substr')) {
            $value = mb_substr($value, 0, $maxLength, 'UTF-8');
        } else {
            $value = substr($value, 0, $maxLength);
        }
    }

    return $value;
}

/**
 * Helper: detect delimiter similar to the client-side logic
 */
function detectDelimiterFromLines(array $lines)
{
    $delimiters = ["\t", ',', ';', '|'];
    $bestDelimiter = ',';
    $bestScore = 0;

    foreach ($delimiters as $delimiter) {
        $matches = 0;
        $totalColumns = 0;

        foreach ($lines as $line) {
            if (strpos($line, $delimiter) !== false) {
                $matches++;
                $totalColumns += count(str_getcsv($line, $delimiter));
            }
        }

        if ($matches) {
            $averageColumns = $totalColumns / $matches;
            if ($averageColumns > $bestScore) {
                $bestScore = $averageColumns;
                $bestDelimiter = $delimiter;
            }
        }
    }

    if ($bestScore === 0) {
        return ' ';
    }

    return $bestDelimiter;
}

/**
 * Helper: parse a line with the detected delimiter
 */
function parseLineByDelimiter($line, $delimiter)
{
    if ($delimiter === ' ') {
        $parts = preg_split('/\s+/', trim($line));
        return array_map('trim', $parts);
    }

    $cells = str_getcsv($line, $delimiter);
    return array_map('trim', $cells);
}

/**
 * Helper: normalize the structured rows into database-ready rows
 */
function buildNormalizedRows(array $rows)
{
    $expectedHeaders = [
        'agent',
        'outlet name',
        'level',
        'deposit',
        'count',
        'total deposit',
        'withdraw count',
        'total withdraw',
        'total',
    ];
    $expectedColumnCount = count($expectedHeaders);
    $normalizedRows = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $cells = array_slice($row, 0, $expectedColumnCount);
        if (count($cells) < $expectedColumnCount) {
            $cells = array_pad($cells, $expectedColumnCount, '');
        }

        $cells = array_map(function ($value) {
            return trim((string) $value);
        }, $cells);

        $lowercaseCells = array_map('toLowerCaseValue', $cells);

        if ($lowercaseCells === $expectedHeaders) {
            continue;
        }

        $hasValue = false;
        foreach ($cells as $cell) {
            if ($cell !== '') {
                $hasValue = true;
                break;
            }
        }

        if (!$hasValue) {
            continue;
        }

        $normalizedRows[] = [
            'agent_identifier' => sanitizeStringValue($cells[0], 100),
            'outlet_name' => sanitizeStringValue($cells[1], 255),
            'level' => sanitizeStringValue($cells[2], 100),
            'deposit' => sanitizeStringValue($cells[3], 100),
            'deposit_count' => sanitizeStringValue($cells[4], 100),
            'total_deposit' => sanitizeStringValue($cells[5], 100),
            'withdraw_count' => sanitizeStringValue($cells[6], 100),
            'total_withdraw' => sanitizeStringValue($cells[7], 100),
            'total' => sanitizeStringValue($cells[8], 100),
        ];
    }

    return $normalizedRows;
}

$structuredRows = [];

if ($structuredRowsInput !== '') {
    $decodedRows = json_decode($structuredRowsInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'The provided structured sales data is invalid. Please refresh and try again.',
            'csrf_token' => csrfGenerate(),
        ]);
        exit;
    }

    if (is_array($decodedRows)) {
        $structuredRows = $decodedRows;
    }
}

if (empty($structuredRows) && $rawData !== '') {
    $lines = preg_split('/\r?\n/', $rawData);
    $lines = array_map('trim', $lines);
    $lines = array_filter($lines, function ($line) {
        return $line !== '';
    });

    if (!empty($lines)) {
        $delimiter = detectDelimiterFromLines($lines);
        foreach ($lines as $line) {
            $structuredRows[] = parseLineByDelimiter($line, $delimiter);
        }
    }
}

$normalizedRows = buildNormalizedRows($structuredRows);

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    $deleteResult = dbQuery(
        'DELETE FROM berhad_external_sales_data WHERE submission_id = :submission_id',
        ['submission_id' => $submissionId]
    );

    if ($deleteResult === false) {
        throw new RuntimeException('Failed to clear existing external sales data.');
    }

    if (!empty($normalizedRows)) {
        foreach ($normalizedRows as $index => $row) {
            $params = [
                'submission_id' => $submissionId,
                'row_index' => $index,
                'agent_identifier' => $row['agent_identifier'],
                'outlet_name' => $row['outlet_name'],
                'level' => $row['level'],
                'deposit' => $row['deposit'],
                'deposit_count' => $row['deposit_count'],
                'total_deposit' => $row['total_deposit'],
                'withdraw_count' => $row['withdraw_count'],
                'total_withdraw' => $row['total_withdraw'],
                'total' => $row['total'],
                'saved_by' => $currentUser['id'] ?? null,
            ];

            $insertResult = dbQuery(
                'INSERT INTO berhad_external_sales_data (
                    submission_id,
                    row_index,
                    agent_identifier,
                    outlet_name,
                    level,
                    deposit,
                    deposit_count,
                    total_deposit,
                    withdraw_count,
                    total_withdraw,
                    total,
                    saved_by
                ) VALUES (
                    :submission_id,
                    :row_index,
                    :agent_identifier,
                    :outlet_name,
                    :level,
                    :deposit,
                    :deposit_count,
                    :total_deposit,
                    :withdraw_count,
                    :total_withdraw,
                    :total,
                    :saved_by
                )',
                $params
            );

            if ($insertResult === false) {
                throw new RuntimeException('Failed to store the provided external sales data.');
            }
        }
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Failed to persist Berhad external sales data: ' . $exception->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save the external sales data. Please try again later.',
        'csrf_token' => csrfGenerate(),
    ]);
    exit;
}

$rowCount = count($normalizedRows);

if ($rowCount === 0) {
    $message = 'External sales data cleared for this submission.';
} elseif ($rowCount === 1) {
    $message = '1 row of external sales data saved successfully.';
} else {
    $message = $rowCount . ' rows of external sales data saved successfully.';
}

$displayDate = date('F j, Y g:i A');

$response = [
    'success' => true,
    'message' => $message,
    'saved_at_display' => $displayDate,
    'saved_by' => $currentUser['name'] ?? '',
    'csrf_token' => csrfGenerate(),
];

echo json_encode($response);
exit;
