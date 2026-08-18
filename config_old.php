<?php
// Database connection settings.
// Fill these in with the credentials your web host / MySQL server gives you.
$DB_HOST = '127.0.0.1:3306';
$DB_NAME = 'u533806958_inspectionform';
$DB_USER = 'u533806958_inspectionform';
$DB_PASS = '1ino1$vzH~';

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// The inspector's / client's own email address.
// Every report sent by email will be CC'd here automatically.
$CC_EMAIL = 'tirtha_big@yahoo.com'; // <-- replace with the real CC address

// SMTP settings for sending mail. PHP's built-in mail() function is
// disabled/unreliable on most hosts (including Hostinger), so mail is
// sent through an authenticated SMTP connection instead.
// These are almost always the SAME as the mailbox login you use in
// Outlook/webmail for this address (e.g. james@adinspections.com).
$SMTP_HOST       = 'smtp.gmail.com';
$SMTP_PORT       = 587;
$SMTP_ENCRYPTION = 'tls';   // ← matches port 587
$SMTP_USERNAME   = 'gregory.mcneely70@gmail.com';
$SMTP_PASSWORD   = 'mueo piqo vgzn cdqo';   // <-- the real mailbox password

// Company letterhead, reused on the report view and in the email.
$COMPANY_NAME    = 'A & D INSPECTIONS, LLC';
$COMPANY_ADDRESS = '16918 Rolling Acres Dr.  Humble, TX. 77396';
$COMPANY_PHONE   = '281-802-0247';
$COMPANY_EMAIL   = 'james@adinspections.com';
$COMPANY_WEBSITE = 'www.adinspections.com';
