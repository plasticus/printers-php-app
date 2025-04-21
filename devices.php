<?php
// devices.php
$pdo = new PDO("mysql:host=db;dbname=myapp", "myuser", "mypass");
$stmt = $pdo->query("SELECT * FROM devices ORDER BY ip_address ASC");
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getImpressions($pdo, $ip, $days) {
    $stmt = $pdo->prepare("SELECT page_count, timestamp FROM page_history WHERE ip_address = ? AND timestamp >= NOW() - INTERVAL ? DAY ORDER BY timestamp ASC");
    $stmt->execute([$ip, $days]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) < 2) return 0;
    return max(0, end($rows)['page_count'] - $rows[0]['page_count']);
}
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
        <th>Last 30 Days</th>
        <th>Last 90 Days</th>
        <th>Last Year</th>
        <th>Toner (Black)</th>
        <th>Location</th>
        <th>Notes</th>
        <th>Last Seen</th>
        <th>Edit/Save</th>
    </tr>
    <?php foreach ($devices as $row):
        $lowToner = is_numeric($row['toner_black']) && $row['toner_black'] < 15;
        $tonerPercent = $row['toner_black'];
        $imp30 = getImpressions($pdo, $row['ip_address'], 30);
        $imp90 = getImpressions($pdo, $row['ip_address'], 90);
        $imp365 = getImpressions($pdo, $row['ip_address'], 365);
    ?>
    <tr class="<?= $lowToner ? 'low-toner' : '' ?>">
        <td><a href="http://<?= htmlspecialchars($row['ip_address']) ?>" target="_blank"><?= htmlspecialchars($row['ip_address']) ?></a></td>
        <td><?= htmlspecialchars($row['model']) ?></td>
        <td><?= htmlspecialchars($row['page_count']) ?></td>
        <td><?= $imp30 ?></td>
        <td><?= $imp90 ?></td>
        <td><?= $imp365 ?></td>
        <td>
            <?php if ($tonerPercent !== null): ?>
                <div class="toner-bar-container">
                    <div class="toner-bar" style="width: <?= $tonerPercent ?>%; background-color: <?=
                        $tonerPercent < 15 ? 'var(--toner-low)' :
                        ($tonerPercent < 40 ? 'var(--toner-med)' : 'var(--toner-ok)')
                    ?>;"></div>
                </div>
                <div class="toner-label"><?= $tonerPercent ?>%</div>
            <?php else: ?>
                Unknown
            <?php endif; ?>
        </td>
        <form method="POST">
            <td><input type="text" name="location" value="<?= htmlspecialchars($row['location'] ?? '') ?>"></td>
            <td><textarea name="notes" rows="2"><?= htmlspecialchars($row['notes'] ?? '') ?></textarea></td>
            <td><?= htmlspecialchars($row['last_seen']) ?></td>
            <td>
                <input type="hidden" name="ip" value="<?= htmlspecialchars($row['ip_address']) ?>">
                <button type="submit">Save</button>
            </td>
        </form>
    </tr>
    <?php endforeach; ?>
</table>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE devices SET location = ?, notes = ? WHERE ip_address = ?");
    $stmt->execute([
        $_POST['location'],
        $_POST['notes'],
        $_POST['ip']
    ]);
    header("Location: devices.php");
    exit;
}
?>

</body>
</html>
