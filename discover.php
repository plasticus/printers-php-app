<?php
$start_ip = 50;
$end_ip = 99;
$subnet = "10.23.0.";

$community = "public"; // SNMP community string
$results = [];

// Clean SNMP output by removing prefixes and quotes
function clean_snmp_string($val) {
    $val = trim($val);
    $val = preg_replace('/^(STRING|Counter32):\s*/', '', $val); // remove SNMP prefix
    $val = trim($val, '"'); // remove quotes if present
    return $val;
}

// Connect to DB once
$pdo = new PDO("mysql:host=db;dbname=myapp", "myuser", "mypass");

for ($i = $start_ip; $i <= $end_ip; $i++) {
    $ip = $subnet . $i;
    echo "Scanning $ip...<br>";

    // SNMP queries
    $model = @snmpget($ip, $community, "1.3.6.1.2.1.25.3.2.1.3.1");        // hrDeviceDescr
    $manufacturer = @snmpget($ip, $community, "1.3.6.1.2.1.1.1.0");       // sysDescr
    $page_count = @snmpget($ip, $community, "1.3.6.1.2.1.43.10.2.1.4.1.1"); // page count
    $toner_black = @snmpget($ip, $community, "1.3.6.1.2.1.43.11.1.1.9.1.1"); // black toner level

    if ($model || $manufacturer) {
        echo "➤ Found printer at $ip!<br>";
        echo "Manufacturer: $manufacturer<br>";
        echo "Model: $model<br>";
        echo "Page Count: $page_count<br><br>";

        // Clean and convert
        $model = clean_snmp_string($model);
        $manufacturer = clean_snmp_string($manufacturer);
        $page_count = (int) filter_var(clean_snmp_string($page_count), FILTER_SANITIZE_NUMBER_INT);
        $toner_black = (int) filter_var(clean_snmp_string($toner_black), FILTER_SANITIZE_NUMBER_INT);

        // Save to DB
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
