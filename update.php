<?php
// update.php
$community = "public";

function clean_snmp_string($val) {
    $val = trim($val);
    $val = preg_replace('/^(STRING|Counter32):\s*/', '', $val);
    return trim($val, '"');
}

$pdo = new PDO("mysql:host=db;dbname=myapp", "myuser", "mypass");
$stmt = $pdo->query("SELECT ip_address FROM devices ORDER BY INET_ATON(ip_address) ASC");
$devices = $stmt->fetchAll(PDO::FETCH_COLUMN);

$log = "";

foreach ($devices as $ip) {
    $model = @snmpget($ip, $community, "1.3.6.1.2.1.25.3.2.1.3.1");
    $serial = @snmpget($ip, $community, "1.3.6.1.2.1.43.5.1.1.17.1");
    $page_count = @snmpget($ip, $community, "1.3.6.1.2.1.43.10.2.1.4.1.1");

    if ($model || $serial || $page_count) {
        $model = clean_snmp_string($model);
        $serial = clean_snmp_string($serial);
        $page_count = (int) filter_var(clean_snmp_string($page_count), FILTER_SANITIZE_NUMBER_INT);

        // Log page count into history
        $history = $pdo->prepare("INSERT INTO page_history (ip_address, model, serial_number, page_count) VALUES (?, ?, ?, ?)");
        $history->execute([$ip, $model, $serial, $page_count]);

        // Update devices table with latest info
        $update = $pdo->prepare("UPDATE devices SET model = ?, serial_number = ?, page_count = ?, last_seen = CURRENT_TIMESTAMP WHERE ip_address = ?");
        $update->execute([$model, $serial, $page_count, $ip]);

        $log .= "<strong>[$ip]</strong> Updated page count: $page_count<br>";
    } else {
        $log .= "<strong>[$ip]</strong> No SNMP response<br>";
    }
}

echo $log;
?>
