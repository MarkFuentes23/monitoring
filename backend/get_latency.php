<?php
require_once '../config/db.php';

// Get all IP records
$dataRows = $conn->query("SELECT * FROM add_ip ORDER BY date DESC")
                ->fetchAll(PDO::FETCH_ASSOC);

// Loop through each record to update its latency via ping
foreach ($dataRows as &$row) {
    $ip = $row['ip_address'];
    $output = [];
    exec("ping -n 5 " . escapeshellarg($ip), $output);

    $times = [];
    $nonZeroFound = false;
    // Loop each output line looking for the ping time in ms
    foreach ($output as $line) {
        if (preg_match('/time[=<]\s*(\d+)\s*ms/i', $line, $m)) {
            $time = (int)$m[1];
            $times[] = $time;
            if ($time > 0) {
                $nonZeroFound = true;
            }
        }
    }


    if ($nonZeroFound) {
        $nonZeroTimes = array_filter($times, function($t) { return $t > 0; });
        $avg = array_sum($nonZeroTimes) / count($nonZeroTimes);
        $status = 'online';
    } else {
        $avg = 0;
        $status = 'offline';
    }
    
    $avgFormatted = number_format($avg, 2);
    

    $stmt = $conn->prepare("UPDATE add_ip SET latency = ?, status = ? WHERE id = ?");
    $stmt->execute([$avgFormatted, $status, $row['id']]);
    

    $stmt = $conn->prepare("INSERT INTO ping_logs (ip_id, latency, status) VALUES (?, ?, ?)");
    $stmt->execute([$row['id'], $avgFormatted, $status]);
    

    $latency_label = ($avgFormatted >= 100) ? 'High Latency' : 'Low Latency';
    
    $row['latency'] = $avgFormatted;
    $row['latency_label'] = $latency_label;
    $row['status'] = $status;
}

header('Content-Type: application/json');
echo json_encode($dataRows);
?>
