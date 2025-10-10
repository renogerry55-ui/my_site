<?php
/**
 * Account - Berhad Sales Verification Process
 * Dedicated workflow page for reviewing an individual outlet submission.
 */

require_once __DIR__ . '/../../includes/init.php';
requireRole('account');

$managerId = filter_input(INPUT_GET, 'manager_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$outletId = filter_input(INPUT_GET, 'outlet_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$submissionId = filter_input(INPUT_GET, 'submission_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$errors = [];

if (!$managerId) {
    $errors[] = 'Missing or invalid manager identifier.';
}

if (!$outletId) {
    $errors[] = 'Missing or invalid outlet identifier.';
}

if (!$submissionId) {
    $errors[] = 'Missing or invalid submission identifier.';
}

$submission = null;

if (empty($errors)) {
    $submission = dbFetchOne(
        "SELECT
            ds.id,
            ds.manager_id,
            ds.outlet_id,
            ds.submission_date,
            ds.batch_code,
            ds.berhad_sales,
            ds.total_income,
            ds.total_expenses,
            ds.net_amount,
            ds.notes,
            o.outlet_name,
            o.outlet_code,
            u.name  AS manager_name,
            u.email AS manager_email
        FROM daily_submissions ds
        INNER JOIN outlets o ON ds.outlet_id = o.id
        INNER JOIN users u   ON ds.manager_id = u.id
        WHERE ds.id = ?
          AND ds.manager_id = ?
          AND ds.outlet_id = ?
        LIMIT 1",
        [$submissionId, $managerId, $outletId]
    );

    if (!$submission) {
        $errors[] = 'No matching submission was found for the provided manager and outlet.';
    }
}

$mpBerhadExpenses = [];
$totalMpBerhadExpenses = 0.0;
$externalSalesRawData = '';
$externalSalesSavedLabel = '';
$externalSalesCsrfToken = null;

if ($submission) {
    $expenseRows = dbFetchAll(
        "SELECT e.id, e.amount, e.description, e.created_at
         FROM expenses e
         INNER JOIN expense_categories ec ON e.expense_category_id = ec.id
         WHERE e.submission_id = ?
           AND ec.category_type = 'mp_berhad'
           AND UPPER(ec.category_name) = 'BERHAD'",
        [$submissionId]
    );

    if ($expenseRows) {
        foreach ($expenseRows as $expenseRow) {
            $mpBerhadExpenses[] = $expenseRow;
            $totalMpBerhadExpenses += (float) $expenseRow['amount'];
        }
    }

    $externalSalesTable = dbFetchOne("SHOW TABLES LIKE 'berhad_external_sales_data'");

    if ($externalSalesTable) {
        $externalSalesRows = dbFetchAll(
            "SELECT
                besd.agent_identifier,
                besd.name,
                besd.level,
                besd.deposit_count,
                besd.total_deposit,
                besd.withdraw_count,
                besd.total_withdraw,
                besd.total
             FROM berhad_external_sales_data besd
             WHERE besd.submission_id = ?
             ORDER BY besd.row_index ASC",
            [$submissionId]
        );

        if ($externalSalesRows) {
            $rowStrings = [];

            foreach ($externalSalesRows as $row) {
                $cells = [
                    trim((string) ($row['agent_identifier'] ?? '')),
                    trim((string) ($row['name'] ?? '')),
                    trim((string) ($row['level'] ?? '')),
                    trim((string) ($row['deposit_count'] ?? '')),
                    trim((string) ($row['total_deposit'] ?? '')),
                    trim((string) ($row['withdraw_count'] ?? '')),
                    trim((string) ($row['total_withdraw'] ?? '')),
                    trim((string) ($row['total'] ?? '')),
                ];

                $rowStrings[] = implode("\t", $cells);
            }

            $externalSalesRawData = implode("\n", $rowStrings);
        }

        $externalSalesLatest = dbFetchOne(
            "SELECT besd.updated_at, u.name AS saved_by_name
             FROM berhad_external_sales_data besd
             LEFT JOIN users u ON besd.saved_by = u.id
             WHERE besd.submission_id = ?
             ORDER BY besd.updated_at DESC
             LIMIT 1",
            [$submissionId]
        );

        if ($externalSalesLatest && !empty($externalSalesLatest['updated_at'])) {
            $timestamp = strtotime($externalSalesLatest['updated_at']);

            if ($timestamp !== false) {
                $formattedDate = date('F j, Y g:i A', $timestamp);
                $savedBy = trim((string) ($externalSalesLatest['saved_by_name'] ?? ''));

                $externalSalesSavedLabel = 'Last saved on ' . $formattedDate;

                if ($savedBy !== '') {
                    $externalSalesSavedLabel .= ' by ' . $savedBy;
                }

                $externalSalesSavedLabel .= '.';
            }
        }
    }

    $externalSalesCsrfToken = csrfGenerate();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berhad Verification Process - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: #fff;
            padding: 24px 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            margin: 0 0 6px;
            font-size: 26px;
        }

        .breadcrumbs {
            font-size: 14px;
            opacity: 0.9;
        }

        .breadcrumbs a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
        }

        .breadcrumbs span {
            margin: 0 4px;
            opacity: 0.7;
        }

        .container {
            max-width: 1100px;
            margin: 24px auto 60px;
            padding: 0 20px;
        }

        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 22px rgba(17, 153, 142, 0.08);
            margin-bottom: 24px;
            padding: 24px 28px;
        }

        .card h2 {
            margin-top: 0;
            font-size: 20px;
            color: #11998e;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }

        .stat {
            background-color: #f0fdfa;
            border: 1px solid #c8f7ee;
            border-radius: 8px;
            padding: 16px;
        }

        .stat .label {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0d7b70;
        }

        .stat .value {
            font-size: 20px;
            font-weight: 600;
            margin-top: 6px;
            color: #0c5550;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        th, td {
            padding: 12px 14px;
            border-bottom: 1px solid #e6f2ef;
            text-align: left;
        }

        th {
            background-color: #f8fffd;
            color: #0f8f7f;
            font-weight: 600;
            font-size: 13px;
        }

        .empty-state {
            padding: 24px;
            border-radius: 8px;
            background-color: #fffaf0;
            border: 1px dashed #facc15;
            color: #8a6d3b;
            margin-bottom: 24px;
        }

        .alert {
            padding: 16px 18px;
            background-color: #fee2e2;
            color: #7f1d1d;
            border: 1px solid #fecaca;
            border-radius: 8px;
            margin: 20px 0;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .comparison-input {
            margin-top: 24px;
        }

        .comparison-input label {
            display: block;
            font-weight: 600;
            color: #0f8f7f;
            margin-bottom: 10px;
        }

        .comparison-input .helper-text {
            font-size: 13px;
            color: #4b5563;
            margin: 0 0 12px;
            line-height: 1.5;
        }

        .comparison-input textarea {
            width: 100%;
            min-height: 160px;
            padding: 14px;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 14px;
            border: 1px solid #cbd5f5;
            border-radius: 8px;
            resize: vertical;
            transition: border-color 0.2s ease;
        }

        .comparison-input textarea:focus {
            outline: none;
            border-color: #38b2ac;
            box-shadow: 0 0 0 3px rgba(56, 178, 172, 0.25);
        }

        .comparison-preview {
            margin-top: 18px;
        }

        .comparison-preview table {
            margin-top: 0;
        }

        .comparison-preview table th,
        .comparison-preview table td {
            font-size: 13px;
        }

        .comparison-template {
            margin-top: 24px;
            padding: 18px 20px;
            border-radius: 10px;
            border: 1px solid #c8f7ee;
            background: #eefdf7;
        }

        .comparison-template h3 {
            margin: 0 0 8px;
            font-size: 16px;
            color: #0f8f7f;
        }

        .comparison-template .template-note {
            margin: 0;
            font-size: 13px;
            color: #4b5563;
        }

        .comparison-template table {
            margin-top: 14px;
            background-color: #fff;
            border: 1px solid #cbd5f5;
            border-radius: 8px;
            overflow: hidden;
        }

        .comparison-template table th {
            background-color: #d1fae5;
            color: #0b7662;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .comparison-template table td {
            position: relative;
            min-height: 36px;
            background-color: #fff;
        }

        .template-actions {
            margin-top: 16px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
        }

        .save-feedback {
            min-height: 20px;
            font-size: 14px;
            color: #0f172a;
        }

        .save-feedback.saving {
            color: #0f766e;
        }

        .save-feedback.success {
            color: #0b7d69;
        }

        .save-feedback.error {
            color: #b91c1c;
        }

        .last-saved-note {
            margin-top: 8px;
            font-size: 13px;
            color: #475569;
        }

        .comparison-template table td:focus-within {
            box-shadow: inset 0 0 0 2px rgba(17, 153, 142, 0.25);
            outline: none;
        }

        .comparison-template table td[contenteditable="true"]:empty::before {
            content: attr(data-placeholder);
            color: #94a3b8;
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            font-size: 13px;
        }

        .btn {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.2s, color 0.2s;
        }

        .btn-primary {
            background-color: #11998e;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #0f837a;
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #1e293b;
        }

        .btn-secondary:hover {
            background-color: #cbd5f5;
        }

        .btn[disabled] {
            opacity: 0.65;
            cursor: not-allowed;
        }

        @media (max-width: 640px) {
            .header h1 {
                font-size: 22px;
            }

            .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="breadcrumbs">
            <a href="dashboard.php">Dashboard</a>
            <span>›</span>
            <a href="verify_submission.php">Pending Submissions</a>
            <span>›</span>
            <a href="berhad_sales_verification.php">Berhad Sales Verification</a>
            <span>›</span>
            <strong>Verification Process</strong>
        </div>
        <h1>Berhad Sales Verification Process</h1>
        <p>Review the submission details for the selected outlet before confirming the Berhad sales.</p>
    </div>

    <div class="container">
        <?php if (!empty($errors)) : ?>
            <div class="alert">
                <strong>We couldn't open the verification workflow.</strong>
                <ul>
                    <?php foreach ($errors as $error) : ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="actions" style="margin-top:16px;">
                    <a class="btn btn-secondary" href="berhad_sales_verification.php">Back to Berhad Verification</a>
                </div>
            </div>
        <?php elseif ($submission) : ?>
            <div class="card">
                <h2>Manager &amp; Outlet</h2>
                <div class="grid">
                    <div class="stat">
                        <div class="label">Manager</div>
                        <div class="value"><?php echo htmlspecialchars($submission['manager_name']); ?></div>
                        <div style="font-size:13px; color:#555; margin-top:6px;">
                            <?php echo htmlspecialchars($submission['manager_email']); ?>
                        </div>
                    </div>
                    <div class="stat">
                        <div class="label">Outlet</div>
                        <div class="value"><?php echo htmlspecialchars($submission['outlet_name']); ?></div>
                        <div style="font-size:13px; color:#555; margin-top:6px;">
                            Code: <?php echo htmlspecialchars($submission['outlet_code']); ?>
                        </div>
                    </div>
                    <div class="stat">
                        <div class="label">Submission Date</div>
                        <div class="value"><?php echo htmlspecialchars(date('F j, Y', strtotime($submission['submission_date']))); ?></div>
                        <?php if (!empty($submission['batch_code'])) : ?>
                            <div style="font-size:13px; color:#555; margin-top:6px;">
                                Batch: <?php echo htmlspecialchars($submission['batch_code']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>Financial Summary</h2>
                <div class="grid">
                    <div class="stat">
                        <div class="label">Berhad Sales</div>
                        <div class="value">RM <?php echo number_format((float) $submission['berhad_sales'], 2); ?></div>
                    </div>
                    <div class="stat">
                        <div class="label">BERHAD CLAIMED</div>
                        <div class="value">RM <?php echo number_format($totalMpBerhadExpenses, 2); ?></div>
                    </div>
                    <div class="stat">
                        <div class="label">Net Amount</div>
                        <div class="value">RM <?php echo number_format((float) $submission['net_amount'], 2); ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>Expense Details</h2>
                <?php if (!empty($mpBerhadExpenses)) : ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date Recorded</th>
                                <th>Amount (RM)</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mpBerhadExpenses as $expense) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date('F j, Y g:i A', strtotime($expense['created_at']))); ?></td>
                                    <td>RM <?php echo number_format((float) $expense['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($expense['description'] ?? '—'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div class="empty-state">
                        No MP Berhad expenses have been linked to this submission yet.
                    </div>
                <?php endif; ?>

                <div class="comparison-input">
                    <label for="external-sales-data">External Sales Data</label>
                    <p class="helper-text">
                        Paste the raw export from the external sales portal. We will automatically convert it into a
                        structured table so you can compare the values against the manager submission.
                    </p>
                    <textarea
                        id="external-sales-data"
                        placeholder="e.g. Date,Total Sales\n2024-01-01,1520.45"
                        data-save-url="/my_site/includes/account/save_berhad_external_sales.php"
                        data-csrf-name="<?php echo htmlspecialchars(CSRF_TOKEN_NAME); ?>"
                        data-csrf-token="<?php echo htmlspecialchars((string) $externalSalesCsrfToken); ?>"
                        data-submission-id="<?php echo (int) $submissionId; ?>"><?php echo htmlspecialchars($externalSalesRawData); ?></textarea>
                    <div class="comparison-template">
                        <h3>External Sales Template</h3>
                        <p class="template-note">Match the pasted data to these nine columns for a consistent review format.</p>
                        <table>
                            <thead>
                                <tr>
                                    <th>Agent</th>
                                    <th>Outlet Name</th>
                                    <th>Level</th>
                                    <th>Deposit</th>
                                    <th>Count</th>
                                    <th>Total Deposit</th>
                                    <th>Withdraw Count</th>
                                    <th>Total Withdraw</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="external-sales-template-body">
                                <tr>
                                    <td colspan="9">
                                        <div class="empty-state">Paste the raw sales data to preview it as a comparison table.</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="template-actions">
                        <button type="button" class="btn btn-primary" id="save-external-sales-button">Save External Sales Data</button>
                        <div id="external-sales-feedback" class="save-feedback" role="status" aria-live="polite"></div>
                    </div>
                    <div id="external-sales-last-saved" class="last-saved-note"<?php if (!$externalSalesSavedLabel) : ?> hidden<?php endif; ?>>
                        <?php if ($externalSalesSavedLabel) : ?>
                            <?php echo htmlspecialchars($externalSalesSavedLabel); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>Verification Notes</h2>
                <?php if (!empty($submission['notes'])) : ?>
                    <p style="white-space: pre-wrap; line-height: 1.6; color:#374151;"><?php echo htmlspecialchars($submission['notes']); ?></p>
                <?php else : ?>
                    <div class="empty-state">
                        The manager has not provided additional notes for this submission.
                    </div>
                <?php endif; ?>
            </div>

            <div class="actions">
                <a class="btn btn-primary" href="berhad_sales_verification.php">Return to Submission List</a>
                <a class="btn btn-secondary" href="verify_submission.php">Back to Pending Submissions</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const textarea = document.getElementById('external-sales-data');
            const templateBody = document.getElementById('external-sales-template-body');
            const templateTable = templateBody ? templateBody.closest('table') : null;
            const headerCells = templateTable ? Array.from(templateTable.querySelectorAll('thead th')) : [];
            const columnCount = headerCells.length;
            const defaultHeaders = headerCells.map(function (th) {
                return th.textContent;
            });
            const saveButton = document.getElementById('save-external-sales-button');
            const feedback = document.getElementById('external-sales-feedback');
            const lastSavedNote = document.getElementById('external-sales-last-saved');
            let latestParsedRows = [];

            if (!textarea || !templateBody || !templateTable || !columnCount) {
                return;
            }

            const emptyMessage = 'Paste the raw sales data to preview it as a comparison table.';
            const noDataMessage = 'No data is available to display.';

            function setFeedback(state, message) {
                if (!feedback) {
                    return;
                }

                feedback.textContent = message || '';
                feedback.className = state ? 'save-feedback ' + state : 'save-feedback';
            }

            function updateCsrfToken(token) {
                if (!token) {
                    return;
                }

                textarea.dataset.csrfToken = token;
            }

            function appendEmptyRow(message) {
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                const wrapper = document.createElement('div');

                td.colSpan = headerCells.length || 1;
                wrapper.className = 'empty-state';
                wrapper.textContent = message;
                td.appendChild(wrapper);
                tr.appendChild(td);

                templateBody.appendChild(tr);
            }

            function resetTemplate(message) {
                headerCells.forEach(function (th, index) {
                    const fallback = defaultHeaders[index] || ('Column ' + (index + 1));
                    th.textContent = fallback;
                });

                templateBody.innerHTML = '';
                latestParsedRows = [];
                appendEmptyRow(message);
            }

            function detectDelimiter(lines) {
                const delimiters = ['\t', ',', ';', '|'];
                let bestDelimiter = ',';
                let bestScore = 0;

                delimiters.forEach(function (delimiter) {
                    let matches = 0;
                    let totalColumns = 0;

                    lines.forEach(function (line) {
                        if (line.indexOf(delimiter) !== -1) {
                            matches += 1;
                            totalColumns += line.split(delimiter).length;
                        }
                    });

                    if (matches) {
                        const averageColumns = totalColumns / matches;
                        if (averageColumns > bestScore) {
                            bestScore = averageColumns;
                            bestDelimiter = delimiter;
                        }
                    }
                });

                if (bestScore === 0) {
                    return ' ';
                }

                return bestDelimiter;
            }

            function parseLine(line, delimiter) {
                if (delimiter === ' ') {
                    return line.trim().split(/\s+/);
                }

                const cells = [];
                let current = '';
                let inQuotes = false;

                for (let i = 0; i < line.length; i += 1) {
                    const char = line[i];

                    if (char === '"') {
                        if (inQuotes && line[i + 1] === '"') {
                            current += '"';
                            i += 1;
                        } else {
                            inQuotes = !inQuotes;
                        }
                    } else if (!inQuotes && char === delimiter) {
                        cells.push(current.trim());
                        current = '';
                    } else {
                        current += char;
                    }
                }

                cells.push(current.trim());
                return cells;
            }

            function renderTable(rows) {
                if (!rows.length) {
                    resetTemplate(noDataMessage);
                    return;
                }

                headerCells.forEach(function (th, index) {
                    const fallback = defaultHeaders[index] || ('Column ' + (index + 1));
                    th.textContent = fallback;
                });

                templateBody.innerHTML = '';
                latestParsedRows = [];

                rows.forEach(function (row) {
                    const tr = document.createElement('tr');
                    const normalizedRow = [];

                    for (let i = 0; i < columnCount; i += 1) {
                        const td = document.createElement('td');
                        const cellValue = row[i] != null ? String(row[i]).trim() : '';
                        normalizedRow.push(cellValue);
                        td.textContent = cellValue;
                        tr.appendChild(td);
                    }

                    latestParsedRows.push(normalizedRow);
                    templateBody.appendChild(tr);
                });
            }

            function updatePreview() {
                const raw = textarea.value.trim();

                if (!raw) {
                    resetTemplate(emptyMessage);
                    return;
                }

                const lines = raw.split(/\r?\n/).map(function (line) {
                    return line.trim();
                }).filter(function (line) {
                    return line.length > 0;
                });

                if (!lines.length) {
                    resetTemplate(emptyMessage);
                    return;
                }

                const delimiter = detectDelimiter(lines);
                const parsedRows = lines.map(function (line) {
                    return parseLine(line, delimiter);
                });

                renderTable(parsedRows);
            }

            function handleSave() {
                if (!textarea || !saveButton) {
                    return;
                }

                const saveUrl = textarea.dataset.saveUrl || '';
                const submissionId = textarea.dataset.submissionId || '';
                const csrfName = textarea.dataset.csrfName || '';
                const csrfToken = textarea.dataset.csrfToken || '';

                if (!saveUrl || !submissionId) {
                    setFeedback('error', 'Saving is not configured for this submission.');
                    return;
                }

                const params = new URLSearchParams();
                params.append('submission_id', submissionId);
                params.append('raw_data', textarea.value);
                params.append('structured_rows', JSON.stringify(latestParsedRows));

                if (csrfName && csrfToken) {
                    params.append(csrfName, csrfToken);
                }

                saveButton.disabled = true;
                setFeedback('saving', 'Saving external sales data...');

                const finish = function () {
                    if (saveButton) {
                        saveButton.disabled = false;
                    }
                };

                fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: params.toString()
                })
                    .then(function (response) {
                        return response.json()
                            .then(function (data) {
                                return { ok: response.ok, data: data };
                            })
                            .catch(function () {
                                return { ok: response.ok, data: null };
                            });
                    })
                    .then(function (result) {
                        const data = result && result.data ? result.data : {};

                        if (data.csrf_token) {
                            updateCsrfToken(data.csrf_token);
                        }

                        if (!result || !result.ok || !data.success) {
                            const errorMessage = data && data.message ? data.message : 'Unable to save external sales data.';
                            setFeedback('error', errorMessage);
                            finish();
                            return;
                        }

                        setFeedback('success', data.message || 'External sales data saved successfully.');

                        if (lastSavedNote) {
                            const savedAt = data.saved_at_display || '';
                            const savedBy = data.saved_by || '';

                            if (savedAt) {
                                let label = 'Last saved on ' + savedAt;
                                if (savedBy) {
                                    label += ' by ' + savedBy;
                                }
                                label += '.';

                                lastSavedNote.textContent = label;
                                lastSavedNote.hidden = false;
                            }
                        }

                        updatePreview();
                        finish();
                    })
                    .catch(function () {
                        setFeedback('error', 'An unexpected error occurred while saving.');
                        finish();
                    });
            }

            textarea.addEventListener('input', updatePreview);
            textarea.addEventListener('blur', updatePreview);
            if (saveButton) {
                saveButton.addEventListener('click', handleSave);
            }
            updatePreview();
        });
    </script>
</body>
</html>
