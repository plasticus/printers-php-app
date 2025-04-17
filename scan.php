<?php
$ip = $_GET['ip'] ?? '';
$community = "public";

function clean_snmp_string($val) {
    $val = trim($val);
    $val = preg_replace('/^(STRING|Counter32):\s*/', '', $val);
    $val = trim($val, '"');
    return $val;
}

if (!$ip) {
    echo json_encode(["error" => "No IP provided"]);
    exit;
}

$model         = @snmpget($ip, $community, "1.3.6.1.2.1.25.3.2.1.3.1");
$manufacturer  = @snmpget($ip, $community, "1.3.6.1.2.1.1.1.0");
$page_count    = @snmpget($ip, $community, "1.3.6.1.2.1.43.10.2.1.4.1.1");
$toner_black   = @snmpget($ip, $community, "1.3.6.1.2.1.43.11.1.1.9.1.1");
$toner_cyan    = @snmpget($ip, $community, "1.3.6.1.2.1.43.11.1.1.9.1.2");
$toner_magenta = @snmpget($ip, $community, "1.3.6.1.2.1.43.11.1.1.9.1.3");
$toner_yellow  = @snmpget($ip, $community, "1.3.6.1.2.1.43.11.1.1.9.1.4");

if ($model || $manufacturer) {
    $pdo = new PDO("mysql:host=db;dbname=myapp", "myuser", "mypass");

    $stmt = $pdo->prepare("INSERT INTO devices 
        (ip_address, manufacturer, model, toner_black, toner_cyan, toner_magenta, toner_yellow, page_count)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            manufacturer = VALUES(manufacturer),
            model = VALUES(model),
            toner_black = VALUES(toner_black),
            toner_cyan = VALUES(toner_cyan),
            toner_magenta = VALUES(toner_magenta),
            toner_yellow = VALUES(toner_yellow),
            page_count = VALUES(page_count),
            last_seen = CURRENT_TIMESTAMP");

    $stmt->execute([
        $ip,
        clean_snmp_string($manufacturer),
        clean_snmp_string($model),
        (int) filter_var(clean_snmp_string($toner_black), FILTER_SANITIZE_NUMBER_INT),
        (int) filter_var(clean_snmp_string($toner_cyan), FILTER_SANITIZE_NUMBER_INT),
        (int) filter_var(clean_snmp_string($toner_magenta), FILTER_SANITIZE_NUMBER_INT),
        (int) filter_var(clean_snmp_string($toner_yellow), FILTER_SANITIZE_NUMBER_INT),
        (int) filter_var(clean_snmp_string($page_count), FILTER_SANITIZE_NUMBER_INT)
    ]);

    echo json_encode([
        "ip" => $ip,
        "model" => clean_snmp_string($model),
        "status" => "Printer found and saved"
    ]);
} else {
    echo json_encode([
        "ip" => $ip,
        "status" => "No response"
    ]);
}
?>
