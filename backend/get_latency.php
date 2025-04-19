<?php
// backend/get_latency.php

require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// pull all IPs
$rows = $conn
    ->query("SELECT * FROM add_ip ORDER BY date DESC")
    ->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as &$r) {
    $ip  = escapeshellarg($r['ip_address']);
    $out = [];

    exec("ping -n 5 $ip", $out);

    $times = [];
    foreach ($out as $line) {
        if (stripos($line, 'time<1ms') !== false) {
            $times[] = 1;
        } elseif (preg_match('/time(?:=|<)\s*(\d+(?:\.\d+)?)\s*ms/i', $line, $m)) {
            $times[] = (int)$m[1];
        }
    }

    if (count($times) > 0) {
        $avg    = array_sum($times) / count($times);
        $status = 'online';
    } else {
        $avg    = 0;
        $status = 'offline';
    }

    $fmt = number_format($avg, 2);

    $u = $conn->prepare("UPDATE add_ip SET latency = ?, status = ? WHERE id = ?");
    $u->execute([$fmt, $status, $r['id']]);

    $l = $conn->prepare("INSERT INTO ping_logs (ip_id, latency, status) VALUES (?, ?, ?)");
    $l->execute([$r['id'], $fmt, $status]);

    $r['latency'] = $fmt;
    $r['status']  = $status;
}

echo json_encode($rows);
