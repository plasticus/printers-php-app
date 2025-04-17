<!DOCTYPE html>
<html>
<head>
    <title>Live Discovery</title>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        #progressBar { width: 100%; background: #ddd; margin-top: 10px; }
        #progressBar div { height: 20px; background: #4caf50; width: 0%; }
        .log { font-family: monospace; margin-top: 20px; max-height: 400px; overflow-y: scroll; background: #f4f4f4; padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>

    <nav>
        <a href="discovery.php">Discovery</a>
        <a href="devices.php">Devices</a>
    </nav>

    <h1>Live Discovery</h1>

    <button onclick="startDiscovery()">Start Discovery</button>

    <div id="progressBar"><div></div></div>
    <div id="progressText"></div>
    <div class="log" id="log"></div>

    <script>
        const start = 50;
        const end = 99;
        let current = start;

        async function startDiscovery() {
            document.getElementById('log').innerHTML = '';
            current = start;
            await scanNext();
        }

        async function scanNext() {
            if (current > end) {
                document.getElementById('progressText').textContent = "Scan complete!";
                return;
            }

            const ip = `10.23.0.${current}`;
            const res = await fetch(`scan.php?ip=${ip}`);
            const data = await res.json();

            let logEntry = `[${ip}] ${data.status}`;
            if (data.model) logEntry += ` – ${data.model}`;

            const log = document.getElementById('log');
            log.innerHTML += logEntry + "<br>";
            log.scrollTop = log.scrollHeight;

            const progress = ((current - start + 1) / (end - start + 1)) * 100;
            document.querySelector('#progressBar div').style.width = progress + "%";
            document.getElementById('progressText').textContent = `${Math.round(progress)}% (${ip})`;

            current++;
            setTimeout(scanNext, 300); // Adjust timing for responsiveness
        }
    </script>
</body>
</html>
