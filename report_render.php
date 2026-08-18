<?php
/**
 * Renders a saved inspection record as HTML matching the original
 * PDF's 3-page layout. Used both for the "Show Report" page and
 * for the emailed report body.
 *
 * @param array $r  Associative array of the record fields (see schema.sql)
 * @return string   HTML markup
 */
function render_report_html($r, $signatureSrc = 'cid:signature') {
    global $COMPANY_NAME, $COMPANY_ADDRESS, $COMPANY_PHONE, $COMPANY_EMAIL, $COMPANY_WEBSITE;
    $esc = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };

    $areas = is_array($r['areas']) ? $r['areas'] : (json_decode($r['areas'], true) ?: []);
    $areasHtml = '';
    foreach ($areas as $a) {
        $status = isset($a['status']) ? $a['status'] : '';
        $passMark = $status === 'pass' ? '&#10003;' : '';
        $failMark = $status === 'fail' ? '&#10003;' : '';
        $areasHtml .=
            '<div class="rp-area-row">' .
                '<div class="rp-area-name">' . $esc(isset($a['name']) ? $a['name'] : '') . '</div>' .
                '<div class="rp-area-check">' . $passMark . '</div>' .
                '<div class="rp-area-check">' . $failMark . '</div>' .
            '</div>';
    }
    if ($areasHtml === '') {
        $areasHtml = '<div class="rp-area-row"><div class="rp-area-name">&mdash;</div><div class="rp-area-check"></div><div class="rp-area-check"></div></div>';
    }

    $methods = ['Check', 'Cash', 'Credit Card'];
    $methodHtml = '';
    foreach ($methods as $m) {
        $mark = ($r['payment_method'] === $m) ? '&#9679;' : '&#9675;';
        $methodHtml .= '<span class="rp-radio">' . $mark . ' ' . $esc($m) . '</span>&nbsp;&nbsp;&nbsp;';
    }

    $optionText = [
        1 => 'Payment at time of inspection via card. Card payment is processed securely on-site using a card terminal — card number, expiration, and CVC are not collected or stored through this form.',
        2 => 'Payment at time of inspection.',
        3 => 'Within 10 business days from the date of the inspection/PR.',
        4 => '30 days from the date of the inspection/PR.'
    ];
    $optionsHtml = '';
    foreach ($optionText as $num => $text) {
        $mark = ((int) $r['payment_option'] === $num) ? '&#9679;' : '&#9675;';
        $optionsHtml .= '<div class="rp-option"><span class="rp-radio">' . $mark . '</span> ' . $num . '. ' . $esc($text) . '</div>';
    }

    ob_start();
    ?>
    <div class="rp-sheet">
        <?php include __DIR__ . '/report_letterhead.php'; ?>
        <div class="rp-field"><span class="rp-label">Client:</span> <?php echo $esc($r['client']); ?></div>
        <div class="rp-field"><span class="rp-label">Date:</span> <?php echo $esc($r['inspection_date']); ?></div>
        <div class="rp-field"><span class="rp-label">Report #</span> <?php echo $esc($r['report_number']); ?></div>
        <div class="rp-field"><span class="rp-label">Inspection Address:</span></div>
        <div class="rp-box"><?php echo $esc($r['address']); ?></div>

        <div class="rp-row" style="margin-top:14px;">
            <div><span class="rp-underline"><?php echo $esc($r['iecc_year']); ?></span> <strong>IECC Inspection</strong></div>
            <div><strong>Scope of work:</strong> Final Energy Inspection</div>
        </div>
        <p>This building meets the requirements of the IECC <span class="rp-underline"><?php echo $esc($r['iecc_year2']); ?></span>.</p>

        <div class="rp-area-header">
            <div class="rp-area-name"><strong>Areas inspected:</strong></div>
            <div class="rp-area-check"><strong>Passed</strong></div>
            <div class="rp-area-check"><strong>Failed</strong></div>
        </div>
        <?php echo $areasHtml; ?>

        <p style="margin-top:22px;">Inspector: James Southerland</p>
        <img src="<?php echo $esc($signatureSrc); ?>" alt="signature" style="height:44px;margin:4px 0;">
        <p>0882584<br>
        ICC Residential Combination Inspector<br>
        International Energy Conservation Code Certified<br>
        ICC Certified: Accessibility Inspector/Plans Examiner</p>
        <p class="rp-note"><strong>NOTE:</strong> This inspection includes IECC energy code items only. This inspection does not include structural, MEP, or accessibility items covered by other applicable codes.</p>
    </div>

    <div class="rp-sheet">
        <?php include __DIR__ . '/report_letterhead.php'; ?>
        <h2 class="rp-h2">Invoice</h2>
        <p>Payment is due at the time of service.</p>
        <div class="rp-field"><span class="rp-label">Client:</span> <?php echo $esc($r['client']); ?></div>
        <div class="rp-field"><span class="rp-label">Date:</span> <?php echo $esc($r['inspection_date']); ?></div>
        <div class="rp-field"><span class="rp-label">Report #</span> <?php echo $esc($r['report_number']); ?></div>
        <div class="rp-field"><span class="rp-label">Inspection Address:</span> <?php echo $esc($r['address']); ?></div>
        <div class="rp-field"><span class="rp-label">Scope of Work:</span> Final Energy Inspection</div>
        <div class="rp-field"><span class="rp-label">Fee:</span> $<?php echo $esc(number_format((float) $r['fee'], 2)); ?></div>
        <div class="rp-field"><span class="rp-label">Payment Method:</span> <?php echo $methodHtml; ?></div>
        <p style="margin-top:18px;">Thank you for your business</p>
        <p class="rp-note">Note: We do not store credit card information.</p>
    </div>

    <div class="rp-sheet">
        <?php include __DIR__ . '/report_letterhead.php'; ?>
        <h2 class="rp-h2">Payment Method / Agreement</h2>
        <div class="rp-field"><span class="rp-label">Date:</span> <?php echo $esc($r['inspection_date']); ?></div>
        <div class="rp-field"><span class="rp-label">RE:</span> <?php echo $esc($r['re_field']); ?></div>
        <div class="rp-field"><span class="rp-label">Client:</span> <?php echo $esc($r['client']); ?></div>
        <p>Client agrees to pay using one of the following methods:</p>
        <?php echo $optionsHtml; ?>
        <p class="rp-note">*30 day pay will accrue an additional fee of $30.00 added to the cost of the inspection/PR.</p>

        <div class="rp-field"><span class="rp-label">Project mgr. / foreman:</span> <?php echo $esc($r['pm_name']); ?> &nbsp;&nbsp; <span class="rp-label">Cell #:</span> <?php echo $esc($r['pm_cell']); ?></div>
        <div class="rp-field"><span class="rp-label">Company name:</span> <?php echo $esc($r['company_name']); ?></div>
        <div class="rp-field"><span class="rp-label">Company Phone and contact person:</span> <?php echo $esc($r['company_contact']); ?></div>
        <div class="rp-field"><span class="rp-label">Project manager / foreman signature (typed):</span> <?php echo $esc($r['signature_name']); ?> &nbsp;&nbsp; <span class="rp-label">Date:</span> <?php echo $esc($r['signature_date']); ?></div>

        <p style="margin-top:14px;">Signing this document ensures agreement of the terms listed above.</p>
        <p style="margin-top:18px;">Regards,</p>
        <img src="<?php echo $esc($signatureSrc); ?>" alt="signature" style="height:44px;margin:4px 0;">
        <p>James Southerland</p>
        <p class="rp-note">Note: We do not store credit card information.</p>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renders a short, professional email BODY (not the full 3-page report --
 * that's in the attached PDF). Built with inline styles and table layout
 * only, since email clients (Gmail, Outlook, Apple Mail) strip out
 * <style> blocks and don't reliably support flexbox/grid.
 *
 * @param array $r  Associative array of the record fields
 * @param string $signatureSrc  'cid:signature' for email, or a file path for preview
 * @return string HTML markup
 */
function render_email_summary_html($r, $signatureSrc = 'cid:signature', $logoSrc = 'cid:logo', $greetingOverride = null, $internalNote = null) {
    global $COMPANY_NAME, $COMPANY_ADDRESS, $COMPANY_PHONE, $COMPANY_EMAIL, $COMPANY_WEBSITE;
    $esc = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };

    $areas = is_array($r['areas']) ? $r['areas'] : (json_decode($r['areas'], true) ?: []);
    $passCount = 0;
    $failCount = 0;
    foreach ($areas as $a) {
        if (isset($a['status']) && $a['status'] === 'pass') $passCount++;
        if (isset($a['status']) && $a['status'] === 'fail') $failCount++;
    }
    $overallPass = $failCount === 0 && $passCount > 0;

    $firstName = trim(explode(' ', trim((string) $r['client']))[0]);
    if ($firstName === '') $firstName = 'there';
    $greeting = $greetingOverride !== null ? $greetingOverride : ('Hi ' . $firstName . ',');

    $green = '#2E7D32';
    $ink = '#2C2A25';
    $inkSoft = '#6B6459';
    $paper = '#F7F3EA';
    $line = '#E3DCC9';

    ob_start();
    ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:<?php echo $paper; ?>;padding:24px 0;font-family:Arial,Helvetica,sans-serif;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border:1px solid <?php echo $line; ?>;border-radius:6px;overflow:hidden;">

  <!-- Header -->
  <tr><td style="background:#ffffff;padding:22px 28px 16px;text-align:center;border-bottom:4px solid <?php echo $green; ?>;">
    <div style="color:#A9C09A;font-size:19px;font-weight:bold;letter-spacing:0.3px;"><?php echo $esc($COMPANY_NAME); ?></div>
    <div style="color:<?php echo $inkSoft; ?>;font-size:11.5px;margin-top:4px;"><?php echo $esc($COMPANY_ADDRESS); ?></div>
    <div style="color:<?php echo $inkSoft; ?>;font-size:11.5px;"><?php echo $esc($COMPANY_PHONE); ?> &nbsp;&middot;&nbsp; <?php echo $esc($COMPANY_EMAIL); ?></div>
  </td></tr>

  <!-- Greeting -->
  <tr><td style="padding:28px 32px 4px;">
    <div style="font-size:15px;color:<?php echo $ink; ?>;"><?php echo $esc($greeting); ?></div>
    <?php if ($internalNote): ?>
    <p style="font-size:12.5px;color:<?php echo $inkSoft; ?>;line-height:1.5;margin:8px 0 0;font-style:italic;"><?php echo $esc($internalNote); ?></p>
    <?php endif; ?>
    <p style="font-size:14px;color:<?php echo $ink; ?>;line-height:1.6;margin:12px 0 0;">
      Thank you for choosing A &amp; D Inspections. Your Final Energy Inspection Report, Invoice, and Payment Agreement are attached as a PDF to this email.
    </p>
  </td></tr>

  <!-- Status banner -->
  <tr><td style="padding:18px 32px 0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:<?php echo $overallPass ? '#E9F3E9' : '#FBF0EC'; ?>;border-radius:5px;">
      <tr><td style="padding:12px 16px;font-size:13.5px;font-weight:bold;color:<?php echo $overallPass ? $green : '#A6432F'; ?>;">
        <?php echo $overallPass
          ? '&#10003; This building meets the requirements of the IECC ' . $esc($r['iecc_year2'])
          : ($failCount > 0 ? $failCount . ' area(s) require attention -- see attached report for details' : 'Inspection recorded -- see attached report for details'); ?>
      </td></tr>
    </table>
  </td></tr>

  <!-- Summary table -->
  <tr><td style="padding:20px 32px 4px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13.5px;color:<?php echo $ink; ?>;border-collapse:collapse;">
      <tr>
        <td style="padding:7px 0;border-bottom:1px solid <?php echo $line; ?>;color:<?php echo $inkSoft; ?>;width:140px;">Report #</td>
        <td style="padding:7px 0;border-bottom:1px solid <?php echo $line; ?>;"><?php echo $esc($r['report_number'] ?: '—'); ?></td>
      </tr>
      <tr>
        <td style="padding:7px 0;border-bottom:1px solid <?php echo $line; ?>;color:<?php echo $inkSoft; ?>;">Date</td>
        <td style="padding:7px 0;border-bottom:1px solid <?php echo $line; ?>;"><?php echo $esc($r['inspection_date']); ?></td>
      </tr>
      <tr>
        <td style="padding:7px 0;border-bottom:1px solid <?php echo $line; ?>;color:<?php echo $inkSoft; ?>;">Property</td>
        <td style="padding:7px 0;border-bottom:1px solid <?php echo $line; ?>;"><?php echo $esc($r['address']); ?></td>
      </tr>
      <tr>
        <td style="padding:7px 0;border-bottom:1px solid <?php echo $line; ?>;color:<?php echo $inkSoft; ?>;">Areas inspected</td>
        <td style="padding:7px 0;border-bottom:1px solid <?php echo $line; ?>;"><?php echo $passCount; ?> passed<?php echo $failCount ? ', ' . $failCount . ' failed' : ''; ?></td>
      </tr>
      <tr>
        <td style="padding:7px 0;border-bottom:1px solid <?php echo $line; ?>;color:<?php echo $inkSoft; ?>;">Fee</td>
        <td style="padding:7px 0;border-bottom:1px solid <?php echo $line; ?>;">$<?php echo $esc(number_format((float) $r['fee'], 2)); ?> &nbsp;(<?php echo $esc($r['payment_method'] ?: 'method not set'); ?>)</td>
      </tr>
    </table>
  </td></tr>

  <!-- Attachment note -->
  <tr><td style="padding:18px 32px 0;">
    <p style="font-size:12.5px;color:<?php echo $inkSoft; ?>;margin:0;">📎 The attached PDF includes the full inspection checklist, invoice, and signed payment agreement.</p>
  </td></tr>

  <!-- Sign-off -->
  <tr><td style="padding:26px 32px 8px;">
    <div style="font-size:14px;color:<?php echo $ink; ?>;">Regards,</div>
    <img src="<?php echo $esc($signatureSrc); ?>" alt="James Southerland signature" style="height:40px;margin:6px 0 4px;display:block;">
    <div style="font-size:13.5px;color:<?php echo $ink; ?>;font-weight:bold;">James Southerland</div>
    <div style="font-size:11.5px;color:<?php echo $inkSoft; ?>;line-height:1.5;margin-top:2px;">
      ICC Residential Combination Inspector<br>
      International Energy Conservation Code Certified<br>
      ICC Certified: Accessibility Inspector/Plans Examiner
    </div>
  </td></tr>

  <!-- Footer -->
  <tr><td style="padding:18px 32px 24px;border-top:1px solid <?php echo $line; ?>;margin-top:10px;">
    <p style="font-size:11px;color:<?php echo $inkSoft; ?>;margin:14px 0 0;">
      This inspection includes IECC energy code items only and does not include structural, MEP, or accessibility items covered by other applicable codes.
    </p>
    <p style="font-size:11px;color:<?php echo $inkSoft; ?>;margin:8px 0 0;">
      <?php echo $esc($COMPANY_NAME); ?> &middot; <?php echo $esc($COMPANY_ADDRESS); ?><br>
      <a href="<?php echo $esc($COMPANY_WEBSITE); ?>" style="color:<?php echo $green; ?>;text-decoration:none;"><?php echo $esc($COMPANY_WEBSITE); ?></a>
    </p>
  </td></tr>

</table>
</td></tr>
</table>
    <?php
    return ob_get_clean();
}
