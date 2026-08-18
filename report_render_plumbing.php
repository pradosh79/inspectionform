<?php
/**
 * Renders a saved Property Inspection (MEP/Plumbing) record as HTML,
 * matching the on-screen form's design. Used both by report_plumbing.php
 * (the "view saved report" page) and as the emailed report body.
 *
 * @param array $r  Associative array of the record fields (see schema.sql), 'items' already decoded or JSON string
 * @return string   HTML markup
 */
function render_plumbing_report_html($r) {
    global $COMPANY_NAME, $COMPANY_ADDRESS, $COMPANY_PHONE, $COMPANY_EMAIL, $COMPANY_WEBSITE;
    $esc = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };

    $items = is_array($r['items']) ? $r['items'] : (json_decode($r['items'], true) ?: []);

    $scopeLabels = [];
    if (!empty($r['scope_plumbing']))   $scopeLabels[] = 'Plumbing';
    if (!empty($r['scope_electrical'])) $scopeLabels[] = 'Electrical';
    if (!empty($r['scope_hvac']))       $scopeLabels[] = 'HVAC';
    if (!empty($r['scope_other']))      $scopeLabels[] = 'Other' . (!empty($r['scope_other_text']) ? ' (' . $r['scope_other_text'] . ')' : '');

    $partyLabels = [];
    if (!empty($r['parties_superintendent'])) $partyLabels[] = 'Superintendent';
    if (!empty($r['parties_subcontractor']))   $partyLabels[] = 'Subcontractor';
    if (!empty($r['parties_other']))           $partyLabels[] = 'Other' . (!empty($r['parties_other_text']) ? ' (' . $r['parties_other_text'] . ')' : '');

    $itemsHtml = '';
    foreach ($items as $item) {
        $status = isset($item['status']) ? $item['status'] : '';
        $statusCells = '';
        foreach (['I', 'NI', 'NP', 'D'] as $code) {
            $mark = ($status === $code) ? '&#9679;' : '&#9675;';
            $statusCells .= '<span class="rp-radio">' . $mark . '</span> ' . $code . '&nbsp;&nbsp;';
        }
        $catLine = trim((isset($item['category']) ? $item['category'] : '') . '  ' . (isset($item['subcategory']) ? $item['subcategory'] : ''));
        $itemsHtml .=
            '<div style="border:1px solid #ddd;border-radius:4px;padding:10px 12px;margin:8px 0;">' .
                '<div style="font-weight:bold;">' . $esc($catLine !== '' ? $catLine : '-') . '</div>' .
                '<div style="margin:4px 0;font-size:13px;">' . $statusCells . '</div>' .
                (!empty($item['findings']) ? '<div style="font-size:13px;color:#444;margin-top:4px;">' . nl2br($esc($item['findings'])) . '</div>' : '') .
            '</div>';
    }
    if ($itemsHtml === '') {
        $itemsHtml = '<p style="color:#666;font-style:italic;">No inspection items recorded.</p>';
    }

    ob_start();
    ?>
    <div class="rp-sheet">
        <?php include __DIR__ . '/report_letterhead.php'; ?>
        <h2 class="rp-h2" style="text-align:center;color:#2E7D32;">Property Inspection Report</h2>
        <?php if (!empty($r['report_title'])): ?>
        <div class="rp-field"><span class="rp-label">Report Title:</span> <?php echo $esc($r['report_title']); ?></div>
        <?php endif; ?>
        <div class="rp-field"><span class="rp-label">Prepared For:</span> <?php echo $esc($r['client']); ?></div>
        <div class="rp-field"><span class="rp-label">Concerning (Inspection Address):</span></div>
        <div class="rp-box"><?php echo $esc($r['inspection_address']); ?></div>
        <div class="rp-row" style="margin-top:14px;">
            <div><span class="rp-label">Name:</span> <?php echo $esc($r['inspector_name']); ?></div>
            <div><span class="rp-label">License No.:</span> <?php echo $esc($r['inspector_license']); ?></div>
            <div><span class="rp-label">Date:</span> <?php echo $esc($r['inspection_date']); ?></div>
        </div>
        <div class="rp-field" style="margin-top:10px;"><span class="rp-label">Inspection Scope:</span> <?php echo $esc(implode(', ', $scopeLabels) ?: '-'); ?></div>
        <div class="rp-field"><span class="rp-label">Parties Present:</span> <?php echo $esc(implode(', ', $partyLabels) ?: '-'); ?></div>
        <div class="rp-row">
            <div><span class="rp-label">Weather:</span> <?php echo $esc($r['weather']); ?></div>
            <div><span class="rp-label">Additional Info Provided:</span> <?php echo $esc($r['additional_info']); ?></div>
        </div>
        <div class="rp-row">
            <div><span class="rp-label">Time of Inspection:</span> <?php echo $esc($r['time_of_inspection']); ?></div>
            <div><span class="rp-label">Outside Temp:</span> <?php echo $esc($r['outside_temp']); ?></div>
        </div>

        <h2 class="rp-h2" style="margin-top:20px;">Inspection Items</h2>
        <div class="legend-row" style="font-size:12.5px;color:#555;font-style:italic;margin-bottom:10px;">I = Inspected &nbsp; NI = Not Inspected &nbsp; NP = Not Present &nbsp; D = Deficient</div>
        <?php echo $itemsHtml; ?>

        <p style="margin-top:22px;">Inspector: <?php echo $esc($r['inspector_name']); ?></p>
        <img src="image/signature.png" alt="signature" style="height:44px;margin:4px 0;">
        <p class="rp-note">This inspection report reflects conditions observed at the time of the site visit only.</p>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Compact HTML summary used as the emailed report's body (the PDF is
 * attached separately, so this is a short courtesy summary, not the
 * full report).
 *
 * @param array $r  Record with 'items' already decoded or JSON string
 * @return string   HTML markup
 */
function render_plumbing_email_summary_html($r) {
    global $COMPANY_NAME, $COMPANY_ADDRESS, $COMPANY_PHONE, $COMPANY_EMAIL, $COMPANY_WEBSITE;
    $esc = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };

    $items = is_array($r['items']) ? $r['items'] : (json_decode($r['items'], true) ?: []);
    $deficientCount = 0;
    foreach ($items as $item) {
        if (isset($item['status']) && $item['status'] === 'D') $deficientCount++;
    }

    ob_start();
    ?>
    <div style="font-family:Arial,Helvetica,sans-serif;color:#222;max-width:640px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:18px;">
            <div style="font-size:20px;font-weight:bold;color:#2E7D32;"><?php echo $esc($COMPANY_NAME); ?></div>
            <div style="font-size:12px;color:#555;"><?php echo $esc($COMPANY_ADDRESS); ?> &nbsp;|&nbsp; <?php echo $esc($COMPANY_PHONE); ?></div>
        </div>
        <p>Hi,</p>
        <p>Please find attached the Property Inspection Report for <strong><?php echo $esc($r['client']); ?></strong>, dated <?php echo $esc($r['inspection_date']); ?>.</p>
        <table style="width:100%;border-collapse:collapse;font-size:13px;margin:14px 0;">
            <?php if (!empty($r['report_title'])): ?>
            <tr><td style="padding:4px 0;color:#666;">Report Title</td><td style="padding:4px 0;"><?php echo $esc($r['report_title']); ?></td></tr>
            <?php endif; ?>
            <tr><td style="padding:4px 0;color:#666;">Address</td><td style="padding:4px 0;"><?php echo $esc($r['inspection_address']); ?></td></tr>
            <tr><td style="padding:4px 0;color:#666;">Inspector</td><td style="padding:4px 0;"><?php echo $esc($r['inspector_name']); ?><?php if (!empty($r['inspector_license'])): ?> (License No. <?php echo $esc($r['inspector_license']); ?>)<?php endif; ?></td></tr>
            <tr><td style="padding:4px 0;color:#666;">Items inspected</td><td style="padding:4px 0;"><?php echo count($items); ?><?php if ($deficientCount > 0): ?> (<span style="color:#A6432F;font-weight:bold;"><?php echo $deficientCount; ?> deficient</span>)<?php endif; ?></td></tr>
        </table>
        <p style="font-size:13px;color:#555;">The full report, including all inspection items and notes, is attached as a PDF.</p>
        <p style="margin-top:20px;">Regards,<br><?php echo $esc($COMPANY_NAME); ?></p>
    </div>
    <?php
    return ob_get_clean();
}
