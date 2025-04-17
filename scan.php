<?php
$ip = $_GET['ip'] ?? '';
$community = "public";

function clean_snmp_string($val) {
    $val = trim($val);
    $val = preg_replace('/^(STRING|Counter32):\s*/', '', $val);
    $val = trim($val, '"');
    return $val;
}

function get_supply_levels($ip, $community) {
    $supplies = [];

    for ($i = 1; $i <= 10; $i++) {
        $desc = @snmpget($ip, $community, "1.3.6.1.2.1.43.11.1.1.6.1.$i");
        $level = @snmpget($ip, $community, "1.3.6.1.2.1.43.11.1.1.9.1.$i");

        if (!$desc || !$level) continue;

        $desc = strtolower(clean_snmp_string($desc));
        $level = (int) filter_var(clean_snmp_string($level), FILTER_SANITIZE_NUMBER_INT);

        if (str_contains($desc, "black"))      $supplies['toner_black'] = $level;
        elseif (str_contains($desc, "cyan"))   $supplies['toner_cyan'] = $level;
        elseif (str_contains($desc, "magenta"))$supplies['toner_magenta'] = $level;
        elseif (str_contains($desc, "yellow")) $supplies['toner_yellow'] = $level;
    }

    return $supplies;
}

if (!$ip) {
    echo json_encode(["error" => "No IP provided"]);
    exit;
}

// SNMP basic data
$model        = @snmpget($ip, $community, "1.3.6.1.2.1.25.3.2.1.3.1");
$manufacturer = @snmpget($ip, $community, "1.3.6.1.2.1.1.1.0");
$page_count   = @snmpget($ip, $community, "1.3.6.1.2.1.43.10.2.1.4.1.1");

if ($model || $manufacturer) {
    $pdo = new PDO("mysql:host=db;dbname=myapp", "myuser", "mypass");

    // Default toner values
    $toner_black = null;
    $toner_cyan = null;
    $toner_magenta = null;
    $toner_yellow = null;

    // Try to detect toner dynamically
    $supplies = get_supply_levels($ip, $community);
    extract($supplies); // populate individual toner vars if set

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
        $toner_black,
        $toner_cyan,
        $toner_magenta,
        $toner_yellow,
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
