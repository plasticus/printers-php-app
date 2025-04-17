<?php
// discovery.php
?>
<!DOCTYPE html>
<html>
<head>
    <title>Live Discovery</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<?php include("nav.php"); ?>

<h1 style="display: flex; align-items: center; gap: 10px;">
    Live Discovery
    <div id="spinner" class="spinner" style="display: none;"></div>
</h1>

<form id="discovery-form" onsubmit="startDiscovery(); return false;" style="display: flex; gap: 15px; align-items: center; margin-bottom: 20px;">
    <label>Start IP:
        <input class="input-ip" type="number" id="start-ip" value="50" min="1" max="254">
    </label>
    <label>End IP:
        <input class="input-ip" type="number" id="end-ip" value="99" min="1" max="254">
    </label>
    <button id="start-btn" type="submit">🚀 Discover</button>
    <button type="button" onclick="startUpdate()" title="Only scans known devices to track impressions over time">↻ Update</button>
</form>

<div id="progressBar"><div></div></div>
<div id="progressText"></div>
<div class="log" id="log"></div>

<script>
let start = 50;
let end = 99;
let currentIp = start;
const maxConcurrent = 10;

function startDiscovery() {
    start = parseInt(document.getElementById('start-ip').value) || 50;
    end = parseInt(document.getElementById('end-ip').value) || 99;

    document.getElementById('log').innerHTML = '';
    document.querySelector('#progressBar div').style.width = '0%';
    document.getElementById('progressText').textContent = '';
    document.getElementById('spinner').style.display = 'inline-block';

    currentIp = start;
    scanRange('discover');
}

function startUpdate() {
    document.getElementById('log').innerHTML = '';
    document.querySelector('#progressBar div').style.width = '0%';
    document.getElementById('progressText').textContent = '';
    document.getElementById('spinner').style.display = 'inline-block';

    fetch('update.php')
        .then(res => res.text())
        .then(html => {
            document.getElementById('log').innerHTML = html;
            document.getElementById('spinner').style.display = 'none';
            document.getElementById('progressText').textContent = "Update complete!";
        });
}

async function scanRange(mode) {
    const total = end - start + 1;

    async function scanBatch() {
        const batch = [];
        for (let i = 0; i < maxConcurrent && currentIp <= end; i++, currentIp++) {
            const ip = `10.23.0.${currentIp}`;
            batch.push(scanOne(ip, currentIp - start + 1, total, mode));
        }
        await Promise.all(batch);
        if (currentIp <= end) {
            await scanBatch();
        } else {
            document.getElementById('progressText').textContent = "Discovery complete!";
            document.getElementById('spinner').style.display = 'none';
        }
    }

    await scanBatch();
}

async function scanOne(ip, progressCount, total, mode) {
    try {
        const res = await fetch(`scan.php?ip=${ip}&mode=${mode}`);
        const data = await res.json();

        let logEntry = `[${ip}] ${data.status}`;
        if (data.model) logEntry += ` – ${data.model}`;

        const log = document.getElementById('log');
        log.innerHTML += logEntry + "<br>";
        log.scrollTop = log.scrollHeight;

        const percent = Math.round((progressCount / total) * 100);
        document.querySelector('#progressBar div').style.width = percent + "%";
        document.getElementById('progressText').textContent = `${percent}% (${ip})`;
    } catch (err) {
        const log = document.getElementById('log');
        log.innerHTML += `[${ip}] ⚠️ Error<br>`;
    }
}
</script>

<style>
.input-ip {
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 1em;
    width: 70px;
    margin-left: 5px;
}
body.dark .input-ip {
    background: #222;
    border-color: #555;
    color: #eee;
}
button[title] {
    position: relative;
}
button[title]:hover::after {
    content: attr(title);
    position: absolute;
    top: 110%;
    left: 0;
    background: #333;
    color: white;
    padding: 5px 10px;
    font-size: 0.8em;
    white-space: nowrap;
    border-radius: 6px;
    z-index: 10;
}
</style>

</body>
</html>