<?php
require_once 'auth.php';
require_login();
require_once 'config.php';

// Recent-activity counts, shown as small hints on each form card.
$energyCount = 0;
$plumbingCount = 0;
$r1 = mysqli_query($conn, "SELECT COUNT(*) AS c FROM inspections");
if ($r1 && ($row = mysqli_fetch_assoc($r1))) $energyCount = (int) $row['c'];
$r2 = mysqli_query($conn, "SELECT COUNT(*) AS c FROM plumbing_inspections");
if ($r2 && ($row = mysqli_fetch_assoc($r2))) $plumbingCount = (int) $row['c'];

$forms = [
    [
        'title' => 'Final Energy Inspection Report',
        'desc'  => 'IECC final energy inspection, invoice, and payment agreement.',
        'href'  => 'form_energy_inspection.php',
        'count' => $energyCount,
    ],
    [
        'title' => 'Property Inspection Report (MEP / Plumbing)',
        'desc'  => 'Site inspection checklist for MEP items such as under-slab plumbing, with I/NI/NP/D findings.',
        'href'  => 'form_plumbing_inspection.php',
        'count' => $plumbingCount,
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
<title>Forms — A &amp; D Inspections</title>
<link rel="stylesheet" href="report.css">
<style>
  .dash-sheet{ max-width:760px; }
  .dash-title{ font-family:Arial,sans-serif; font-size:21px; font-weight:bold; color:#a9c09a; margin:0 0 4px; text-align:center; }
  .dash-sub{ text-align:center; font-family:Arial,sans-serif; font-size:13px; color:#555; margin:0 0 24px; }
  .form-card{ display:block; text-decoration:none; color:#222; border:1px solid #ddd; border-radius:6px; padding:18px 18px; margin-bottom:14px; font-family:Arial,sans-serif; transition:border-color .15s, background .15s; }
  .form-card:hover{ border-color:#2E7D32; background:#F7FBF6; }
  .form-card:hover .form-card-title{color:#2E7D32;}
  .form-card-title{ font-weight:bold; font-size:17px; color:#a9c09a; margin-bottom:4px; }
  .form-card-desc{ font-size:13.5px; color:#444; line-height:1.5; }
  .form-card-meta{ margin-top:10px; font-size:12px; color:#888; }
  .dash-logout{ text-align:center; margin-top:8px; }
  .dash-logout a{ color:#888; font-family:Arial,sans-serif; font-size:12.5px; text-decoration:none; }
  .dash-logout a:hover{ color:#2E7D32; }
  @media (max-width:600px){
    .form-card{ padding:15px 14px; }
  }
</style>
</head>
<body>
<div class="rp-sheet dash-sheet no-pagenum" style="border-color:#999;">
  <div class="dash-title">A &amp; D Inspections</div>
  <div class="dash-sub">Choose a form to fill out</div>

  <?php foreach ($forms as $f): ?>
    <a class="form-card" href="<?php echo htmlspecialchars($f['href'], ENT_QUOTES, 'UTF-8'); ?>">
      <div class="form-card-title"><?php echo htmlspecialchars($f['title'], ENT_QUOTES, 'UTF-8'); ?></div>
      <div class="form-card-desc"><?php echo htmlspecialchars($f['desc'], ENT_QUOTES, 'UTF-8'); ?></div>
      <div class="form-card-meta">
        <?php echo $f['count']; ?> saved report<?php echo $f['count'] === 1 ? '' : 's'; ?>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<div class="dash-logout"><a href="logout.php">Sign out</a></div>

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
