<?php
// devices.php
?>
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
        <th>Custom Model</th>
        <th>Location</th>
        <th>Notes</th>
        <th>Edit</th>
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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $stmt = $pdo->prepare("UPDATE devices SET user_model = ?, location = ?, notes = ? WHERE ip_address = ?");
        $stmt->execute([
            $_POST['user_model'],
            $_POST['location'],
            $_POST['notes'],
            $_POST['ip']
        ]);
    }

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
        $ip = htmlspecialchars($row['ip_address']);
        $user_model = htmlspecialchars($row['user_model'] ?? '');
        $location = htmlspecialchars($row['location'] ?? '');
        $notes = htmlspecialchars($row['notes'] ?? '');

        echo "<form method='post'>
            <input type='hidden' name='ip' value='{$ip}'>
            <tr $class>
                <td><a href='http://{$ip}' target='_blank'>{$ip}</a></td>
                <td>{$row['model']}</td>
                <td>{$row['page_count']}</td>
                <td>" . render_toner_bar($row['toner_black']) . "</td>
                <td>" . render_toner_bar($row['toner_cyan']) . "</td>
                <td>" . render_toner_bar($row['toner_magenta']) . "</td>
                <td>" . render_toner_bar($row['toner_yellow']) . "</td>
                <td>{$row['last_seen']}</td>
                <td><input type='text' name='user_model' value='{$user_model}'></td>
                <td><input type='text' name='location' value='{$location}'></td>
                <td><input type='text' name='notes' value='{$notes}'></td>
                <td><button type='submit'>💾 Save</button></td>
            </tr>
        </form>";
    }
    ?>
</table>

</body>
</html>
