<?php
require_once 'auth.php';
require_login();
require_once 'config.php';

$result = mysqli_query($conn, "SELECT id, report_title, client, inspection_date, inspection_address, inspector_license, saved_at FROM plumbing_inspections ORDER BY saved_at DESC LIMIT 200");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Previous Property Inspection Reports — A &amp; D Inspections</title>
<link rel="stylesheet" href="report.css">
<style>
  .list-sheet{ max-width:760px; }
  .list-title{ font-family:Arial,sans-serif; font-size:20px; font-weight:bold; color:#2E7D32; margin:0 0 4px; text-align:center; }
  .list-sub{ text-align:center; font-family:Arial,sans-serif; font-size:13px; color:#555; margin:0 0 20px; }
  .report-row{ display:block; text-decoration:none; color:#222; border:1px solid #ddd; border-radius:4px; padding:12px 14px; margin-bottom:10px; font-family:Arial,sans-serif; }
  .report-row:hover{ border-color:#2E7D32; background:#F7FBF6; }
  .report-client{ font-weight:bold; font-size:15px; color:#2E7D32; }
  .report-date{ float:right; font-size:12.5px; color:#666; }
  .report-addr{ font-size:13px; color:#444; margin-top:3px; clear:both; }
  .empty{ text-align:center; font-family:Arial,sans-serif; color:#666; padding:30px 10px; }
</style>
</head>
<body>
<div class="rp-sheet list-sheet no-pagenum" style="border-color:#999;">
  <div class="list-title">Previous Property Inspection Reports</div>
  <div class="list-sub">Most recent first</div>

  <?php if (!$result || mysqli_num_rows($result) === 0): ?>
    <div class="empty">No reports have been saved yet.</div>
  <?php else: ?>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
      <a class="report-row" href="report_plumbing.php?id=<?php echo urlencode($row['id']); ?>">
        <span class="report-date"><?php echo htmlspecialchars($row['inspection_date'], ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="report-client"><?php echo htmlspecialchars($row['client'], ENT_QUOTES, 'UTF-8'); ?></span>
        <div class="report-addr"><?php echo htmlspecialchars($row['inspection_address'], ENT_QUOTES, 'UTF-8'); ?><?php if ($row['inspector_license']): ?> &middot; Lic # <?php echo htmlspecialchars($row['inspector_license'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></div>
      </a>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<div style="text-align:center;margin-top:6px;">
  <a href="form_plumbing_inspection.php" style="color:#2E7D32;font-family:Arial,sans-serif;font-size:13px;">&larr; Back to form</a>
  &nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="index.php" style="color:#2E7D32;font-family:Arial,sans-serif;font-size:13px;">All forms</a>
</div>
<script>
(function(){
  function ping(){
    fetch('heartbeat.php', { method: 'GET', cache: 'no-store' })
      .then(function(res){ if(res.status === 401){ window.location.href = 'login.php'; } })
      .catch(function(){});
  }
  ping();
  setInterval(ping, 8000);
})();
</script>
</body>
</html>
