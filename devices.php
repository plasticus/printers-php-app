<!DOCTYPE html>
<html>
<head>
    <title>Devices</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #aaa; padding: 8px; text-align: left; }
        th { background-color: #eee; }
    </style>
</head>
<body>
    <h1>Devices</h1>
    <table>
        <tr>
            <th>IP Address</th>
            <th>Manufacturer</th>
            <th>Model</th>
            <th>Page Count</th>
            <th>Last Seen</th>
        </tr>

        <?php
        $pdo = new PDO("mysql:host=db;dbname=myapp", "myuser", "mypass");
        $stmt = $pdo->query("SELECT * FROM devices ORDER BY last_seen DESC");

        while ($row = $stmt->fetch()) {
            echo "<tr>
                    <td>{$row['ip_address']}</td>
                    <td>{$row['manufacturer']}</td>
                    <td>{$row['model']}</td>
                    <td>{$row['page_count']}</td>
                    <td>{$row['last_seen']}</td>
                  </tr>";
        }
        ?>
    </table>
</body>
</html>
