<?php
/**
 * Generates the Property Inspection Report (MEP / Plumbing) as a real PDF,
 * matching the same letterhead/border/checkbox visual language as
 * pdf_generate.php. Plain procedural usage of FPDF (no OOP app code).
 *
 * Requires pdf_generate.php to already be loaded (for rp_txt, rp_border2,
 * rp_letterhead2, rp_label_row, rp_checkbox, rp_footer_note).
 */

/**
 * @param array $record  DB row from plumbing_inspections, with 'items' already json_decode'd
 * @param string $outputPath  Full filesystem path to write the PDF to
 */
function generate_plumbing_pdf($record, $outputPath) {
    $pdf = new FPDF('P', 'mm', 'Letter');
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(true, 18);
    $sigPath = __DIR__ . '/image/signature.png';

    $darkGreen = [45, 80, 22];
    $darkGray  = [51, 51, 51];
    $footGray  = [120, 120, 120];

    $W = 215.9; $H = 279.4;
    $M = 20;
    $contentW = $W - ($M * 2);

    $pdf->AddPage();
    pl_border_and_letterhead($pdf, $M, $contentW, $W, $H, $darkGreen, $darkGray);
    $y = 44;

    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'B', 15);
    $pdf->SetTextColor(...$darkGreen);
    $pdf->Cell($contentW, 8, 'Property Inspection Report', 0, 1, 'C');
    $y += 8;
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(...$footGray);
    $pdf->Cell($contentW, 5, 'Notice: This inspection report is subject to the attached contract and handouts.', 0, 1, 'C');
    $y += 9;

    if (!empty($record['report_title'])) {
        $y = rp_label_row($pdf, $M, $contentW, $y, 'Report Title:', rp_txt($record['report_title']), $darkGray);
    }
    $y = rp_label_row($pdf, $M, $contentW, $y, 'Prepared For:', rp_txt($record['client']), $darkGray);
    $y += 1;
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(...$darkGray);
    $pdf->Cell($contentW, 6, 'Concerning (Inspection Address):', 0, 1);
    $y += 6;
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.25);
    $addrLines = $pdf->SetFont('Arial', '', 11);
    $boxH = 12;
    $pdf->Rect($M, $y, $contentW, $boxH);
    $pdf->SetXY($M + 3, $y + 3.5);
    $pdf->Cell($contentW - 6, 5, rp_txt($record['inspection_address']));
    $y += $boxH + 5;

    $y = pl_label_row3(
        $pdf, $M, $contentW, $y,
        'Name:', rp_txt($record['inspector_name']),
        'License No.:', rp_txt($record['inspector_license']),
        'Date:', rp_txt($record['inspection_date']),
        $darkGray
    );
    $y += 2;

    // Inspection scope
    $scopeLabels = [];
    if (!empty($record['scope_plumbing']))   $scopeLabels[] = 'Plumbing';
    if (!empty($record['scope_electrical'])) $scopeLabels[] = 'Electrical';
    if (!empty($record['scope_hvac']))       $scopeLabels[] = 'HVAC';
    if (!empty($record['scope_other']))      $scopeLabels[] = 'Other' . (!empty($record['scope_other_text']) ? ' (' . $record['scope_other_text'] . ')' : '');
    $y = rp_label_row($pdf, $M, $contentW, $y, 'Inspection Scope:', rp_txt(implode(', ', $scopeLabels) ?: '-'), $darkGray);

    // Parties present
    $partyLabels = [];
    if (!empty($record['parties_superintendent'])) $partyLabels[] = 'Superintendent';
    if (!empty($record['parties_subcontractor']))   $partyLabels[] = 'Subcontractor';
    if (!empty($record['parties_other']))           $partyLabels[] = 'Other' . (!empty($record['parties_other_text']) ? ' (' . $record['parties_other_text'] . ')' : '');
    $y = rp_label_row($pdf, $M, $contentW, $y, 'Parties Present:', rp_txt(implode(', ', $partyLabels) ?: '-'), $darkGray);

    $y = rp_label_row2($pdf, $M, $contentW, $y, 'Weather:', rp_txt($record['weather']), 'Additional Info Provided:', rp_txt($record['additional_info']), $darkGray);
    $y = rp_label_row2($pdf, $M, $contentW, $y, 'Time of Inspection:', rp_txt($record['time_of_inspection']), 'Outside Temp:', rp_txt($record['outside_temp']), $darkGray);

    $y += 3;
    $pdf->SetDrawColor(...$darkGreen);
    $pdf->SetLineWidth(0.4);
    $pdf->Line($M, $y, $M + $contentW, $y);
    $y += 6;

    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(...$darkGreen);
    $pdf->Cell($contentW, 6, 'Inspection Items', 0, 1);
    $y += 6;
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'I', 8.5);
    $pdf->SetTextColor(...$footGray);
    $pdf->Cell($contentW, 4.5, 'I = Inspected     NI = Not Inspected     NP = Not Present     D = Deficient', 0, 1);
    $y += 7;

    $items = is_array($record['items']) ? $record['items'] : (json_decode($record['items'], true) ?: []);
    $statusCodes = ['I', 'NI', 'NP', 'D'];

    foreach ($items as $item) {
        // Page-break check before drawing a new item block
        if ($y > $H - 45) {
            $pdf->AddPage();
            pl_border_and_letterhead($pdf, $M, $contentW, $W, $H, $darkGreen, $darkGray);
            $y = 44;
        }

        $pdf->SetDrawColor(210, 210, 210);
        $pdf->SetLineWidth(0.2);
        $pdf->SetFillColor(247, 250, 247);
        $blockTop = $y;

        $pdf->SetXY($M, $y);
        $pdf->SetFont('Arial', 'B', 10.5);
        $pdf->SetTextColor(...$darkGray);
        $catLine = trim((isset($item['category']) ? $item['category'] : '') . '  ' . (isset($item['subcategory']) ? $item['subcategory'] : ''));
        $pdf->Cell($contentW - 60, 6, rp_txt($catLine !== '' ? $catLine : '-'));

        $statusX = $M + $contentW - 58;
        $sy = $y;
        foreach ($statusCodes as $i => $code) {
            $checked = (isset($item['status']) && $item['status'] === $code);
            $cx = $statusX + ($i * 15);
            rp_checkbox($pdf, $cx, $sy, $checked, $darkGreen);
            $pdf->SetXY($cx + 6, $sy - 0.5);
            $pdf->SetFont('Arial', $checked ? 'B' : '', 8.5);
            $pdf->SetTextColor(...$darkGray);
            $pdf->Cell(9, 5, $code);
        }
        $y += 7;

        $findings = isset($item['findings']) ? trim($item['findings']) : '';
        if ($findings !== '') {
            $pdf->SetXY($M + 3, $y);
            $pdf->SetFont('Arial', '', 9.5);
            $pdf->SetTextColor(60, 60, 60);
            $pdf->MultiCell($contentW - 6, 4.6, rp_txt($findings));
            $y = $pdf->GetY() + 2;
        } else {
            $y += 2;
        }

        $pdf->SetDrawColor(225, 225, 225);
        $pdf->SetLineWidth(0.2);
        $pdf->Line($M, $y, $M + $contentW, $y);
        $y += 5;
    }

    if (empty($items)) {
        $pdf->SetXY($M, $y);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->SetTextColor(...$footGray);
        $pdf->Cell($contentW, 6, 'No inspection items recorded.');
        $y += 8;
    }

    $y += 4;
    if ($y > $H - 40) {
        $pdf->AddPage();
        pl_border_and_letterhead($pdf, $M, $contentW, $W, $H, $darkGreen, $darkGray);
        $y = 44;
    }
    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', '', 10.5);
    $pdf->SetTextColor(...$darkGray);
    $pdf->Cell($contentW, 6, 'Inspector: ' . rp_txt($record['inspector_name']));
    $y += 6;
    if (file_exists($sigPath)) {
        $pdf->Image($sigPath, $M, $y, 32);
    }
    $y += 15;
    rp_footer_note($pdf, $M, $contentW, $y, 'This inspection report reflects conditions observed at the time of the site visit only.', $footGray);

    $pdf->Output('F', $outputPath);
    return $outputPath;
}

/**
 * Draws the standard border + centered letterhead for the plumbing
 * report's pages. Reuses rp_border2()/rp_letterhead2() from pdf_generate.php.
 */
function pl_border_and_letterhead($pdf, $M, $contentW, $W, $H, $darkGreen, $darkGray) {
    rp_border2($pdf, $darkGreen, $W, $H);
    rp_letterhead2($pdf, $M, $contentW, $darkGreen, $darkGray);
}

/**
 * Three label/value pairs on one row -- used for
 * "Name / License No. / Date" (matches the Word template's
 * "(Name and License Number of Inspector) ... (Date)" line).
 */
function pl_label_row3($pdf, $M, $contentW, $y, $label1, $value1, $label2, $value2, $label3, $value3, $color) {
    $colW = $contentW / 3;

    $pdf->SetXY($M, $y);
    $pdf->SetFont('Arial', 'B', 10.5);
    $pdf->SetTextColor(...$color);
    $l1w = $pdf->GetStringWidth($label1) + 2;
    $pdf->Cell($l1w, 6.5, $label1);
    $pdf->SetFont('Arial', '', 10.5);
    $pdf->Cell($colW - $l1w, 6.5, ' ' . $value1);

    $pdf->SetXY($M + $colW, $y);
    $pdf->SetFont('Arial', 'B', 10.5);
    $l2w = $pdf->GetStringWidth($label2) + 2;
    $pdf->Cell($l2w, 6.5, $label2);
    $pdf->SetFont('Arial', '', 10.5);
    $pdf->Cell($colW - $l2w, 6.5, ' ' . $value2);

    $pdf->SetXY($M + (2 * $colW), $y);
    $pdf->SetFont('Arial', 'B', 10.5);
    $l3w = $pdf->GetStringWidth($label3) + 2;
    $pdf->Cell($l3w, 6.5, $label3);
    $pdf->SetFont('Arial', '', 10.5);
    $pdf->Cell($contentW - (2 * $colW) - $l3w, 6.5, ' ' . $value3);

    return $y + 7.5;
}
