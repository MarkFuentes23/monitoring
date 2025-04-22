<?php
// backend/get_latency.php

require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// Get all IPs
$rows = $conn
    ->query("SELECT * FROM add_ip ORDER BY date DESC")
    ->fetchAll(PDO::FETCH_ASSOC);

// Use parallel processing with proc_open
$processes = [];
$pipes = [];
$results = [];

// Start all ping processes simultaneously
foreach ($rows as $index => $row) {
    $ip = escapeshellarg($row['ip_address']);
    $cmd = "ping -n 5 $ip";
    
    $descriptorspec = [
        0 => ["pipe", "r"],  // stdin
        1 => ["pipe", "w"],  // stdout
        2 => ["pipe", "w"]   // stderr
    ];
    
    $processes[$index] = proc_open($cmd, $descriptorspec, $pipes[$index]);
    
    // Non-blocking
    stream_set_blocking($pipes[$index][1], 0);
    
    // Store reference to row
    $results[$index] = [
        'id' => $row['id'],
        'row' => $row,
        'output' => "",
        'complete' => false
    ];
}

// Poll until all processes complete
$running = true;
while ($running) {
    $running = false;
    
    foreach ($processes as $index => $process) {
        if ($results[$index]['complete']) {
            continue;
        }
        
        // Read available output
        $output = stream_get_contents($pipes[$index][1]);
        if ($output) {
            $results[$index]['output'] .= $output;
        }
        
        // Check if process is still running
        $status = proc_get_status($process);
        if (!$status['running']) {
            $results[$index]['complete'] = true;
            
            // Get any remaining output
            $results[$index]['output'] .= stream_get_contents($pipes[$index][1]);
            
            // Close pipes
            fclose($pipes[$index][0]);
            fclose($pipes[$index][1]);
            fclose($pipes[$index][2]);
            
            // Close process
            proc_close($process);
        } else {
            $running = true;
        }
    }
    
    // Small sleep to prevent CPU hogging
    if ($running) {
        usleep(10000); // 10ms
    }
}

// Process results
$response = [];
foreach ($results as $result) {
    $row = $result['row'];
    $output = $result['output'];
    
    // Parse ping results
    $times = [];
    foreach (explode("\n", $output) as $line) {
        if (stripos($line, 'time<1ms') !== false) {
            $times[] = 1;
        } elseif (preg_match('/time(?:=|<)\s*(\d+(?:\.\d+)?)\s*ms/i', $line, $m)) {
            $times[] = (int)$m[1];
        }
    }

    if (count($times) > 0) {
        $avg = array_sum($times) / count($times);
        $status = 'online';
    } else {
        $avg = 0;
        $status = 'offline';
    }

    $fmt = number_format($avg, 2);

    // Update database
    $u = $conn->prepare("UPDATE add_ip SET latency = ?, status = ? WHERE id = ?");
    $u->execute([$fmt, $status, $row['id']]);

    $l = $conn->prepare("INSERT INTO ping_logs (ip_id, latency, status) VALUES (?, ?, ?)");
    $l->execute([$row['id'], $fmt, $status]);

    // Add to response
    $row['latency'] = $fmt;
    $row['status'] = $status;
    $response[] = $row;
}

echo json_encode($response);