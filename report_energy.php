<?php
require_once 'auth.php';
require_login();
require_once 'config.php';
require_once 'report_render.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';
if ($id === '') {
    http_response_code(400);
    die('Missing report id.');
}

$stmt = mysqli_prepare($conn, "SELECT * FROM inspections WHERE id = ?");
mysqli_stmt_bind_param($stmt, 's', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$record = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$record) {
    http_response_code(404);
    die('Report not found.');
}

$record['areas'] = json_decode($record['areas'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inspection Report — <?php echo htmlspecialchars($record['client'], ENT_QUOTES, 'UTF-8'); ?></title>
<link rel="stylesheet" href="report.css">
</head>
<body>
<?php echo render_report_html($record, 'image/signature.png'); ?>

<div class="rp-sheet no-pagenum" style="max-width:760px;border-color:#999;">
  <label for="r-recipient" style="font-weight:bold;font-family:Arial,sans-serif;font-size:14px;display:block;margin-bottom:6px;">Send this report to (client email):</label>
  <input type="email" id="r-recipient" placeholder="client@example.com"
         value="<?php echo htmlspecialchars($record['recipient_email'], ENT_QUOTES, 'UTF-8'); ?>"
         style="width:100%;font-family:Arial,sans-serif;font-size:14px;padding:8px 10px;border:1px solid #999;box-sizing:border-box;">
  <p style="font-size:12px;color:#555;font-style:italic;margin:8px 0 0;">A copy will automatically be CC'd to A &amp; D Inspections.</p>
  <button id="r-send-btn" type="button"
          style="margin-top:14px;width:100%;padding:12px;border-radius:4px;font-family:Arial,sans-serif;font-weight:bold;font-size:15px;cursor:pointer;border:2px solid #2E7D32;background:#2E7D32;color:#fff;">
    Send Email
  </button>
  <p id="r-status" style="text-align:center;font-family:Arial,sans-serif;font-size:13px;font-weight:bold;min-height:18px;margin-top:10px;"></p>
</div>

<div style="text-align:center;margin-top:10px;">
  <a href="form_energy_inspection.php" style="color:#2E7D32;font-family:Arial,sans-serif;font-size:13px;">&larr; Back to form</a>
  &nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="reports_list_energy.php" style="color:#2E7D32;font-family:Arial,sans-serif;font-size:13px;">View previous reports</a>
</div>

<script>
document.getElementById('r-send-btn').addEventListener('click', async function(){
  var recipient = document.getElementById('r-recipient').value.trim();
  var statusEl = document.getElementById('r-status');
  if(!recipient || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(recipient)){
    statusEl.textContent = 'Enter a valid recipient email address.';
    statusEl.style.color = '#A6432F';
    return;
  }
  statusEl.textContent = 'Sending…';
  statusEl.style.color = '#555';
  try{
    var res = await fetch('api.php?action=email_by_id', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ id: <?php echo json_encode($id); ?>, recipientEmail: recipient })
    });
    var json = await res.json();
    if(res.ok && json.ok){
      statusEl.textContent = 'Report emailed to ' + recipient + '. A copy was CC\'d to you.';
      statusEl.style.color = '#2E7D32';
    } else {
      statusEl.textContent = json.error || 'Could not send the email.';
      statusEl.style.color = '#A6432F';
    }
  }catch(e){
    statusEl.textContent = 'Could not reach the server.';
    statusEl.style.color = '#A6432F';
  }
});
</script>
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
