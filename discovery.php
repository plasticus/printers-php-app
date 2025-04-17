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

<form id="discovery-form" onsubmit="startDiscovery(); return false;">
    <label>Start IP: <input type="number" id="start-ip" value="50" min="1" max="254"></label>
    <label>End IP: <input type="number" id="end-ip" value="99" min="1" max="254"></label>
    <button id="start-btn" type="submit">🚀 Start Discovery</button>
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
    scanRange();
}

async function scanRange() {
    const total = end - start + 1;

    async function scanBatch() {
        const batch = [];
        for (let i = 0; i < maxConcurrent && currentIp <= end; i++, currentIp++) {
            const ip = `10.23.0.${currentIp}`;
            batch.push(scanOne(ip, currentIp - start + 1, total));
        }
        await Promise.all(batch);
        if (currentIp <= end) {
            await scanBatch();
        } else {
            document.getElementById('progressText').textContent = "Scan complete!";
            document.getElementById('spinner').style.display = 'none';
        }
    }

    await scanBatch();
}

async function scanOne(ip, progressCount, total) {
    try {
        const res = await fetch(`scan.php?ip=${ip}`);
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

</body>
</html>
