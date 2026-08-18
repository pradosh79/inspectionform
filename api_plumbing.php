<?php
// Plain procedural PHP API for the Property Inspection Report (MEP / Plumbing)
// form. No framework, no classes. Mirrors the pattern used by api.php
// for the Final Energy Inspection form, but simpler: a single email is
// sent straight to the recipient the user typed in, with a CC to the
// company address (no 3-way admin/client routing).

ob_start();
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (ob_get_level() > 0) ob_end_clean();
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode([
            'error' => 'Server error: ' . $error['message'] . ' (' . basename($error['file']) . ' line ' . $error['line'] . ')'
        ]);
    }
});

header('Content-Type: application/json');

$requiredFiles = ['auth.php', 'config.php', 'report_render_plumbing.php', 'smtp_mailer.php', 'pdf_generate_plumbing.php', 'pdf_generate.php', 'fpdf/fpdf.php'];
foreach ($requiredFiles as $rf) {
    if (!file_exists(__DIR__ . '/' . $rf)) {
        http_response_code(500);
        echo json_encode(['error' => 'Missing server file: ' . $rf . ' -- re-upload the full project folder (including the fpdf/ subfolder).']);
        exit;
    }
}
if (!function_exists('mysqli_stmt_get_result')) {
    http_response_code(500);
    echo json_encode(['error' => "This server's PHP is missing the mysqlnd driver (mysqli_stmt_get_result unavailable). Ask your host to enable mysqlnd for PHP."]);
    exit;
}

require_once 'auth.php';
require_once 'config.php';
require_once 'report_render_plumbing.php';
require_once 'smtp_mailer.php';
require_once 'pdf_generate.php';           // shared FPDF helper functions (rp_checkbox, rp_border2, ...)
require_once 'pdf_generate_plumbing.php';  // generate_plumbing_pdf()

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not signed in.']);
    exit;
}

/**
 * Builds a unique, filesystem-safe PDF filename from the client name and id.
 */
function pl_unique_pdf_filename($record, $id) {
    $safeName = preg_replace('/[^A-Za-z0-9]+/', '_', $record['client']);
    $safeName = trim($safeName, '_');
    if ($safeName === '') $safeName = 'client';
    return 'PropertyInspection_' . $safeName . '_' . date('Ymd') . '_' . substr(md5($id), 0, 8) . '.pdf';
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

/**
 * Reads and validates the posted record. Exits with a 400 error if
 * required fields are missing.
 */
function pl_read_record_from_request() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!$data || empty($data['client']) || empty($data['address'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Client and inspection address are required.']);
        exit;
    }
    return $data;
}

/**
 * Inserts or updates the record in MySQL. Returns the record id.
 */
function pl_save_record($conn, $data) {
    $id = (!empty($data['id'])) ? $data['id'] : uniqid('p', true);

    $reportTitle     = isset($data['reportTitle']) ? $data['reportTitle'] : '';
    $client          = $data['client'];
    $address         = $data['address'];
    $licenseNo       = isset($data['licenseNo']) ? $data['licenseNo'] : '';
    $inspector       = isset($data['inspector']) ? $data['inspector'] : '';
    $date            = (!empty($data['date'])) ? $data['date'] : date('Y-m-d');

    $scopePlumbing   = !empty($data['scopePlumbing']) ? 1 : 0;
    $scopeElectrical = !empty($data['scopeElectrical']) ? 1 : 0;
    $scopeHvac       = !empty($data['scopeHvac']) ? 1 : 0;
    $scopeOther      = !empty($data['scopeOther']) ? 1 : 0;
    $scopeOtherText  = isset($data['scopeOtherText']) ? $data['scopeOtherText'] : '';

    $partiesSuper    = !empty($data['partiesSuperintendent']) ? 1 : 0;
    $partiesSub      = !empty($data['partiesSubcontractor']) ? 1 : 0;
    $partiesOther    = !empty($data['partiesOther']) ? 1 : 0;
    $partiesOtherTxt = isset($data['partiesOtherText']) ? $data['partiesOtherText'] : '';

    $weather         = isset($data['weather']) ? $data['weather'] : '';
    $timeOfInsp      = isset($data['timeOfInspection']) ? $data['timeOfInspection'] : '';
    $outsideTemp     = isset($data['outsideTemp']) ? $data['outsideTemp'] : '';
    $additionalInfo  = isset($data['additionalInfo']) ? $data['additionalInfo'] : '';

    $itemsJson       = json_encode(isset($data['items']) ? $data['items'] : []);
    $recipientEmail  = isset($data['recipientEmail']) ? $data['recipientEmail'] : '';
    $savedAt         = date('Y-m-d H:i:s');

    $sql = "INSERT INTO plumbing_inspections
                (id, report_title, client, inspection_address, inspector_license, inspector_name, inspection_date,
                 scope_plumbing, scope_electrical, scope_hvac, scope_other, scope_other_text,
                 parties_superintendent, parties_subcontractor, parties_other, parties_other_text,
                 weather, time_of_inspection, outside_temp, additional_info, items, recipient_email, saved_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                report_title=VALUES(report_title), client=VALUES(client), inspection_address=VALUES(inspection_address),
                inspector_license=VALUES(inspector_license),
                inspector_name=VALUES(inspector_name), inspection_date=VALUES(inspection_date),
                scope_plumbing=VALUES(scope_plumbing), scope_electrical=VALUES(scope_electrical),
                scope_hvac=VALUES(scope_hvac), scope_other=VALUES(scope_other), scope_other_text=VALUES(scope_other_text),
                parties_superintendent=VALUES(parties_superintendent), parties_subcontractor=VALUES(parties_subcontractor),
                parties_other=VALUES(parties_other), parties_other_text=VALUES(parties_other_text),
                weather=VALUES(weather), time_of_inspection=VALUES(time_of_inspection), outside_temp=VALUES(outside_temp),
                additional_info=VALUES(additional_info), items=VALUES(items),
                recipient_email=VALUES(recipient_email), saved_at=VALUES(saved_at)";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not prepare statement: ' . mysqli_error($conn)]);
        exit;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'sssssssiiiisiiissssssss',
        $id, $reportTitle, $client, $address, $licenseNo, $inspector, $date,
        $scopePlumbing, $scopeElectrical, $scopeHvac, $scopeOther, $scopeOtherText,
        $partiesSuper, $partiesSub, $partiesOther, $partiesOtherTxt,
        $weather, $timeOfInsp, $outsideTemp, $additionalInfo, $itemsJson, $recipientEmail, $savedAt
    );

    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        echo json_encode(['error' => 'Save failed: ' . mysqli_stmt_error($stmt)]);
        exit;
    }
    mysqli_stmt_close($stmt);
    return $id;
}

function pl_fetch_record($conn, $id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM plumbing_inspections WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 's', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $record = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $record;
}

/**
 * Sends the report email: recipient gets the PDF attached; the
 * company address is CC'd on the same message (single send, one PDF).
 */
function pl_send_report_email($record, $toEmail, $COMPANY_EMAIL, $CLIENT_EMAIL, $SMTP_HOST, $SMTP_PORT, $SMTP_ENCRYPTION, $SMTP_USERNAME, $SMTP_PASSWORD, $pdfPath) {
    if (!$toEmail || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Missing or invalid recipient email: ' . $toEmail];
    }
    if (!file_exists($pdfPath)) {
        return ['ok' => false, 'error' => 'Generated PDF not found on server.'];
    }

    $htmlBody = render_plumbing_email_summary_html($record);
    $subject = 'Property Inspection Report — ' . $record['client'] .
               ($record['inspection_date'] ? ' (' . $record['inspection_date'] . ')' : '');

    $mixedBoundary = 'mix_' . md5(uniqid((string) time(), true));
    $mimeHeaders = "MIME-Version: 1.0\r\n";
    $mimeHeaders .= "Content-Type: multipart/mixed; boundary=\"" . $mixedBoundary . "\"\r\n\r\n";

    $body  = "--" . $mixedBoundary . "\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $htmlBody . "\r\n\r\n";

    $pdfData = base64_encode(file_get_contents($pdfPath));
    $pdfName = basename($pdfPath);
    $body .= "--" . $mixedBoundary . "\r\n";
    $body .= "Content-Type: application/pdf; name=\"" . $pdfName . "\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"" . $pdfName . "\"\r\n\r\n";
    $body .= chunk_split($pdfData) . "\r\n";
    $body .= "--" . $mixedBoundary . "--\r\n";

    $smtpCfg = [
        'host' => $SMTP_HOST,
        'port' => $SMTP_PORT,
        'encryption' => $SMTP_ENCRYPTION,
        'username' => $SMTP_USERNAME,
        'password' => $SMTP_PASSWORD
    ];

    $fromHeader = 'A & D Inspections <' . $COMPANY_EMAIL . '>';

    return smtp_send_mail($smtpCfg, $fromHeader, $toEmail, $CLIENT_EMAIL, $subject, $mimeHeaders . $body);
}

// ---- LIST (previous reports) ----
if ($method === 'GET' && $action === 'list') {
    $result = mysqli_query($conn, "SELECT id, report_title, client, inspection_date, inspection_address, inspector_license, saved_at FROM plumbing_inspections ORDER BY saved_at DESC LIMIT 200");
    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not fetch reports: ' . mysqli_error($conn)]);
        exit;
    }
    $records = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $records[] = $row;
    }
    echo json_encode(['ok' => true, 'records' => $records]);
    exit;
}

// ---- DOWNLOAD AS PDF (saves to DB, generates PDF, streams it to the browser) ----
if ($method === 'POST' && $action === 'download_pdf') {
    $data = pl_read_record_from_request();
    $id = pl_save_record($conn, $data);
    $record = pl_fetch_record($conn, $id);
    $record['items'] = json_decode($record['items'], true);

    $filename = pl_unique_pdf_filename($record, $id);
    $docDir = __DIR__ . '/document';
    if (!is_dir($docDir)) mkdir($docDir, 0755, true);
    $outputPath = $docDir . '/' . $filename;

    generate_plumbing_pdf($record, $outputPath);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($outputPath));
    header('X-Record-Id: ' . $id);
    readfile($outputPath);
    exit;
}

// ---- SHARE WITH EMAIL (saves to DB, generates PDF, saves it, emails it as attachment) ----
if ($method === 'POST' && $action === 'share_email') {
    $data = pl_read_record_from_request();

    if (empty($data['recipientEmail']) || !filter_var($data['recipientEmail'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'A valid recipient email address is required.']);
        exit;
    }

    $id = pl_save_record($conn, $data);
    $record = pl_fetch_record($conn, $id);
    $record['items'] = json_decode($record['items'], true);

    $docDir = __DIR__ . '/document';
    if (!is_dir($docDir)) mkdir($docDir, 0755, true);

    $filename = pl_unique_pdf_filename($record, $id);
    $pdfPath = $docDir . '/' . $filename;
    generate_plumbing_pdf($record, $pdfPath);

    $result = pl_send_report_email($record, $data['recipientEmail'], $COMPANY_EMAIL, $CLIENT_EMAIL, $SMTP_HOST, $SMTP_PORT, $SMTP_ENCRYPTION, $SMTP_USERNAME, $SMTP_PASSWORD, $pdfPath);
    if ($result['ok']) {
        echo json_encode(['ok' => true, 'id' => $id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $result['error']]);
    }
    exit;
}

// ---- EMAIL BY ID (resend an already-saved record from report_plumbing.php) ----
if ($method === 'POST' && $action === 'email_by_id') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $id = isset($data['id']) ? $data['id'] : '';
    $recipientEmail = isset($data['recipientEmail']) ? trim($data['recipientEmail']) : '';

    if ($id === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Missing report id.']);
        exit;
    }
    if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'A valid recipient email address is required.']);
        exit;
    }

    $record = pl_fetch_record($conn, $id);
    if (!$record) {
        http_response_code(404);
        echo json_encode(['error' => 'Report not found.']);
        exit;
    }

    $stmt = mysqli_prepare($conn, "UPDATE plumbing_inspections SET recipient_email = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $recipientEmail, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $record['recipient_email'] = $recipientEmail;
    $record['items'] = json_decode($record['items'], true);

    $docDir = __DIR__ . '/document';
    if (!is_dir($docDir)) mkdir($docDir, 0755, true);

    $filename = pl_unique_pdf_filename($record, $id);
    $pdfPath = $docDir . '/' . $filename;
    generate_plumbing_pdf($record, $pdfPath);

    $result = pl_send_report_email($record, $recipientEmail, $COMPANY_EMAIL, $CLIENT_EMAIL, $SMTP_HOST, $SMTP_PORT, $SMTP_ENCRYPTION, $SMTP_USERNAME, $SMTP_PASSWORD, $pdfPath);
    if ($result['ok']) {
        echo json_encode(['ok' => true, 'id' => $id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $result['error']]);
    }
    exit;
}

// ---- Unknown action ----
http_response_code(400);
echo json_encode(['error' => 'Unknown or missing action.']);
