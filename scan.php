<?php
// scan.php
file_put_contents('/tmp/snmp_debug.log', '');

$community = "public";

function snmp_get_value($ip, $oid) {
    return @snmpget($ip, "public", $oid);
}

function clean_snmp_string($val) {
    $val = trim($val);
    $val = preg_replace('/^(STRING|Counter32):\s*/', '', $val);
    return trim($val, '"');
}

$ip = $_GET['ip'] ?? '';
$mode = $_GET['mode'] ?? 'discover';

if (!$ip) {
    echo json_encode(["status" => "No IP specified"]);
    exit;
}

$model = clean_snmp_string(snmp_get_value($ip, "1.3.6.1.2.1.25.3.2.1.3.1"));
$manufacturer = clean_snmp_string(snmp_get_value($ip, "1.3.6.1.2.1.1.1.0"));
$serial = clean_snmp_string(snmp_get_value($ip, "1.3.6.1.2.1.43.5.1.1.17.1"));
$page_count = (int) filter_var(clean_snmp_string(snmp_get_value($ip, "1.3.6.1.2.1.43.10.2.1.4.1.1")), FILTER_SANITIZE_NUMBER_INT);
$toner_black = (int) filter_var(clean_snmp_string(snmp_get_value($ip, "1.3.6.1.2.1.43.11.1.1.9.1.1")), FILTER_SANITIZE_NUMBER_INT);

file_put_contents('/tmp/snmp_debug.log', "[{$ip}] Raw toner_black: {$toner_black}\n", FILE_APPEND);

if ($model || $manufacturer) {
    $pdo = new PDO("mysql:host=db;dbname=myapp", "myuser", "mypass");

    if ($mode === 'discover') {
        $stmt = $pdo->prepare("INSERT INTO devices (ip_address, manufacturer, model, serial_number, toner_black, page_count)
                               VALUES (?, ?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE
                               manufacturer = VALUES(manufacturer),
                               model = VALUES(model),
                               serial_number = VALUES(serial_number),
                               toner_black = VALUES(toner_black),
                               page_count = VALUES(page_count),
                               last_seen = CURRENT_TIMESTAMP");
        $stmt->execute([$ip, $manufacturer, $model, $serial, $toner_black, $page_count]);
    }

    if ($mode === 'update') {
        $stmt = $pdo->prepare("INSERT INTO page_history (ip_address, model, serial_number, page_count) VALUES (?, ?, ?, ?)");
        $stmt->execute([$ip, $model, $serial, $page_count]);

        $stmt2 = $pdo->prepare("UPDATE devices SET model = ?, serial_number = ?, page_count = ?, toner_black = ?, last_seen = CURRENT_TIMESTAMP WHERE ip_address = ?");
        $stmt2->execute([$model, $serial, $page_count, $toner_black, $ip]);
    }

    echo json_encode([
        "status" => "✔ Found",
        "model" => $model,
        "page_count" => $page_count,
        "toner_black" => $toner_black,
    ]);
} else {
    echo json_encode(["status" => "No SNMP response"]);
}
?>
