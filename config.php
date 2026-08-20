<?php
// Database + app settings.
// On Railway (and any modern host) credentials come from ENVIRONMENT
// VARIABLES, never hardcoded. Set these in your Railway service ->
// "Variables" tab. When you add a Railway MySQL database, Railway
// injects MYSQLHOST / MYSQLPORT / MYSQLUSER / MYSQLPASSWORD / MYSQL_DATABASE
// automatically -- the getenv() fallbacks below pick those up.

$DB_HOST = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: '127.0.0.1';
$DB_PORT = (int)(getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: 3306);
$DB_NAME = getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: 'inspectionform';
$DB_USER = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: getenv('MYSQLPASSWORD') ?: '';

// NOTE: host and port are passed SEPARATELY. Do not glue ":3306" onto
// the host string -- mysqli does not parse a port out of the host.
$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);

if (!$conn) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ---- Email routing (also moved to env vars) ----
$ADMIN_EMAIL  = getenv('ADMIN_EMAIL')  ?: 'tirtha_big@yahoo.com';
$CLIENT_EMAIL = getenv('CLIENT_EMAIL') ?: 'pradosh.bargad@gmail.com';

// ---- SMTP (secrets from env; NEVER hardcode) ----
$SMTP_HOST       = getenv('SMTP_HOST')       ?: 'smtp.gmail.com';
$SMTP_PORT       = (int)(getenv('SMTP_PORT') ?: 587);
$SMTP_ENCRYPTION = getenv('SMTP_ENCRYPTION') ?: 'tls';
$SMTP_USERNAME   = getenv('SMTP_USERNAME')   ?: '';
$SMTP_PASSWORD   = getenv('SMTP_PASSWORD')   ?: '';   // set in Railway Variables

// ---- Company letterhead ----
$COMPANY_NAME    = 'A & D INSPECTIONS, LLC';
$COMPANY_ADDRESS = '16918 Rolling Acres Dr.  Humble, TX. 77396';
$COMPANY_PHONE   = '281-802-0247';
$COMPANY_EMAIL   = 'james@adinspections.com';
$COMPANY_WEBSITE = 'www.adinspections.com';
