<!DOCTYPE html>
<html>
<head>
    <title>Devices</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <?php include("nav.php"); ?>

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

            $color = '#4caf50';
            if ($value < 30) $color = '#ff9800';
            if ($value < 15) $color = '#f44336';

            return "
                <div class='toner-bar-container'>
                    <div class='toner-bar' style='background:$color;width:{$value}%;'></div>
                </div>
                <div class='toner-label'>{$value}%</div>
            ";
        }

        $pdo = new PDO("mysql:host=db;dbname=myapp", "myuser", "mypass");
        $stmt = $pdo->query("SELECT * FROM devices ORDER BY INET_ATON(ip_address) ASC");

        while ($row = $stmt->fetch()) {
            $critical = false;
            foreach (['toner_black', 'toner_cyan', 'toner_magenta', 'toner_yellow'] as $t) {
                if (is_numeric($row[$t]) && $row[$t] < 15) {
                    $critical = true;
                    break;
                }
            }

            $class = $critical ? "class='low-toner'" : "";

            echo "<tr $class>";
            echo "<td>{$row['ip_address']}</td>";
            echo "<td>{$row['model']}</td>";
            echo "<td>{$row['page_count']}</td>";
            echo "<td>" . render_toner_bar($row['toner_black']) . "</td>";
            echo "<td>" . render_toner_bar($row['toner_cyan']) . "</td>";
            echo "<td>" . render_toner_bar($row['toner_magenta']) . "</td>";
            echo "<td>" . render_toner_bar($row['toner_yellow']) . "</td>";
            echo "<td>{$row['last_seen']}</td>";
            echo "</tr>";
        }
        ?>
    </table>

</body>
</html>
