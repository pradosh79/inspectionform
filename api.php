<?php
// Plain procedural PHP API. No framework, no classes.

// Catch fatal errors (missing files, missing PHP extensions, etc.) and
// report them as JSON instead of a blank 500 page, so problems are
// actually diagnosable from the browser's network tab.
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

// Verify every file this script depends on actually made it onto the
// server before requiring them -- a missing file here would otherwise
// cause a blank, undiagnosable 500 error.
$requiredFiles = ['auth.php', 'config.php', 'report_render.php', 'report_letterhead.php', 'smtp_mailer.php', 'pdf_generate.php', 'fpdf/fpdf.php'];
foreach ($requiredFiles as $rf) {
    if (!file_exists(__DIR__ . '/' . $rf)) {
        http_response_code(500);
        echo json_encode(['error' => 'Missing server file: ' . $rf . ' -- re-upload the full project folder (including the fpdf/ subfolder).']);
        exit;
    }
}
$requiredFonts = ['courier', 'courierb', 'courierbi', 'courieri', 'helvetica', 'helveticab', 'helveticabi', 'helveticai', 'symbol', 'times', 'timesb', 'timesbi', 'timesi', 'zapfdingbats'];
foreach ($requiredFonts as $font) {
    $fontFile = __DIR__ . '/fpdf/font/' . $font . '.json';
    if (!file_exists($fontFile) || filesize($fontFile) === 0) {
        http_response_code(500);
        echo json_encode(['error' => 'Missing or empty font file: fpdf/font/' . $font . '.json -- the fpdf/ folder uploaded incompletely. Re-upload fpdf.zip and extract it fresh on the server.']);
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
require_once 'report_render.php';
require_once 'smtp_mailer.php';
require_once 'pdf_generate.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not signed in.']);
    exit;
}

/**
 * Builds a unique, filesystem-safe PDF filename from the client name and id.
 */
function unique_pdf_filename($record, $id) {
    $safeName = preg_replace('/[^A-Za-z0-9]+/', '_', $record['client']);
    $safeName = trim($safeName, '_');
    if ($safeName === '') $safeName = 'client';
    return 'Inspection_' . $safeName . '_' . date('Ymd') . '_' . substr(md5($id), 0, 8) . '.pdf';
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

/**
 * Reads and validates the posted record, returns it as an assoc array
 * ready for DB binding. Exits with a 400 error if required fields are missing.
 */
function read_record_from_request() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!$data || empty($data['client']) || empty($data['address'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Client and address are required.']);
        exit;
    }
    return $data;
}

/**
 * Inserts or updates the record in MySQL. Returns the record id.
 */
function save_record($conn, $data) {
    $id = (!empty($data['id'])) ? $data['id'] : uniqid('r', true);

    $client         = $data['client'];
    $date           = (!empty($data['date'])) ? $data['date'] : date('Y-m-d');
    $reportNumber   = isset($data['reportNumber']) ? $data['reportNumber'] : '';
    $address        = $data['address'];
    $ieccYear       = isset($data['ieccYear']) ? $data['ieccYear'] : '';
    $ieccYear2      = isset($data['ieccYear2']) ? $data['ieccYear2'] : '';
    $areasJson      = json_encode(isset($data['areas']) ? $data['areas'] : []);
    $fee            = (isset($data['fee']) && $data['fee'] !== '') ? $data['fee'] : null;
    $paymentMethod  = isset($data['paymentMethod']) ? $data['paymentMethod'] : null;
    $paymentOption  = (isset($data['paymentOption']) && $data['paymentOption'] !== '') ? (int) $data['paymentOption'] : null;
    $reField        = isset($data['reField']) ? $data['reField'] : '';
    $pmName         = isset($data['pmName']) ? $data['pmName'] : '';
    $pmCell         = isset($data['pmCell']) ? $data['pmCell'] : '';
    $companyName    = isset($data['companyName']) ? $data['companyName'] : '';
    $companyContact = isset($data['companyContact']) ? $data['companyContact'] : '';
    $signatureName  = isset($data['signatureName']) ? $data['signatureName'] : '';
    $signatureDate  = (!empty($data['signatureDate'])) ? $data['signatureDate'] : null;
    $recipientEmail = isset($data['recipientEmail']) ? $data['recipientEmail'] : '';
    $savedAt        = date('Y-m-d H:i:s');

    $sql = "INSERT INTO inspections
                (id, client, inspection_date, report_number, address, iecc_year, iecc_year2, areas,
                 fee, payment_method, payment_option, re_field, pm_name, pm_cell, company_name,
                 company_contact, signature_name, signature_date, recipient_email, saved_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                client=VALUES(client), inspection_date=VALUES(inspection_date), report_number=VALUES(report_number),
                address=VALUES(address), iecc_year=VALUES(iecc_year), iecc_year2=VALUES(iecc_year2), areas=VALUES(areas),
                fee=VALUES(fee), payment_method=VALUES(payment_method), payment_option=VALUES(payment_option),
                re_field=VALUES(re_field), pm_name=VALUES(pm_name), pm_cell=VALUES(pm_cell), company_name=VALUES(company_name),
                company_contact=VALUES(company_contact), signature_name=VALUES(signature_name), signature_date=VALUES(signature_date),
                recipient_email=VALUES(recipient_email), saved_at=VALUES(saved_at)";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not prepare statement: ' . mysqli_error($conn)]);
        exit;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ssssssssdsisssssssss',
        $id, $client, $date, $reportNumber, $address, $ieccYear, $ieccYear2, $areasJson,
        $fee, $paymentMethod, $paymentOption, $reField, $pmName, $pmCell, $companyName,
        $companyContact, $signatureName, $signatureDate, $recipientEmail, $savedAt
    );

    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        echo json_encode(['error' => 'Save failed: ' . mysqli_stmt_error($stmt)]);
        exit;
    }
    mysqli_stmt_close($stmt);
    return $id;
}

function fetch_record($conn, $id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM inspections WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 's', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $record = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $record;
}

/**
 * Sends the styled report email to a specific $toEmail (no CC --
 * callers now send separate, targeted emails to each recipient).
 * $attachments is a list of filesystem paths to PDFs to attach (0, 1, or more).
 * Returns ['ok' => bool, 'error' => string|null].
 */
function send_report_email($record, $toEmail, $COMPANY_EMAIL, $SMTP_HOST, $SMTP_PORT, $SMTP_ENCRYPTION, $SMTP_USERNAME, $SMTP_PASSWORD, $attachments = [], $greetingOverride = null, $internalNote = null) {
    $htmlBody = render_email_summary_html($record, 'cid:signature', '', $greetingOverride, $internalNote);

    $sigPath = __DIR__ . '/image/signature.png';
    if (!file_exists($sigPath)) {
        return ['ok' => false, 'error' => 'image/signature.png is missing from the server folder.'];
    }
    if (!$toEmail || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Missing or invalid recipient email: ' . $toEmail];
    }

    $to = $toEmail;
    $subject = 'Final Energy Inspection Report — ' . $record['client'] .
               ($record['inspection_date'] ? ' (' . $record['inspection_date'] . ')' : '');

    $relatedBoundary = 'rel_' . md5(uniqid((string) time(), true));
    $related  = "--" . $relatedBoundary . "\r\n";
    $related .= "Content-Type: text/html; charset=UTF-8\r\n";
    $related .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $related .= $htmlBody . "\r\n\r\n";
    $imgData = base64_encode(file_get_contents($sigPath));
    $related .= "--" . $relatedBoundary . "\r\n";
    $related .= "Content-Type: image/png; name=\"signature.png\"\r\n";
    $related .= "Content-Transfer-Encoding: base64\r\n";
    $related .= "Content-ID: <signature>\r\n";
    $related .= "Content-Disposition: inline; filename=\"signature.png\"\r\n\r\n";
    $related .= chunk_split($imgData) . "\r\n";
    $related .= "--" . $relatedBoundary . "--\r\n";

    $validAttachments = array_filter($attachments, function ($p) { return $p && file_exists($p); });

    if (!empty($validAttachments)) {
        $mixedBoundary = 'mix_' . md5(uniqid((string) time(), true) . 'x');
        $mimeHeaders = "MIME-Version: 1.0\r\n";
        $mimeHeaders .= "Content-Type: multipart/mixed; boundary=\"" . $mixedBoundary . "\"\r\n\r\n";

        $body  = "--" . $mixedBoundary . "\r\n";
        $body .= "Content-Type: multipart/related; boundary=\"" . $relatedBoundary . "\"\r\n\r\n";
        $body .= $related . "\r\n";

        foreach ($validAttachments as $pdfPath) {
            $pdfData = base64_encode(file_get_contents($pdfPath));
            $pdfName = basename($pdfPath);
            $body .= "--" . $mixedBoundary . "\r\n";
            $body .= "Content-Type: application/pdf; name=\"" . $pdfName . "\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= "Content-Disposition: attachment; filename=\"" . $pdfName . "\"\r\n\r\n";
            $body .= chunk_split($pdfData) . "\r\n";
        }
        $body .= "--" . $mixedBoundary . "--\r\n";
    } else {
        $mimeHeaders = "MIME-Version: 1.0\r\n";
        $mimeHeaders .= "Content-Type: multipart/related; boundary=\"" . $relatedBoundary . "\"\r\n\r\n";
        $body = $related;
    }

    $smtpCfg = [
        'host' => $SMTP_HOST,
        'port' => $SMTP_PORT,
        'encryption' => $SMTP_ENCRYPTION,
        'username' => $SMTP_USERNAME,
        'password' => $SMTP_PASSWORD
    ];

    $fromHeader = 'Final Energy Inspection Reports <' . $COMPANY_EMAIL . '>';

    return smtp_send_mail($smtpCfg, $fromHeader, $to, '', $subject, $mimeHeaders . $body);
}

// ---- SAVE (used by both "Show report" and "Send email") ----
if ($method === 'POST' && $action === 'save') {
    $data = read_record_from_request();
    $id = save_record($conn, $data);
    echo json_encode(['ok' => true, 'id' => $id]);
    exit;
}

/**
 * Implements the 3-way routing:
 *   - end-user (recipientEmail)  -> certificate only
 *   - ADMIN_EMAIL                -> full 3-page report only
 *   - CLIENT_EMAIL               -> both attachments
 * Returns ['ok' => bool, 'errors' => array of any per-recipient failures]
 */
function send_three_way(
    $record, $recipientEmail, $certPath, $reportPath,
    $COMPANY_EMAIL, $ADMIN_EMAIL, $CLIENT_EMAIL,
    $SMTP_HOST, $SMTP_PORT, $SMTP_ENCRYPTION, $SMTP_USERNAME, $SMTP_PASSWORD
) {
    $errors = [];

    $r1 = send_report_email($record, $recipientEmail, $COMPANY_EMAIL, $SMTP_HOST, $SMTP_PORT, $SMTP_ENCRYPTION, $SMTP_USERNAME, $SMTP_PASSWORD, [$certPath]);
    if (!$r1['ok']) $errors[] = 'End-user email: ' . $r1['error'];

    $r2 = send_report_email($record, $ADMIN_EMAIL, $COMPANY_EMAIL, $SMTP_HOST, $SMTP_PORT, $SMTP_ENCRYPTION, $SMTP_USERNAME, $SMTP_PASSWORD, [$reportPath], 'Hi Admin,', 'Internal copy -- full inspection report for your records.');
    if (!$r2['ok']) $errors[] = 'Admin email: ' . $r2['error'];

    $r3 = send_report_email($record, $CLIENT_EMAIL, $COMPANY_EMAIL, $SMTP_HOST, $SMTP_PORT, $SMTP_ENCRYPTION, $SMTP_USERNAME, $SMTP_PASSWORD, [$certPath, $reportPath], 'Hi,', 'Copy of the certificate and full report sent for this inspection.');
    if (!$r3['ok']) $errors[] = 'Client email: ' . $r3['error'];

    return ['ok' => empty($errors), 'errors' => $errors];
}

// ---- EMAIL BY ID (from report.php's inline Send Email box, reusing an already-saved record) ----
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

    $record = fetch_record($conn, $id);
    if (!$record) {
        http_response_code(404);
        echo json_encode(['error' => 'Report not found.']);
        exit;
    }

    // Update the recipient email on the saved record, then send.
    $stmt = mysqli_prepare($conn, "UPDATE inspections SET recipient_email = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'ss', $recipientEmail, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $record['recipient_email'] = $recipientEmail;
    $record['areas'] = json_decode($record['areas'], true);

    $docDir = __DIR__ . '/document';
    if (!is_dir($docDir)) mkdir($docDir, 0755, true);

    $reportFilename = unique_pdf_filename($record, $id);
    $reportPath = $docDir . '/' . $reportFilename;
    generate_report_pdf($record, $reportPath);

    $certFilename = 'Certificate_' . preg_replace('/^Inspection_/', '', $reportFilename);
    $certPath = $docDir . '/' . $certFilename;
    generate_certificate_pdf($record, $certPath);

    $result = send_three_way($record, $recipientEmail, $certPath, $reportPath, $COMPANY_EMAIL, $ADMIN_EMAIL, $CLIENT_EMAIL, $SMTP_HOST, $SMTP_PORT, $SMTP_ENCRYPTION, $SMTP_USERNAME, $SMTP_PASSWORD);
    if ($result['ok']) {
        echo json_encode(['ok' => true, 'id' => $id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => implode(' | ', $result['errors'])]);
    }
    exit;
}

// ---- LIST (previous reports) ----
if ($method === 'GET' && $action === 'list') {
    $result = mysqli_query($conn, "SELECT id, client, inspection_date, address, saved_at FROM inspections ORDER BY saved_at DESC LIMIT 200");
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
    $data = read_record_from_request();
    $id = save_record($conn, $data);
    $record = fetch_record($conn, $id);
    $record['areas'] = json_decode($record['areas'], true);

    $filename = unique_pdf_filename($record, $id);
    $docDir = __DIR__ . '/document';
    if (!is_dir($docDir)) mkdir($docDir, 0755, true);
    $outputPath = $docDir . '/' . $filename;

    generate_report_pdf($record, $outputPath);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($outputPath));
    header('X-Record-Id: ' . $id);
    readfile($outputPath);
    exit;
}

// ---- SHARE WITH EMAIL (saves to DB, generates PDF, saves it, emails it as attachment) ----
if ($method === 'POST' && $action === 'share_email') {
    $data = read_record_from_request();

    if (empty($data['recipientEmail']) || !filter_var($data['recipientEmail'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'A valid recipient email address is required.']);
        exit;
    }

    $id = save_record($conn, $data);
    $record = fetch_record($conn, $id);
    $record['areas'] = json_decode($record['areas'], true);

    $docDir = __DIR__ . '/document';
    if (!is_dir($docDir)) mkdir($docDir, 0755, true);

    $reportFilename = unique_pdf_filename($record, $id);
    $reportPath = $docDir . '/' . $reportFilename;
    generate_report_pdf($record, $reportPath);

    $certFilename = 'Certificate_' . preg_replace('/^Inspection_/', '', $reportFilename);
    $certPath = $docDir . '/' . $certFilename;
    generate_certificate_pdf($record, $certPath);

    // Three separate, targeted emails:
    //   end-user      -> certificate only
    //   office admin  -> full 3-page report only
    //   you (client)  -> both
    $result = send_three_way($record, $data['recipientEmail'], $certPath, $reportPath, $COMPANY_EMAIL, $ADMIN_EMAIL, $CLIENT_EMAIL, $SMTP_HOST, $SMTP_PORT, $SMTP_ENCRYPTION, $SMTP_USERNAME, $SMTP_PASSWORD);
    if ($result['ok']) {
        echo json_encode(['ok' => true, 'id' => $id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => implode(' | ', $result['errors'])]);
    }
    exit;
}

// ---- Unknown action ----
http_response_code(400);
echo json_encode(['error' => 'Unknown or missing action.']);
