<!DOCTYPE html>
<html>
<head>
    <title>Devices</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 20px;
        }
        nav {
            margin-bottom: 20px;
        }
        nav a {
            margin-right: 20px;
            text-decoration: none;
            font-weight: bold;
            color: #333;
        }
        nav a:hover {
            text-decoration: underline;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #aaa;
            padding: 8px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #eee;
        }
        .low-toner {
            background-color: #ffe0e0;
        }
        .toner-bar-container {
            background: #eee;
            width: 100px;
            border: 1px solid #ccc;
            height: 12px;
            position: relative;
        }
        .toner-bar {
            height: 100%;
        }
        .toner-label {
            font-size: 0.75em;
            color: #555;
        }
    </style>
</head>
<body>

    <nav>
        <a href="discovery.php">Discovery</a>
        <a href="devices.php">Devices</a>
    </nav>

    <h1>Devices</h1>

    <table>
        <tr>
            <th>IP Address</th>
            <th>Model</th>
            <th>Page Count</th>
            <th>Toner (Black)</th>
            <th>Toner (Cyan)</th>
            <th>Toner (Magenta)</th>
            <th>Toner (Yellow)</th>
            <th>Last Seen</th>
        </tr>

        <?php
        function render_toner_bar($value) {
            if (!is_numeric($value) || $value < 0 || $value > 100) {
                return "<span class='toner-label'>Unknown</span>";
            }

            $color = '#4caf50'; // green
            if ($value < 30) $color = '#ff9800'; // orange
            if ($value < 15) $color = '#f44336'; // red

            return "
                <div class='toner-bar-container'>
                    <div class='toner-bar' style='background:$color;width:{$value}%;'></div>
                </div>
                <div
