<?php
$start_ip = 50;
$end_ip = 99;
$subnet = "10.23.0.";

$community = "public"; // SNMP community string
$results = [];

function snmp_get_value($ip, $oid) {
    return @snmpget($ip, "public", $oid);
}

for ($i = $start_ip; $i <= $end_ip; $i++) {
    $ip = $subnet . $i;
    echo "Scanning $ip...<br>";

    // Try to get basic printer info via SNMP
    $model = snmp_get_value($ip, "1.3.6.1.2.1.25.3.2.1.3.1"); // hrDeviceDescr
    $manufacturer = snmp_get_value($ip, "1.3.6.1.2.1.1.1.0"); // sysDescr

    if ($model || $manufacturer) {
        // Get page count (may need printer-specific OIDs)
        $page_count = snmp_get_value($ip, "1.3.6.1.2.1.43.10.2.1.4.1.1");
        $toner_black = snmp_get_value($ip, "1.3.6.1.2.1.43.11.1.1.9.1.1");

        echo "➤ Found printer at $ip!<br>";
        echo "Manufacturer: $manufacturer<br>";
        echo "Model: $model<br>";
        echo "Page Count: $page_count<br><br>";

        // Strip quotes and SNMP wrappers
        $model = trim(str_replace('"', '', $model));
        $manufacturer = trim(str_replace('"', '', $manufacturer));
        $page_count = (int) filter_var($page_count, FILTER_SANITIZE_NUMBER_INT);
        $toner_black = (int) filter_var($toner_black, FILTER_SANITIZE_NUMBER_INT);

        // Save to DB
        $pdo = new PDO("mysql:host=db;dbname=myapp", "myuser", "mypass");
        $stmt = $pdo->prepare("INSERT INTO devices (ip_address, manufacturer, model, toner_black, page_count)
                               VALUES (?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE
                               manufacturer = VALUES(manufacturer),
                               model = VALUES(model),
                               toner_black = VALUES(toner_black),
                               page_count = VALUES(page_count),
                               last_seen = CURRENT_TIMESTAMP");

        $stmt->execute([$ip, $manufacturer, $model, $toner_black, $page_count]);
    }
}
?>
