<?php
// scan.php

$community = "public";

function snmp_get_value($ip, $oid) {
    return @snmpget($ip, "public", $oid);
}

function clean_snmp_string($val) {
    $val = trim($val);
    $val = preg_replace('/^(STRING|Counter32):\s*/', '', $val);
    return trim($val, '"');
}

function get_toner_percentage($ip, $descriptionMatch = "Black Toner") {
    $descWalk = @snmpwalk($ip, "public", "1.3.6.1.2.1.43.11.1.1.6");
    $maxWalk  = @snmpwalk($ip, "public", "1.3.6.1.2.1.43.11.1.1.8");
    $currWalk = @snmpwalk($ip, "public", "1.3.6.1.2.1.43.11.1.1.9");

    if (!$descWalk || !$maxWalk || !$currWalk) return null;

    foreach ($descWalk as $line) {
        if (preg_match('/\.(\d+)]? = STRING: \"(.*?)\"/', $line, $matches)) {
            $index = $matches[1];
            $desc = $matches[2];

            if (stripos($desc, $descriptionMatch) !== false) {
                $max = null;
                $curr = null;

                foreach ($maxWalk as $m) {
                    if (strpos($m, ".$index") !== false && preg_match('/INTEGER: (\d+)/', $m, $mm)) {
                        $max = (int) $mm[1];
                    }
                }
                foreach ($currWalk as $c) {
                    if (strpos($c, ".$index") !== false && preg_match('/INTEGER: (-?\d+)/', $c, $cc)) {
                        $curr = (int) $cc[1];
                    }
                }

                if ($max > 0 && $curr >= 0) {
                    return round(($curr / $max) * 100);
                }
            }
        }
    }

    return null;
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

if (stripos($manufacturer, 'Xerox') !== false && ($toner_black <= 0 || $toner_black > 100)) {
    $toner_black = get_toner_percentage($ip);
    file_put_contents('/tmp/snmp_debug.log', "[{$ip}] Xerox toner fallback: {$toner_black}\n", FILE_APPEND);
}

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
