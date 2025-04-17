<?php
$start_ip = 50;
$end_ip = 99;
$subnet = "10.23.0.";
$community = "public"; // SNMP community string

// Clean SNMP output by removing prefixes and quotes
function clean_snmp_string($val) {
    $val = trim($val);
    $val = preg_replace('/^(STRING|Counter32):\s*/', '', $val); // remove SNMP prefix
    $val = trim($val, '"');
    return $val;
}

// Connect to DB once
$pdo = new PDO("mysql:host=db;dbname=myapp", "myuser", "mypass");

for ($i = $start_ip; $i <= $end_ip; $i++) {
    $ip = $subnet . $i;
    echo "Scanning $ip...<br>";

    // SNMP queries
    $model         = @snmpget($ip, $community, "1.3.6.1.2.1.25.3.2.1.3.1");
    $manufacturer  = @snmpget($ip, $community, "1.3.6.1.2.1.1.1.0");
    $page_count    = @snmpget($ip, $community, "1.3.6.1.2.1.43.10.2.1.4.1.1");

    // Toner levels
    $toner_black   = @snmpget($ip, $community, "1.3.6.1.2.1.43.11.1.1.9.1.1");
    $toner_cyan    = @snmpget($ip, $community, "1.3.6.1.2.1.43.11.1.1.9.1.2");
    $toner_magenta = @snmpget($ip, $community, "1.3.6.1.2.1.43.11.1.1.9.1.3");
    $toner_yellow  = @snmpget($ip, $community, "1.3.6.1.2.1.43.11.1.1.9.1.4");

    if ($model || $manufacturer) {
        echo "➤ Found printer at $ip!<br>";
        echo "Manufacturer: $manufacturer<br>";
        echo "Model: $model<br>";
        echo "Page Count: $page_count<br><br>";

        // Clean values
        $model         = clean_snmp_string($model);
        $manufacturer  = clean_snmp_string($manufacturer);
        $page_count    = (int) filter_var(clean_snmp_string($page_count), FILTER_SANITIZE_NUMBER_INT);
        $toner_black   = (int) filter_var(clean_snmp_string($toner_black), FILTER_SANITIZE_NUMBER_INT);
        $toner_cyan    = (int) filter_var(clean_snmp_string($toner_cyan), FILTER_SANITIZE_NUMBER_INT);
        $toner_magenta = (int) filter_var(clean_snmp_string($toner_magenta), FILTER_SANITIZE_NUMBER_INT);
        $toner_yellow  = (int) filter_var(clean_snmp_string($toner_yellow), FILTER_SANITIZE_NUMBER_INT);

        // Save to DB
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
            $manufacturer,
            $model,
            $toner_black,
            $toner_cyan,
            $toner_magenta,
            $toner_yellow,
            $page_count
        ]);
    }
}
?>
