<!DOCTYPE html>
<html>
<head>
    <title>Live Discovery</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <?php include("nav.php"); ?>

    <h1>Live Discovery</h1>

    <button onclick="startDiscovery()">Start Discovery</button>

    <div id="progressBar"><div></div></div>
    <div id="progressText"></div>
    <div class="log" id="log"></div>

    <script>
        const start = 50;
        const end = 99;
        const maxConcurrent = 10;
        let currentIp = start;

        async function startDiscovery() {
            document.getElementById('log').innerHTML = '';
            document.querySelector('#progressBar div').style.width = '0%';
            document.getElementById('progressText').textContent = '';
            currentIp = start;
            await scanRange();
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
