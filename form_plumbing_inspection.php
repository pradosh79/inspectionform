<?php
require_once 'auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
<title>Property Inspection Report (MEP / Plumbing) — A &amp; D Inspections</title>
<link rel="stylesheet" href="report.css">
<style>
  input[type=text], input[type=date], input[type=time], input[type=number], input[type=email]{
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
  .f-row, .f-row-inline{ margin:10px 0; font-size:14px; }
  .f-row label, .f-row-inline label{ display:block; font-weight:bold; margin-bottom:4px; }
  .f-row-inline{ display:flex; gap:14px; flex-wrap:wrap; align-items:flex-start; }
  .f-row-inline > div{ flex:1; min-width:150px; }
  .chip-row{ display:flex; gap:14px; flex-wrap:wrap; margin-top:6px; }
  .chip-row label{ font-weight:normal; display:flex; align-items:center; gap:6px; font-size:14px; }
  .legend-row{ font-size:12.5px; color:#555; font-style:italic; margin:6px 0 14px; }
  .item-card{ border:1px solid #ccc; border-radius:6px; padding:14px; margin:12px 0; background:#FAFAF7; position:relative; }
  .item-card-head{ display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
  .item-num{ font-weight:bold; color:#2E7D32; font-size:14px; }
  .item-remove{ background:none; border:none; color:#A6432F; font-size:13px; cursor:pointer; font-family:Arial,sans-serif; }
  .status-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-top:6px; }
  .status-grid label{ font-weight:normal; text-align:center; border:1px solid #ccc; border-radius:4px; padding:8px 4px; cursor:pointer; font-size:13px; display:flex; flex-direction:column; align-items:center; gap:4px; background:#fff; }
  .status-grid input{ margin:0; }
  .status-grid label.checked{ border-color:#2E7D32; background:#EAF5EA; font-weight:bold; }
  .actions{ max-width:760px; margin:0 auto 40px; padding:0 4px; display:flex; gap:12px; flex-wrap:wrap; }
  .btn{ flex:1; min-width:160px; padding:13px; border-radius:4px; font-family:Arial,sans-serif; font-weight:bold; font-size:15px; cursor:pointer; border:2px solid #2E7D32; }
  .btn-primary{ background:#2E7D32; color:#fff; }
  .btn-secondary{ background:#fff; color:#2E7D32; }
  .status-msg{ max-width:760px; margin:0 auto 20px; text-align:center; font-size:13px; font-weight:bold; min-height:18px; }
  .status-msg.ok{ color:#2E7D32; }
  .status-msg.error{ color:#A6432F; }
  #add-item-btn{ margin-top:8px;width:100%;padding:9px;border:1.5px dashed #999;background:none;color:#2E7D32;font-family:Arial,sans-serif;font-weight:bold;font-size:13.5px;border-radius:4px;cursor:pointer; }
  @media (max-width:600px){
    .status-grid{ grid-template-columns:repeat(2,1fr); }
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

  <h2 class="rp-h2" style="text-align:center;color:#a9c09a;">Property Inspection Report</h2>
  <p style="text-align:center;font-size:12.5px;color:#555;font-style:italic;margin-top:-4px;">Notice: This inspection report is subject to the attached contract and handouts.</p>

  <div class="f-row"><label for="f-report-title">Report Title</label><input type="text" id="f-report-title" placeholder=""></div>
  <div class="f-row"><label for="f-client">Prepared For (Client):</label><input type="text" id="f-client" placeholder="Client / Company name"></div>
  <div class="f-row"><label for="f-address">Concerning (Inspection Address):</label><textarea id="f-address" rows="2" placeholder="Address or other identification of inspected property"></textarea></div>
  <div class="f-row-inline">
    <div><label for="f-inspector">Inspector (Name):</label><input type="text" id="f-inspector" value="James Southerland"></div>
    <div><label for="f-license">License No.</label><input type="text" id="f-license" placeholder="e.g. 0882584"></div>
    <div><label for="f-date">Date:</label><input type="date" id="f-date"></div>
  </div>

  <div class="f-row" style="margin-top:18px;">
    <label>Inspection Scope:</label>

    <div class="chip-row">
      <label><input type="radio" name="scope" id="scope-plumbing" checked> Plumbing</label>
      <label><input type="radio" name="scope" id="scope-electrical"> Electrical</label>
      <label><input type="radio" name="scope" id="scope-hvac"> HVAC</label>
      <label><input type="radio" name="scope" id="scope-other"> Other</label>
      <input type="text" id="scope-other-text" placeholder="Specify other scope" style="max-width:220px;">
    </div>
  </div>

  <div class="f-row">
    <label>Parties present at inspection:</label>
    <div class="chip-row">
      <label><input type="checkbox" id="parties-superintendent"> Superintendent</label>
      <label><input type="checkbox" id="parties-subcontractor"> Subcontractor</label>
      <label><input type="checkbox" id="parties-other"> Other</label>
      <input type="text" id="parties-other-text" placeholder="Specify other party" style="max-width:220px;">
    </div>
  </div>

  <div class="f-row">
    <label>Weather conditions during inspection:</label>
    <div class="chip-row">
      <label><input type="radio" name="weather" value="Sunny" checked> Sunny</label>
      <label><input type="radio" name="weather" value="Overcast"> Overcast</label>
      <label><input type="radio" name="weather" value="Raining"> Raining</label>
    </div>
  </div>

  <div class="f-row-inline">
    <div><label for="f-time">Time of inspection:</label><input type="time" id="f-time"></div>
    <div><label for="f-temp">Outside air temperature:</label><input type="text" id="f-temp" placeholder="e.g. 82°F"></div>
  </div>

  <div class="f-row">
    <label>Additional written information provided with this inspection report:</label>
    <div class="chip-row">
      <label><input type="radio" name="addinfo" value="Yes"> Yes</label>
      <label><input type="radio" name="addinfo" value="No" checked> No</label>
    </div>
  </div>
</div>

<div class="rp-sheet">
  <div class="rp-letterhead">
    <div class="rp-company">A &amp; D INSPECTIONS, LLC</div>
    <div class="rp-companyline">16918 Rolling Acres Dr.&nbsp; Humble, TX. 77396</div>
    <div class="rp-companyline">281-802-0247 &nbsp; james@adinspections.com</div>
    <div class="rp-companyline">www.adinspections.com</div>
  </div>

  <h2 class="rp-h2">Inspection Items</h2>
  <div class="legend-row">I = Inspected &nbsp;&nbsp; NI = Not Inspected &nbsp;&nbsp; NP = Not Present &nbsp;&nbsp; D = Deficient</div>

  <div id="item-list"></div>
  <button type="button" id="add-item-btn">+ Add inspection item</button>

  <p style="margin-top:24px;">Inspector: <span id="sig-name-mirror">James Southerland</span></p>
  <img src="image/signature.png" alt="signature" style="height:44px;margin:4px 0;">
  <p>0882584<br>ICC Residential Combination Inspector<br>International Energy Conservation Code Certified<br>ICC Certified: Accessibility Inspector/Plans Examiner</p>
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
  <a href="reports_list_plumbing.php" style="color:#2E7D32;font-family:Arial,sans-serif;font-size:13px;">View previous reports &rarr;</a>
  &nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="index.php" style="color:#2E7D32;font-family:Arial,sans-serif;font-size:13px;">&larr; All forms</a>
</div>

<script>
(function(){
  var STATUS_OPTS = [
    {code:'I', label:'Inspected'},
    {code:'NI', label:'Not Inspected'},
    {code:'NP', label:'Not Present'},
    {code:'D', label:'Deficient'}
  ];
  var itemCounter = 0;
  var currentRecordId = null; // set after first successful save, so later clicks UPDATE this same record

  function makeItemCard(data){
    data = data || {};
    itemCounter++;
    var groupName = 'status-' + itemCounter;
    var card = document.createElement('div');
    card.className = 'item-card';

    var statusInputsHtml = STATUS_OPTS.map(function(opt){
      var checked = (data.status === opt.code) ? ' checked' : '';
      return '<label><input type="radio" name="' + groupName + '" value="' + opt.code + '"' + checked + '>' + opt.code + '<br><span style="font-weight:normal;font-size:11px;color:#666;">' + opt.label + '</span></label>';
    }).join('');

    card.innerHTML =
      '<div class="item-card-head">' +
        '<span class="item-num">Item</span>' +
        '<button type="button" class="item-remove">Remove</button>' +
      '</div>' +
      '<div class="f-row-inline">' +
        '<div><label>Category</label><input type="text" class="item-category" placeholder="e.g. I. MEP" value="' + escapeAttr(data.category || 'I. MEP') + '"></div>' +
        '<div><label>Subcategory / Item</label><input type="text" class="item-subcategory" placeholder="e.g. A. Underground Plumbing" value="' + escapeAttr(data.subcategory || '') + '"></div>' +
      '</div>' +
      '<label style="margin-top:8px;">Status</label>' +
      '<div class="status-grid">' + statusInputsHtml + '</div>' +
      '<div class="f-row"><label>Findings / Deficiency notes</label><textarea class="item-findings" rows="3" placeholder="Describe findings, e.g. Passed – PVC plumbing pipe has been assembled and embedded in sand per plans.">' + escapeHtml(data.findings || '') + '</textarea></div>';

    card.querySelector('.item-remove').addEventListener('click', function(){ card.remove(); renumberItems(); });
    card.querySelectorAll('.status-grid input').forEach(function(radio){
      radio.addEventListener('change', function(){ syncStatusHighlight(card); });
    });
    syncStatusHighlight(card);
    return card;
  }

  function syncStatusHighlight(card){
    card.querySelectorAll('.status-grid label').forEach(function(lbl){
      var input = lbl.querySelector('input');
      lbl.classList.toggle('checked', input.checked);
    });
  }

  function renumberItems(){
    var cards = document.querySelectorAll('#item-list .item-card .item-num');
    cards.forEach(function(el, i){ el.textContent = 'Item ' + (i + 1); });
  }

  function escapeHtml(s){
    return String(s).replace(/[&<>"]/g, function(c){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];
    });
  }
  function escapeAttr(s){ return escapeHtml(s); }

  var itemList = document.getElementById('item-list');
  itemList.appendChild(makeItemCard({category:'I. MEP', subcategory:'A. Underground Plumbing', status:'I', findings:''}));
  renumberItems();

  document.getElementById('add-item-btn').addEventListener('click', function(){
    itemList.appendChild(makeItemCard({}));
    renumberItems();
  });

  function todayStr(){
    var d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
  }
  document.getElementById('f-date').value = todayStr();

  document.getElementById('f-inspector').addEventListener('input', function(){
    document.getElementById('sig-name-mirror').textContent = this.value.trim() || 'James Southerland';
  });

  function selectedRadio(name){
    var el = document.querySelector('input[name="' + name + '"]:checked');
    return el ? el.value : '';
  }

  function collectItems(){
    var cards = document.querySelectorAll('#item-list .item-card');
    var out = [];
    cards.forEach(function(card, i){
      var category = card.querySelector('.item-category').value.trim();
      var subcategory = card.querySelector('.item-subcategory').value.trim();
      var statusInput = card.querySelector('.status-grid input:checked');
      var findings = card.querySelector('.item-findings').value.trim();
      if(category || subcategory || findings){
        out.push({
          category: category,
          subcategory: subcategory,
          status: statusInput ? statusInput.value : '',
          findings: findings
        });
      }
    });
    return out;
  }

  function collectRecord(){
    return {
      id: currentRecordId,
      reportTitle: document.getElementById('f-report-title').value.trim(),
      client: document.getElementById('f-client').value.trim(),
      address: document.getElementById('f-address').value.trim(),
      licenseNo: document.getElementById('f-license').value.trim(),
      inspector: document.getElementById('f-inspector').value.trim(),
      date: document.getElementById('f-date').value || todayStr(),
      scopePlumbing: document.getElementById('scope-plumbing').checked,
      scopeElectrical: document.getElementById('scope-electrical').checked,
      scopeHvac: document.getElementById('scope-hvac').checked,
      scopeOther: document.getElementById('scope-other').checked,
      scopeOtherText: document.getElementById('scope-other-text').value.trim(),
      partiesSuperintendent: document.getElementById('parties-superintendent').checked,
      partiesSubcontractor: document.getElementById('parties-subcontractor').checked,
      partiesOther: document.getElementById('parties-other').checked,
      partiesOtherText: document.getElementById('parties-other-text').value.trim(),
      weather: selectedRadio('weather'),
      timeOfInspection: document.getElementById('f-time').value,
      outsideTemp: document.getElementById('f-temp').value.trim(),
      additionalInfo: selectedRadio('addinfo'),
      items: collectItems(),
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
      setStatus('Add a client name and inspection address first.', 'error');
      return;
    }
    setStatus('Generating PDF…', '');
    try{
      var res = await fetch('api_plumbing.php?action=download_pdf', {
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
      var filename = match ? match[1] : 'Property_Inspection_Report.pdf';
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
      setStatus('Add a client name and inspection address first.', 'error');
      return;
    }
    if(!record.recipientEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(record.recipientEmail)){
      setStatus('Enter a valid recipient email address.', 'error');
      return;
    }
    setStatus('Generating PDF and sending…', '');
    try{
      var res = await fetch('api_plumbing.php?action=share_email', {
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

// Keep the session alive only while this tab is open (see auth.php / heartbeat.php).
(function(){
  function ping(){
    fetch('heartbeat.php', { method: 'GET', cache: 'no-store' })
      .then(function(res){
        if(res.status === 401){
          window.location.href = 'login.php';
        }
      })
      .catch(function(){});
  }
  ping();
  setInterval(ping, 8000);
})();
</script>
</body>
</html>
