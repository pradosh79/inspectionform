<?php
/**
 * Generates the filled-in 3-page inspection report as a real PDF file,
 * matching the original document's letterhead design. Plain procedural
 * usage of the FPDF library (no app-level OOP/framework code).
 */
require_once __DIR__ . '/fpdf/fpdf.php';

define('RP_GREEN', [46, 125, 50]);
define('RP_GRAY', [90, 90, 90]);
define('RP_BLACK', [20, 20, 20]);

/**
 * FPDF's core fonts only support Windows-1252 (Latin-1-ish), not UTF-8.
 * Convert any text pulled from the database/JS (which is UTF-8) before
 * handing it to FPDF, or accented characters and punctuation like em
 * dashes render as garbled bytes.
 */
function rp_txt($s) {
    if ($s === null) return '';
    $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', (string) $s);
    return $converted !== false ? $converted : (string) $s;
}

/**
 * Builds a single-page, landscape "Certificate of Inspection" PDF --
 * a wall-worthy document (not a working form) meant to be printed
 * and framed by the client. Uses the company logo if present at
 * image/logo.png, otherwise falls back to a text wordmark.
 *
 * @param array $record  DB row, with 'areas' already json_decode'd
 * @param string $outputPath  Full filesystem path to write the PDF to
 */
/**
 * Builds a single-page, A4-landscape "Certificate of Inspection" PDF --
 * a wall-worthy, frame-ready document (not a working form). Uses the
 * company logo if present at image/logo.png, otherwise falls back to
 * a text wordmark.
 *
 * Design: navy blue + gold classic certificate palette, serif
 * typography (Times, the closest FPDF core font to Georgia/Playfair),
 * generous margins, and a decorative double border with corner
 * flourishes.
 *
 * @param array $record  DB row, with 'areas' already json_decode'd
 * @param string $outputPath  Full filesystem path to write the PDF to
 */
/**
 * Builds the certificate as a single page -- literally the same
 * "page 1" content and design as the full 3-page report (letterhead,
 * client/date/report#/address, IECC line, Areas Inspected table,
 * inspector sign-off, footer note). No separate certificate wording;
 * this is intentional, per the client's decision to keep one
 * consistent design across both documents.
 *
 * @param array $record  DB row, with 'areas' already json_decode'd
 * @param string $outputPath  Full filesystem path to write the PDF to
 */
function generate_certificate_pdf($record, $outputPath) {
    $pdf = new FPDF('P', 'mm', 'Letter');
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $sigPath = __DIR__ . '/image/signature.png';

    $darkGreen  = [45, 80, 22];
    $medGreen   = [74, 124, 44];
    $lightGreen = [232, 245, 233];
    $zebra      = [244, 249, 244];
    $darkGray   = [51, 51, 51];
    $footGray   = [120, 120, 120];

    $W = 215.9; $H = 279.4;
    $M = 20;
    $contentW = $W - ($M * 2);

    $pdf->AddPage();
    rp_render_page1($pdf, $record, $M, $contentW, $W, $H, $darkGreen, $medGreen, $lightGreen, $zebra, $darkGray, $footGray, $sigPath);

    $pdf->Output('F', $outputPath);
    return $outputPath;
}



/**
 * Builds the PDF for $record and saves it to $outputPath.
 * @param array $record  DB row, with 'areas' already json_decode'd
 * @param string $outputPath  Full filesystem path to write the PDF to
 */
/**
 * Renders the full "page 1" inspection report content (letterhead,
 * client/date/report#/address, IECC line, Areas Inspected table,
 * inspector sign-off, footer note) onto whatever page is currently
 * active on $pdf. Used by both generate_report_pdf() (as its first
 * page) and generate_certificate_pdf() (as its only page), so the
 * two are guaranteed to look identical -- not just similar.
 */
function rp_render_page1($pdf, $record, $M, $contentW, $W, $H, $darkGreen, $medGreen, $lightGreen, $zebra, $darkGray, $footGray, $sigPath) {
    rp_border2($pdf, $darkGreen, $W, $H);
    $y = rp_letterhead2($pdf, $M, $contentW, $darkGreen, $darkGray);

    $y = rp_label_row($pdf, $M, $contentW, $y, 'Client:', rp_txt($record['client']), $darkGray);
    $y = rp_label_row($pdf, $M, $contentW, $y, 'Date:', rp_txt($record['inspection_date']), $darkGray);
    $y = rp_label_row($pdf, $M, $contentW, $y, 'Report #', rp_txt($record['report_number']), $darkGray);
    $y += 1;
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(...$darkGray);
    $pdf->Cell($contentW, 6, 'Inspection Address:', 0, 1);
    $y += 6;
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.25);
    $pdf->Rect($M, $y, $contentW, 12);
    $pdf->SetXY($M + 3, $y + 3.5);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell($contentW - 6, 5, rp_txt($record['address']));
    $y += 17;

    // IECC inspection line
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'B', 12.5);
    $pdf->SetTextColor(...$medGreen);
    rp_underline_value2($pdf, rp_txt($record['iecc_year']), $M, $y, 18, $darkGray);
    $pdf->SetXY($M + 22, $y);
    $pdf->Cell(42, 6, 'IECC Inspection');
    $pdf->SetFont('Arial', 'B', 10.5);
    $pdf->SetTextColor(...$darkGray);
    $pdf->SetXY($M + 90, $y);
    $pdf->Cell(28, 6, 'Scope of work:');
    $pdf->SetFont('Arial', '', 10.5);
    $pdf->Cell(0, 6, ' Final Energy Inspection');
    $y += 9;

    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetTextColor(...$darkGray);
    $pdf->Cell(88, 6, 'This building meets the requirements of the IECC ');
    rp_underline_value2($pdf, rp_txt($record['iecc_year2']), $M + 88, $y, 16, $darkGray);
    $pdf->SetXY($M + 106, $y);
    $pdf->Cell(0, 6, '.');
    $y += 11;

    // Areas Inspected table
    $areas = is_array($record['areas']) ? $record['areas'] : [];
    if (empty($areas)) $areas = [['name' => '—', 'status' => null]];
    $col1 = $contentW - 60; $col2 = 30; $col3 = 30;

    $tableBorder = [130, 130, 130]; // darker + thicker than before -- the old 180/0.25 combo
    $tableLineW = 0.4;              // rendered as under 1px and was nearly invisible, especially
                                     // on the closing bottom edge of the last row.

    $pdf->SetXY($M, $y);
    $pdf->SetFillColor(...$lightGreen);
    $pdf->SetTextColor(...$darkGreen);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetDrawColor(...$tableBorder);
    $pdf->SetLineWidth($tableLineW);
    $pdf->Cell($col1, 8, '  Areas Inspected', 1, 0, 'L', true);
    $pdf->Cell($col2, 8, 'Passed', 1, 0, 'C', true);
    $pdf->Cell($col3, 8, 'Failed', 1, 1, 'C', true);
    $y += 8;

    $rowH = 9;
    foreach ($areas as $i => $a) {
        $pdf->SetXY($M, $y);
        $fill = ($i % 2 === 1);
        $pdf->SetFillColor(...$zebra);
        $pdf->SetTextColor(...$darkGray);
        $pdf->SetFont('Arial', '', 10.5);
        $pdf->SetDrawColor(...$tableBorder);
        $pdf->SetLineWidth($tableLineW);
        $pdf->Cell($col1, $rowH, '  ' . rp_txt(isset($a['name']) ? $a['name'] : ''), 1, 0, 'L', $fill);
        $status = isset($a['status']) ? $a['status'] : '';
        $pdf->SetFillColor(...$zebra);
        $pdf->SetDrawColor(...$tableBorder);
        $pdf->SetLineWidth($tableLineW);
        $pdf->Cell($col2, $rowH, '', 1, 0, 'C', $fill);
        rp_checkbox($pdf, $M + $col1 + $col2/2 - 2.5, $y + $rowH/2 - 2.5, $status === 'pass', $darkGreen);
        $pdf->SetXY($M + $col1 + $col2, $y);
        $pdf->SetFillColor(...$zebra);
        $pdf->SetDrawColor(...$tableBorder);
        $pdf->SetLineWidth($tableLineW);
        $pdf->Cell($col3, $rowH, '', 1, 1, 'C', $fill);
        rp_checkbox($pdf, $M + $col1 + $col2 + $col3/2 - 2.5, $y + $rowH/2 - 2.5, $status === 'fail', [166, 42, 42]);
        $y += $rowH;
    }
    $y += 8;

    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetTextColor(...$darkGray);
    $pdf->Cell(0, 6, 'Inspector: James Southerland');
    $y += 8;
    if (file_exists($sigPath)) {
        $pdf->Image($sigPath, $M, $y, 42, 10.5);
    }
    $y += 13;
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell($contentW, 5, "0882584\nICC Residential Combination Inspector\nInternational Energy Conservation Code Certified\nICC Certified: Accessibility Inspector/Plans Examiner");
    $y = $pdf->GetY() + 3;

    rp_footer_note($pdf, $M, $contentW, $y, "NOTE:  This inspection includes IECC energy code items only.  This inspection does not include structural, MEP, or accessibility items covered by other applicable codes.", $footGray);

    return $y;
}

function generate_report_pdf($record, $outputPath) {
    $pdf = new FPDF('P', 'mm', 'Letter'); // 215.9 x 279.4mm
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $sigPath = __DIR__ . '/image/signature.png';

    // ---- Palette ----
    $darkGreen  = [45, 80, 22];    // #2D5016 -- borders, headers
    $medGreen   = [74, 124, 44];   // #4A7C2C -- section headers
    $lightGreen = [232, 245, 233]; // #E8F5E9 -- table header / accent bg
    $zebra      = [244, 249, 244]; // faint zebra stripe, lighter than $lightGreen
    $darkGray   = [51, 51, 51];    // #333333 -- body text
    $footGray   = [120, 120, 120];

    $W = 215.9; $H = 279.4;
    $M = 20; // 20mm margins on all sides -> content column
    $contentW = $W - ($M * 2);

    $feeDisplay = $record['fee'] ? number_format((float) $record['fee'], 2) : '0.00';
    $paymentOption = rp_map_payment_option($record);

    // =========================================================
    // PAGE 1: INSPECTION REPORT
    // =========================================================
    $pdf->AddPage();
    rp_render_page1($pdf, $record, $M, $contentW, $W, $H, $darkGreen, $medGreen, $lightGreen, $zebra, $darkGray, $footGray, $sigPath);

    // =========================================================
    // PAGE 2: INVOICE
    // =========================================================
    $pdf->AddPage();
    rp_border2($pdf, $darkGreen, $W, $H);
    $y = rp_letterhead2($pdf, $M, $contentW, $darkGreen, $darkGray);

    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'B', 17);
    $pdf->SetTextColor(...$darkGreen);
    $pdf->Cell($contentW, 9, 'Invoice');
    $y += 10;
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetTextColor(...$darkGray);
    $pdf->Cell($contentW, 6, 'Payment is due at the time of service.');
    $y += 10;

    $y = rp_label_row($pdf, $M, $contentW, $y, 'Client:', rp_txt($record['client']), $darkGray);
    $y = rp_label_row($pdf, $M, $contentW, $y, 'Date:', rp_txt($record['inspection_date']), $darkGray);
    $y = rp_label_row($pdf, $M, $contentW, $y, 'Report #', rp_txt($record['report_number']), $darkGray);
    $y = rp_label_row($pdf, $M, $contentW, $y, 'Inspection Address:', rp_txt($record['address']), $darkGray);
    $y = rp_label_row($pdf, $M, $contentW, $y, 'Scope of Work:', 'Final Energy Inspection', $darkGray);
    $y += 2;

    // Fee row: label left, value right-aligned
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(...$darkGreen);
    $pdf->Cell($contentW / 2, 8, 'Fee:');
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell($contentW / 2, 8, '$' . $feeDisplay, 0, 1, 'R');
    $y += 12;

    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(...$darkGray);
    $pdf->Cell($contentW, 6, 'Payment Method:');
    $y += 8;

    $methods = ['Check', 'Cash', 'Credit Card'];
    $mx = $M;
    foreach ($methods as $m) {
        $checked = ($record['payment_method'] === $m);
        rp_checkbox($pdf, $mx, $y, $checked, $darkGreen);
        $pdf->SetXY($mx + 6, $y - 1.3);
        $pdf->SetFont('Arial', $checked ? 'B' : '', 10.5);
        $pdf->SetTextColor(...$darkGray);
        $pdf->Cell(40, 6, $m);
        $mx += 45;
    }
    $y += 16;

    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(...$darkGray);
    $pdf->Cell($contentW, 6, 'Thank you for your business');
    $y += 12;

    rp_footer_note($pdf, $M, $contentW, $y, 'Note: We do not store credit card information.', $footGray);

    // =========================================================
    // PAGE 3: PAYMENT METHOD / AGREEMENT
    // =========================================================
    $pdf->AddPage();
    rp_border2($pdf, $darkGreen, $W, $H);
    $y = rp_letterhead2($pdf, $M, $contentW, $darkGreen, $darkGray);

    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'B', 17);
    $pdf->SetTextColor(...$darkGreen);
    $pdf->Cell($contentW, 9, 'Payment Method / Agreement');
    $y += 11;

    $y = rp_label_row($pdf, $M, $contentW, $y, 'Date:', rp_txt($record['inspection_date']), $darkGray);
    $y = rp_label_row($pdf, $M, $contentW, $y, 'RE:', rp_txt($record['re_field']), $darkGray);
    $y = rp_label_row($pdf, $M, $contentW, $y, 'Client:', rp_txt($record['client']), $darkGray);
    $y += 3;

    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', '', 10.5);
    $pdf->SetTextColor(...$darkGray);
    $pdf->Cell($contentW, 6, 'Client agrees to pay using one of the following methods:');
    $y += 8;

    $options = [
        1 => "Payment at time of inspection via card. Card payment is processed securely on-site using a card terminal \xE2\x80\x94 card number, expiration, and CVC are not collected or stored through this form.",
        2 => "Payment at time of inspection.",
        3 => "Within 10 business days from the date of the inspection/PR.",
        4 => "30 days from the date of the inspection/PR."
    ];
    foreach ($options as $num => $text) {
        $checked = ((int) $record['payment_option'] === $num);
        rp_checkbox($pdf, $M, $y, $checked, $darkGreen);
        $pdf->SetXY($M + 6, $y - 1);
        $pdf->SetFont('Arial', $checked ? 'B' : '', 9.7);
        $pdf->SetTextColor(...$darkGray);
        $pdf->MultiCell($contentW - 6, 4.7, rp_txt($num . '. ' . $text));
        $y = $pdf->GetY() + 2.5;
    }
    $pdf->SetXY($M + 6, $y);
    $pdf->SetFont('Arial', 'I', 8.7);
    $pdf->SetTextColor(...$footGray);
    $pdf->Cell($contentW - 6, 5, '*30 day pay will accrue an additional fee of $30.00 added to the cost of the inspection/PR.');
    $y += 8;

    $y = rp_label_row2($pdf, $M, $contentW, $y, 'Project mgr. / foreman:', rp_txt($record['pm_name']), 'Cell #:', rp_txt($record['pm_cell']), $darkGray);
    $y = rp_label_row($pdf, $M, $contentW, $y, 'Company name:', rp_txt($record['company_name']), $darkGray);
    $y = rp_label_row($pdf, $M, $contentW, $y, 'Company Phone and contact person:', rp_txt($record['company_contact']), $darkGray);
    $y += 4;

    // Signature line: clean horizontal rule, typed name + date beneath
    $pdf->SetDrawColor(150, 150, 150);
    $pdf->SetLineWidth(0.25);
    $pdf->Line($M, $y, $M + 85, $y);
    $pdf->Line($M + 95, $y, $M + $contentW, $y);
    $y += 4;
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', '', 10.5);
    $pdf->SetTextColor(...$darkGray);
    $pdf->Cell(85, 5, rp_txt($record['signature_name']) ?: ' ');
    $pdf->SetXY($M + 95, $y);
    $pdf->Cell($contentW - 95, 5, rp_txt($record['signature_date']) ?: ' ');
    $y += 5;
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'I', 8.5);
    $pdf->SetTextColor(...$footGray);
    $pdf->Cell(85, 4, 'Project manager / foreman signature (typed)');
    $pdf->SetXY($M + 95, $y);
    $pdf->Cell($contentW - 95, 4, 'Date');
    $y += 9;

    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', '', 10.5);
    $pdf->SetTextColor(...$darkGray);
    $pdf->Cell($contentW, 6, 'Signing this document ensures agreement of the terms listed above.');
    $y += 10;

    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell($contentW, 6, 'Regards,');
    $y += 8;
    if (file_exists($sigPath)) {
        $pdf->Image($sigPath, $M, $y, 42, 10.5);
    }
    $y += 13;
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'B', 10.5);
    $pdf->SetTextColor(...$darkGray);
    $pdf->Cell($contentW, 6, 'James Southerland');
    $y += 8;

    rp_footer_note($pdf, $M, $contentW, $y, 'Note: We do not store credit card information.', $footGray);

    $pdf->Output('F', $outputPath);
    return $outputPath;
}

/**
 * Maps the form's payment method + terms into one of the four
 * original agreement options (1-4).
 */
function rp_map_payment_option($record) {
    if (!empty($record['payment_option'])) return (int) $record['payment_option'];
    return 2;
}

/**
 * 2-3mm thick double-line green border, inset from the page edge,
 * leaving room for 20mm content margins.
 */
function rp_border2($pdf, $color, $W, $H) {
    $pdf->SetDrawColor(...$color);
    $pdf->SetLineWidth(1.0);
    $pdf->Rect(8, 8, $W - 16, $H - 16);
    $pdf->SetLineWidth(0.3);
    $pdf->Rect(10.5, 10.5, $W - 21, $H - 21);
}

/**
 * Draws the centered letterhead (company name + contact line) and
 * returns the Y position where page content should start.
 */
function rp_letterhead2($pdf, $M, $contentW, $darkGreen, $darkGray) {
    $headerTextColor = [169, 192, 154]; // #A9C09A
    $y = 20;
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'B', 19);
    $pdf->SetTextColor(...$headerTextColor);
    $pdf->Cell($contentW, 9, 'A & D INSPECTIONS, LLC', 0, 1, 'C');
    $y += 9;
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', '', 9.5);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell($contentW, 5, '16918 Rolling Acres Dr.  Humble, TX. 77396   |   281-802-0247   |   www.adinspections.com', 0, 1, 'C');
    $y += 7;
    $pdf->SetDrawColor(...$darkGreen);
    $pdf->SetLineWidth(0.4);
    $pdf->Line($M, $y, $M + $contentW, $y);
    $y += 7;
    return $y;
}

/**
 * A "Label:  value" row -- bold label, regular value, left-aligned,
 * for text fields (name, date, address-style short values).
 */
function rp_label_row($pdf, $M, $contentW, $y, $label, $value, $color) {
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(...$color);
    $labelW = $pdf->GetStringWidth($label) + 3;
    $pdf->Cell($labelW, 6.5, $label);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell($contentW - $labelW, 6.5, ' ' . $value);
    return $y + 7.5;
}

/**
 * Two label/value pairs on one row.
 */
function rp_label_row2($pdf, $M, $contentW, $y, $label1, $value1, $label2, $value2, $color) {
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(...$color);
    $l1w = $pdf->GetStringWidth($label1) + 3;
    $pdf->Cell($l1w, 6.5, $label1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(60, 6.5, ' ' . $value1);
    $pdf->SetFont('Arial', 'B', 11);
    $l2w = $pdf->GetStringWidth($label2) + 3;
    $pdf->Cell($l2w, 6.5, $label2);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell($contentW - $l1w - 60 - $l2w, 6.5, ' ' . $value2);
    return $y + 7.5;
}

/**
 * A vector checkbox: empty outlined square, or a filled square with
 * a white checkmark when checked. Used instead of Unicode checkbox
 * glyphs, since FPDF's core fonts (Windows-1252) can't render them.
 */
function rp_checkbox($pdf, $x, $y, $checked, $color) {
    $size = 5;
    if ($checked) {
        $pdf->SetFillColor(...$color);
        $pdf->Rect($x, $y, $size, $size, 'F');
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->SetLineWidth(0.6);
        $pdf->Line($x + 1, $y + 2.6, $x + 2.1, $y + 3.8);
        $pdf->Line($x + 2.1, $y + 3.8, $x + 4.1, $y + 1.1);
    } else {
        $pdf->SetDrawColor(140, 140, 140);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect($x, $y, $size, $size);
    }
    // Reset the draw color/width to the standard table-border gray so this
    // checkbox's color choice (especially the white checkmark stroke) can
    // never leak into the border of the next cell drawn after it.
    $pdf->SetDrawColor(130, 130, 130);
    $pdf->SetLineWidth(0.4);
}

/**
 * Underlined blank/value used for the IECC year fields.
 */
function rp_underline_value2($pdf, $value, $x, $y, $w, $color) {
    $pdf->SetXY($x, $y);
    $pdf->Cell($w, 6, $value, 0, 0, 'C');
    $pdf->SetDrawColor(120, 120, 120);
    $pdf->SetLineWidth(0.25);
    $pdf->Line($x + 1, $y + 5.5, $x + $w - 1, $y + 5.5);
}

/**
 * Italicized, smaller, gray footer note.
 */
function rp_footer_note($pdf, $M, $contentW, $y, $text, $color) {
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(...$color);
    $pdf->MultiCell($contentW, 4.3, $text);
}
