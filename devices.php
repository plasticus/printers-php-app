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
        }
        th {
            background-color: #eee;
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
            <th>Last Seen</th>
        </tr>

        <?php
        $pdo = new PDO("mysql:host=db;dbname=myapp", "myuser", "mypass");

        // Convert IP string to integer for proper sorting
        $stmt = $pdo->query("SELECT * FROM devices ORDER BY INET_ATON(ip_address) ASC");

        while ($row = $stmt->fetch()) {
            echo "<tr>
                    <td>{$row['ip_address']}</td>
                    <td>{$row['model']}</td>
                    <td>{$row['page_count']}</td>
                    <td>{$row['last_seen']}</td>
                  </tr>";
        }
        ?>
    </table>
</body>
</html>
