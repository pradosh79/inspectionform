<?php
require_once 'auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
<title>A &amp; D Inspections — Final Energy Inspection</title>
<link rel="stylesheet" href="report.css">
<style>
  input[type=text], input[type=date], input[type=number], input[type=email]{
    width:100%;
    font-family:Arial, Helvetica, sans-serif;
    font-size:14px;
    padding:7px 8px;
    border:1px solid #999;
    background:#FDF3EC;
    box-sizing:border-box;
  }
  textarea{
    width:100%;
    font-family:Arial, Helvetica, sans-serif;
    font-size:14px;
    padding:7px 8px;
    border:1px solid #999;
    background:#FDF3EC;
    box-sizing:border-box;
    resize:vertical;
  }
  .customclass{display:flex;gap:8px;align-items:center;}
  .f-row, .f-row-inline{ margin:10px 0; font-size:14px; }
  .f-row label, .f-row-inline label{ display:block; font-weight:bold; margin-bottom:4px; }
  .custom input[type="text"]{width:initial;}
  .mirror-value{ color:#333; }
  .mirror-value.empty{ color:#999; font-style:italic; }
  .f-row-inline{ display:flex; gap:14px; flex-wrap:wrap; align-items:center;justify-content:space-between; }
  .f-row-inline > div{ flex:1; min-width:140px; }
  .inline-year{ display:inline-block; width:60px; margin:0 4px; }
  .area-row{ display:grid; grid-template-columns:1fr 70px 70px; gap:8px; align-items:center; padding:6px 0; }
  .area-row input[type=text]{ padding:6px 8px; }
  .area-check{ text-align:center; }
  .area-check input{ width:18px; height:18px; }
  .chip-row{ display:flex; gap:16px; flex-wrap:wrap; margin-top:6px; }
  .chip-row label{ font-weight:normal; display:flex; align-items:center; gap:6px; font-size:14px; }
  .option-block{ margin:10px 0; padding:8px 0; border-bottom:1px solid #eee; }
  .option-block label p{line-height:41px;}
  .option-block label{ font-weight:normal; display:flex; gap:8px; align-items:flex-start; font-size:13.5px; }
  .option-block input[type=radio]{ margin-top:3px; flex-shrink:0; }
  .actions{ max-width:760px; margin:0 auto 40px; padding:0 4px; display:flex; gap:12px; flex-wrap:wrap; }
  .btn{ flex:1; min-width:160px; padding:13px; border-radius:4px; font-family:Arial,sans-serif; font-weight:bold; font-size:15px; cursor:pointer; border:2px solid #2E7D32; }
  .btn-primary{ background:#2E7D32; color:#fff; }
  .btn-secondary{ background:#fff; color:#2E7D32; }
  .status-msg{ max-width:760px; margin:0 auto 20px; text-align:center; font-size:13px; font-weight:bold; min-height:18px; }
  .status-msg.ok{ color:#2E7D32; }
  .status-msg.error{ color:#A6432F; }
  @media (max-width:600px){
    .area-row{ grid-template-columns:1fr 46px 46px; }
  }
</style>
</head>
<body>

<div class="rp-sheet">
  <div class="rp-letterhead">
    <div class="rp-company">A &amp; D INSPECTIONS, LLC</div>
    <div class="rp-companyline">16918 Rolling Acres Dr.&nbsp; Humble, TX. 77396</div>
    <div class="rp-companyline">281-802-0247 &nbsp; james@adinspections.com</div>
    <div class="rp-companyline">www.adinspections.com</div>
  </div>

  <div class="f-row"><label for="f-client">Client:</label><input type="text" id="f-client"></div>
  <div class="f-row-inline">
    <div><label for="f-date">Date:</label><input type="date" id="f-date"></div>
    <div><label for="f-report">Report #</label><input type="text" id="f-report"></div>
  </div>
  <div class="f-row"><label for="f-address">Inspection Address:</label><textarea id="f-address" rows="2"></textarea></div>

  <div class="f-row-inline custom" style="margin-top:18px;">
    <div style="flex:0 0 auto;">
      <label>&nbsp;</label>
      <div><input type="text" id="f-iecc-year" class="inline-year" style="width:80px;"> <strong>IECC Inspection</strong></div>
    </div>
    <div><label>&nbsp;</label><div><strong>Scope of work:</strong> Final Energy Inspection</div></div>
  </div>
  <div class="f-row custom">This building meets the requirements of the IECC <input type="text" id="f-iecc-year2" class="inline-year">.</div>

  <div class="f-row" style="margin-top:16px;">
    <div class="rp-area-header"><div><strong>Areas inspected:</strong></div><div style="text-align:center;"><strong>Passed</strong></div><div style="text-align:center;"><strong>Failed</strong></div></div>
    <div id="area-list"></div>
    <button type="button" id="add-area-btn" style="margin-top:8px;width:100%;padding:9px;border:1.5px dashed #999;background:none;color:#2E7D32;font-family:Arial,sans-serif;font-weight:bold;font-size:13.5px;border-radius:4px;cursor:pointer;">+ Add area</button>
  </div>

  <p style="margin-top:24px;">Inspector: James Southerland</p>
  <img src="image/signature.png" alt="signature" style="height:44px;margin:4px 0;">
  <p>0882584<br>ICC Residential Combination Inspector<br>International Energy Conservation Code Certified<br>ICC Certified: Accessibility Inspector/Plans Examiner</p>
  <p class="rp-note"><strong>NOTE:</strong> This inspection includes IECC energy code items only. This inspection does not include structural, MEP, or accessibility items covered by other applicable codes.</p>
</div>

<div class="rp-sheet">
  <div class="rp-letterhead">
    <div class="rp-company">A &amp; D INSPECTIONS, LLC</div>
    <div class="rp-companyline">16918 Rolling Acres Dr.&nbsp; Humble, TX. 77396</div>
    <div class="rp-companyline">281-802-0247 &nbsp; james@adinspections.com</div>
    <div class="rp-companyline">www.adinspections.com</div>
  </div>
  <h2 class="rp-h2">Invoice</h2>
  <p>Payment is due at the time of service.</p>
  <div class="f-row"><span class="rp-label">Client:</span> <span id="inv-client" class="mirror-value">—</span></div>
  <div class="f-row"><span class="rp-label">Date:</span> <span id="inv-date" class="mirror-value">—</span></div>
  <div class="f-row"><span class="rp-label">Report #</span> <span id="inv-report" class="mirror-value">—</span></div>
  <div class="f-row"><span class="rp-label">Inspection Address:</span> <span id="inv-address" class="mirror-value">—</span></div>
  <div class="f-row"><span class="rp-label">Scope of Work:</span> Final Energy Inspection</div>
  <div class="f-row customclass"><label for="f-fee">Fee:</label><input type="number" id="f-fee" inputmode="decimal" placeholder="0.00"></div>
  <div class="f-row">
    <label>Payment Method:</label>
    <div class="chip-row">
      <label><input type="radio" name="payMethod" value="Check"> Check</label>
      <label><input type="radio" name="payMethod" value="Cash"> Cash</label>
      <label><input type="radio" name="payMethod" value="Credit Card"> Credit card</label>
    </div>
  </div>
  <p class="rp-note">Note: We do not store credit card information.</p>
</div>

<div class="rp-sheet">
  <div class="rp-letterhead">
    <div class="rp-company">A &amp; D INSPECTIONS, LLC</div>
    <div class="rp-companyline">16918 Rolling Acres Dr.&nbsp; Humble, TX. 77396</div>
    <div class="rp-companyline">281-802-0247 &nbsp; james@adinspections.com</div>
    <div class="rp-companyline">www.adinspections.com</div>
  </div>
  <h2 class="rp-h2">Payment Method / Agreement</h2>
  <div class="f-row"><span class="rp-label">Date:</span> <span id="agr-date" class="mirror-value">—</span></div>
  <div class="f-row customclass"><label for="f-re">RE:</label><input type="text" id="f-re"></div>
  <div class="f-row"><span class="rp-label">Client:</span> <span id="agr-client" class="mirror-value">—</span></div>
  <p>Client,  <input type="text" id="f-client" style="width:220px;"> agrees to pay using one of the following methods:</p>

  <div class="option-block"><label>
      <p>
        <input type="radio" id="payment">

            <strong>1. Payment at the time of the inspection/PR.</strong>

    
        &nbsp;&nbsp;CC#:
        <input type="text" name="card_number" style="width:220px;">
    
        &nbsp;&nbsp;Exp:
        <input type="text" name="date" style="width:70px;">
    
        &nbsp;&nbsp;CVV:
        <input type="text" name="cvv" style="width:60px;" maxlength="3">
    
        &nbsp;&nbsp;Zip Code:
        <input type="text" name="pin" style="width:80px;">
        
        &nbsp;&nbsp;Address:
        <input type="text" name="address" style="width:280px;">
    </p>
  </label></div>
  <div class="option-block"><label><input type="radio" name="payOption" value="2"> 2. Payment at time of inspection.</label></div>
  <div class="option-block"><label><input type="radio" name="payOption" value="3"> 3. Within 10 business days from the date of the inspection/PR.</label></div>
  <div class="option-block"><label><input type="radio" name="payOption" value="4"> 4. 30 days from the date of the inspection/PR.</label></div>
  <p class="rp-note">*30 day pay will accrue an additional fee of $30.00 added to the cost of the inspection/PR.</p>

  <div class="f-row-inline">
    <div><label for="f-pmname">Project mgr. / foreman:</label><input type="text" id="f-pmname"></div>
    <div><label for="f-pmcell">Cell #:</label><input type="text" id="f-pmcell"></div>
  </div>
  <div class="f-row"><label for="f-companyname">Company name:</label><input type="text" id="f-companyname"></div>
  <div class="f-row"><label for="f-companycontact">Company Phone and contact person:</label><input type="text" id="f-companycontact"></div>
  <div class="f-row-inline">
    <div><label for="f-signature">Project manager / foreman signature (typed):</label><input type="text" id="f-signature"></div>
    <div><label for="f-signature-date">Date:</label><input type="date" id="f-signature-date"></div>
  </div>
  <p>Signing this document ensures agreement of the terms listed above.</p>
  <p style="margin-top:16px;">Regards,</p>
  <img src="image/signature.png" alt="signature" style="height:44px;margin:4px 0;">
  <p>James Southerland</p>
  <p class="rp-note">Note: We do not store credit card information.</p>
</div>

<div class="rp-sheet no-pagenum" style="border-color:#999;">
  <div class="f-row"><label for="f-recipient">Send report to (client email):</label><input type="email" id="f-recipient" placeholder="client@example.com"></div>
  <p class="rp-note" style="font-style:normal;">A copy will automatically be CC'd to A &amp; D Inspections.</p>
</div>

<div class="actions">
  <button class="btn btn-secondary" id="download-btn" type="button">Download as PDF</button>
  <button class="btn btn-primary" id="share-btn" type="button">Share with Email</button>
</div>
<p class="status-msg" id="status-msg"></p>

<div style="text-align:center;margin-bottom:40px;">
  <a href="reports_list_energy.php" style="color:#2E7D32;font-family:Arial,sans-serif;font-size:13px;">View previous reports &rarr;</a>
  &nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="index.php" style="color:#2E7D32;font-family:Arial,sans-serif;font-size:13px;">&larr; All forms</a>
</div>

<script>
(function(){
  var DEFAULT_AREAS = ['Attic insulation','Wall insulation','Air sealing / infiltration','Duct testing','Window U-factor / SHGC'];
  var currentRecordId = null; // set after the first successful save, so later clicks UPDATE this same record instead of inserting a new one

  function makeAreaRow(name){
    var wrap = document.createElement('div');
    wrap.className = 'area-row';
    wrap.style.gridTemplateColumns = '1fr 70px 70px 26px';
    wrap.innerHTML =
      '<input type="text" class="area-name" value="' + (name || '') + '" placeholder="Area name">' +
      '<div class="area-check"><input type="checkbox" class="area-pass"></div>' +
      '<div class="area-check"><input type="checkbox" class="area-fail"></div>' +
      '<button type="button" class="area-remove" title="Remove" style="background:none;border:none;color:#999;font-size:18px;cursor:pointer;line-height:1;">&times;</button>';
    var passBox = wrap.querySelector('.area-pass');
    var failBox = wrap.querySelector('.area-fail');
    passBox.addEventListener('change', function(){ if(passBox.checked) failBox.checked = false; });
    failBox.addEventListener('change', function(){ if(failBox.checked) passBox.checked = false; });
    wrap.querySelector('.area-remove').addEventListener('click', function(){ wrap.remove(); });
    return wrap;
  }

  var areaList = document.getElementById('area-list');
  DEFAULT_AREAS.forEach(function(a){ areaList.appendChild(makeAreaRow(a)); });
  document.getElementById('add-area-btn').addEventListener('click', function(){
    areaList.appendChild(makeAreaRow(''));
  });

  function todayStr(){
    var d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
  }
  document.getElementById('f-date').value = todayStr();

  function syncInvoiceMirror(){
    var map = [
      ['f-client', 'inv-client'],
      ['f-date', 'inv-date'],
      ['f-report', 'inv-report'],
      ['f-address', 'inv-address'],
      ['f-client', 'agr-client'],
      ['f-date', 'agr-date']
    ];
    map.forEach(function(pair){
      var src = document.getElementById(pair[0]);
      var dest = document.getElementById(pair[1]);
      var val = src.value.trim();
      dest.textContent = val || '—';
      dest.classList.toggle('empty', !val);
    });
  }
  ['f-client','f-date','f-report','f-address'].forEach(function(id){
    document.getElementById(id).addEventListener('input', syncInvoiceMirror);
  });
  syncInvoiceMirror();

  function collectAreas(){
    var rows = document.querySelectorAll('#area-list .area-row');
    var out = [];
    rows.forEach(function(row){
      var name = row.querySelector('.area-name').value.trim();
      var pass = row.querySelector('.area-pass').checked;
      var fail = row.querySelector('.area-fail').checked;
      if(name){
        out.push({name: name, status: pass ? 'pass' : (fail ? 'fail' : null)});
      }
    });
    return out;
  }

  function selectedRadio(name){
    var el = document.querySelector('input[name="' + name + '"]:checked');
    return el ? el.value : null;
  }

  function collectRecord(){
    return {
      id: currentRecordId,
      client: document.getElementById('f-client').value.trim(),
      date: document.getElementById('f-date').value || todayStr(),
      reportNumber: document.getElementById('f-report').value.trim(),
      address: document.getElementById('f-address').value.trim(),
      ieccYear: document.getElementById('f-iecc-year').value.trim(),
      ieccYear2: document.getElementById('f-iecc-year2').value.trim(),
      areas: collectAreas(),
      fee: document.getElementById('f-fee').value,
      paymentMethod: selectedRadio('payMethod'),
      paymentOption: selectedRadio('payOption'),
      reField: document.getElementById('f-re').value.trim(),
      pmName: document.getElementById('f-pmname').value.trim(),
      pmCell: document.getElementById('f-pmcell').value.trim(),
      companyName: document.getElementById('f-companyname').value.trim(),
      companyContact: document.getElementById('f-companycontact').value.trim(),
      signatureName: document.getElementById('f-signature').value.trim(),
      signatureDate: document.getElementById('f-signature-date').value,
      recipientEmail: document.getElementById('f-recipient').value.trim()
    };
  }

  function setStatus(msg, kind){
    var el = document.getElementById('status-msg');
    el.textContent = msg;
    el.className = 'status-msg' + (kind ? ' ' + kind : '');
  }

  document.getElementById('download-btn').addEventListener('click', async function(){
    var record = collectRecord();
    if(!record.client || !record.address){
      setStatus('Add a client name and address first.', 'error');
      return;
    }
    setStatus('Generating PDF…', '');
    try{
      var res = await fetch('api.php?action=download_pdf', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(record)
      });
      if(!res.ok){
        var json = await res.json().catch(function(){ return {}; });
        setStatus(json.error || 'Could not generate the PDF.', 'error');
        return;
      }
      var blob = await res.blob();
      var returnedId = res.headers.get('X-Record-Id');
      if(returnedId){ currentRecordId = returnedId; }
      var disposition = res.headers.get('Content-Disposition') || '';
      var match = disposition.match(/filename="([^"]+)"/);
      var filename = match ? match[1] : 'Inspection_Report.pdf';
      var url = window.URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
      setStatus('PDF downloaded.', 'ok');
    }catch(e){
      setStatus('Could not reach the server.', 'error');
    }
  });

  document.getElementById('share-btn').addEventListener('click', async function(){
    var record = collectRecord();
    if(!record.client || !record.address){
      setStatus('Add a client name and address first.', 'error');
      return;
    }
    if(!record.recipientEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(record.recipientEmail)){
      setStatus('Enter a valid recipient email address.', 'error');
      return;
    }
    setStatus('Generating PDF and sending…', '');
    try{
      var res = await fetch('api.php?action=share_email', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(record)
      });
      var json = await res.json();
      if(res.ok && json.ok){
        currentRecordId = json.id;
        setStatus('PDF report emailed to ' + record.recipientEmail + '. A copy was CC\'d to you.', 'ok');
      } else {
        setStatus(json.error || 'Could not send the email.', 'error');
      }
    }catch(e){
      setStatus('Could not reach the server.', 'error');
    }
  });
})();

// Keep the session alive only while this tab is open. If these pings
// stop (tab closed, browser closed, etc.), the session goes stale on
// the server after a short timeout -- see auth.php / heartbeat.php.
(function(){
  function ping(){
    fetch('heartbeat.php', { method: 'GET', cache: 'no-store' })
      .then(function(res){
        if(res.status === 401){
          window.location.href = 'login.php';
        }
      })
      .catch(function(){ /* network hiccup -- try again next tick */ });
  }
  ping();
  setInterval(ping, 8000);
})();
</script>
</body>
</html>
