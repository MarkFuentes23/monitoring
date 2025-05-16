<?php
//dashboard.php = backend
require_once '../config/db.php';

// Function to get summary data
function getSummaryData($conn) {
    try {
        // Total IPs
        $totalQuery = "SELECT COUNT(*) as total FROM add_ip";
        $totalStmt = $conn->query($totalQuery);
        $totalIPs = $totalStmt->fetchColumn();
        
        // Online/Offline counts
        $statusQuery = "SELECT status, COUNT(*) as count FROM add_ip GROUP BY status";
        $statusStmt = $conn->query($statusQuery);
        
        $online = 0;
        $offline = 0;
        
        while($row = $statusStmt->fetch()) {
            if($row['status'] == 'online') {
                $online = $row['count'];
            } else if($row['status'] == 'offline') {
                $offline = $row['count'];
            }
        }
        
        return [
            'total' => $totalIPs,
            'online' => $online,
            'offline' => $offline
        ];
    } catch(PDOException $e) {
        error_log("Error in getSummaryData: " . $e->getMessage());
        return ['total' => 0, 'online' => 0, 'offline' => 0];
    }
}

// Function to get category statistics
function getCategoryStats($conn) {
    try {
        // Get all categories from the database
        $categoryQuery = "SELECT DISTINCT category FROM add_ip WHERE category IS NOT NULL AND category != ''";
        $categoryStmt = $conn->query($categoryQuery);
        $categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $categoryStats = [];
        
        foreach($categories as $category) {
            $query = "SELECT 
                        COUNT(*) as total, 
                        SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online,
                        SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline,
                        AVG(latency) as avg_latency
                      FROM add_ip 
                      WHERE category = :category";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':category', $category);
            $stmt->execute();
            $row = $stmt->fetch();
            
            $categoryStats[$category] = [
                'total' => $row['total'] ?? 0,
                'online' => $row['online'] ?? 0,
                'offline' => $row['offline'] ?? 0,
                'avg_latency' => round($row['avg_latency'] ?? 0, 2)
            ];
        }
        
        return $categoryStats;
    } catch(PDOException $e) {
        error_log("Error in getCategoryStats: " . $e->getMessage());
        return [];
    }
}

// Function to get offline devices
function getOfflineDevices($conn) {
    try {
        $query = "
           SELECT id,
                    ip_address,
                   location,
                   category,
                   description,
                   status
            FROM add_ip
            WHERE status = 'offline'
            ORDER BY category ASC, location ASC
        ";
        
        $stmt = $conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error in getOfflineDevices: " . $e->getMessage());
        return [];
    }
}


// Function to get high latency devices
function getHighLatencyDevices($conn) {
    try {
        // Kukunin na ngayon lahat ng online devices at isa-sort by latency DESC
        $query = "SELECT id,ip_address, location, category, description, latency 
                  FROM add_ip 
                  WHERE status = 'online'
                  ORDER BY latency DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error in getHighLatencyDevices: " . $e->getMessage());
        return [];
    }
}

// Function to update latency data - similar to your get_latency.php
function updateLatencyData($conn) {
    try {
        // Get current status of all IPs for comparison later
        $currentStatusQuery = "SELECT id, ip_address, status FROM add_ip";
        $currentStatusStmt = $conn->query($currentStatusQuery);
        $currentStatus = [];
        while ($row = $currentStatusStmt->fetch(PDO::FETCH_ASSOC)) {
            $currentStatus[$row['id']] = [
                'ip_address' => $row['ip_address'],
                'status' => $row['status']
            ];
        }

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
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"]
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
        $newlyOfflineDevices = [];
        
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

            // Check if device just went offline
            if ($status == 'offline' && isset($currentStatus[$row['id']]) && $currentStatus[$row['id']]['status'] == 'online') {
                $newlyOfflineDevices[] = [
                    'ip_address' => $row['ip_address'],
                    'location' => $row['location'] ?? 'Unknown',
                    'category' => $row['category'] ?? 'Unknown'
                ];
            }

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

        return [
            'devices' => $response,
            'newlyOfflineDevices' => $newlyOfflineDevices
        ];
    } catch(Exception $e) {
        error_log("Error in updateLatencyData: " . $e->getMessage());
        return [
            'devices' => [],
            'newlyOfflineDevices' => []
        ];
    }
}

// Require login to access this page
requireLogin();

// Check if this is an AJAX refresh request
if(isset($_GET['refresh']) && $_GET['refresh'] === 'true') {
    $updateResult = ['newlyOfflineDevices' => []];
    
    if(isset($_GET['force']) && $_GET['force'] === 'true') {
        $updateResult = updateLatencyData($conn);
    }
    
    $summaryData = getSummaryData($conn);
    $categoryStats = getCategoryStats($conn);
    $offlineDevices = getOfflineDevices($conn);
    $highLatencyDevices = getHighLatencyDevices($conn);
    
    header('Content-Type: application/json');
    echo json_encode([
        'summary' => $summaryData,
        'categories' => $categoryStats,
        'offlineDevices' => $offlineDevices,
        'highLatencyDevices' => $highLatencyDevices,
        'timestamp' => date('Y-m-d H:i:s'),
        'newlyOfflineDevices' => $updateResult['newlyOfflineDevices']
    ]);
    exit;
}

// For first page load, update latency data if it hasn't been updated in the last 15 minutes
$lastUpdateQuery = "SELECT MAX(created_at) as last_update FROM ping_logs";
$lastUpdateStmt = $conn->query($lastUpdateQuery);
$lastUpdate = $lastUpdateStmt->fetchColumn();

$fifteenMinutesAgo = date('Y-m-d H:i:s', strtotime('-15 minutes'));
if (!$lastUpdate || $lastUpdate < $fifteenMinutesAgo) {
    updateLatencyData($conn);
}

// Fetch data for initial display
$summaryData = getSummaryData($conn);
$categoryStats = getCategoryStats($conn);
$offlineDevices = getOfflineDevices($conn);
$highLatencyDevices = getHighLatencyDevices($conn);


?>