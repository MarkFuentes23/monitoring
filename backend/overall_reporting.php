<?php
/**
 * Calculate daily statistics for all IPs or filtered IPs
 * 
 * @param array $filters Filter parameters
 * @return array Daily statistics
 */
function getGlobalDailyStats($filters = []) {
    global $conn;
    
    // Extract filter values
    $selectedMonth = $filters['month'] ?? date('n');
    $selectedYear = $filters['year'] ?? date('Y');
    $location = $filters['location'] ?? '';
    $category = $filters['category'] ?? '';
    $ip_id = $filters['ip_id'] ?? '';
    
    // Build base query
    $whereClause = "WHERE YEAR(p.created_at) = ? AND MONTH(p.created_at) = ?";
    $params = [$selectedYear, $selectedMonth];
    
    // Add IP filter if specified
    if (!empty($ip_id)) {
        $whereClause .= " AND p.ip_id = ?";
        $params[] = $ip_id;
    }
    
    // Add location filter
    if (!empty($location)) {
        $whereClause .= " AND a.location = ?";
        $params[] = $location;
    }
    
    // Add category filter
    if (!empty($category)) {
        $whereClause .= " AND a.category = ?";
        $params[] = $category;
    }
    
    // Define business hours window for uptime calculation
    $businessStart = '08:00:00';
    $businessEnd = '18:00:00';
    $businessHoursPerDay = (strtotime($businessEnd) - strtotime($businessStart)) / 3600; // 10 hours
    $intervalsPerDay = $businessHoursPerDay * 4; // 4 pings per hour (40 pings per day)
    
    // Get daily stats
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(p.created_at, '%Y-%m-%d') AS log_date,
            COUNT(DISTINCT p.ip_id) AS device_count,
            AVG(p.latency) AS avg_latency,
            MIN(p.latency) AS min_latency,
            MAX(p.latency) AS max_latency,
            COUNT(*) AS total_checks,
            SUM(CASE WHEN p.status = 'offline' THEN 1 ELSE 0 END) AS offline_count
        FROM ping_logs p
        JOIN add_ip a ON p.ip_id = a.id
        $whereClause
        GROUP BY log_date
        ORDER BY log_date
    ");
    
    $stmt->execute($params);
    $daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate uptime percentage for each day
    foreach ($daily_stats as &$day) {
        // Calculate devices count for expected check count
        $expected_checks = $day['device_count'] * $intervalsPerDay;
        $day['uptime_percent'] = 100 - (($day['offline_count'] / $expected_checks) * 100);
        
        // Add status text based on uptime percentage
        $u = $day['uptime_percent'];
        if ($u == 100) {
            $day['status_text'] = 'Excellent';
            $day['status_class'] = 'bg-success text-white';
        } elseif ($u >= 99.5) {
            $day['status_text'] = 'Very Good';
            $day['status_class'] = 'bg-success text-white';
        } elseif ($u >= 95) {
            $day['status_text'] = 'Average';
            $day['status_class'] = 'bg-warning';
        } else {
            $day['status_text'] = 'Poor';
            $day['status_class'] = 'bg-danger text-white';
        }
    }
    unset($day);
    
    // Calculate monthly totals/averages
    $total_days = count($daily_stats);
    $monthly_total = [
        'avg_latency' => 0,
        'min_latency' => 0,
        'max_latency' => 0,
        'total_checks' => 0,
        'total_offline_count' => 0,
        'avg_uptime_percent' => 0,
    ];
    
    if ($total_days > 0) {
        $sum_latency = array_sum(array_column($daily_stats, 'avg_latency'));
        $min_vals = array_column($daily_stats, 'min_latency');
        $max_vals = array_column($daily_stats, 'max_latency');
        $total_checks = array_sum(array_column($daily_stats, 'total_checks'));
        $total_offline = array_sum(array_column($daily_stats, 'offline_count'));
        
        $monthly_total = [
            'avg_latency' => $sum_latency / $total_days,
            'min_latency' => min($min_vals),
            'max_latency' => max($max_vals),
            'total_checks' => $total_checks,
            'total_offline_count' => $total_offline,
            'avg_uptime_percent' => 100 - (($total_offline / $total_checks) * 100),
        ];
        
        // Calculate status for monthly total
        $u = $monthly_total['avg_uptime_percent'];
        if ($u == 100) {
            $monthly_total['status_text'] = 'Excellent';
            $monthly_total['status_class'] = 'bg-success text-white';
        } elseif ($u >= 99.5) {
            $monthly_total['status_text'] = 'Very Good';
            $monthly_total['status_class'] = 'bg-success text-white';
        } elseif ($u >= 95) {
            $monthly_total['status_text'] = 'Average';
            $monthly_total['status_class'] = 'bg-warning';
        } else {
            $monthly_total['status_text'] = 'Poor';
            $monthly_total['status_class'] = 'bg-danger text-white';
        }
    }
    
    return [
        'daily_stats' => $daily_stats,
        'monthly_total' => $monthly_total,
        'total_days' => $total_days
    ];
}

/**
 * Get device-specific statistics with offline periods detection
 * 
 * @param array $filters Filter parameters
 * @return array Device statistics by IP
 */
function getDeviceStats($filters = []) {
    global $conn;
    
    $selectedMonth = $filters['month'] ?? date('n');
    $selectedYear = $filters['year'] ?? date('Y');
    $location = $filters['location'] ?? '';
    $category = $filters['category'] ?? '';
    
    // Build filters
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($location)) {
        $whereClause .= " AND a.location = ?";
        $params[] = $location;
    }
    
    if (!empty($category)) {
        $whereClause .= " AND a.category = ?";
        $params[] = $category;
    }
    
    // Get all active devices with filters
  // NEW: join ka sa ping_logs at isali ang p.created_at
$stmt = $conn->prepare("
    SELECT 
        a.id,
        a.ip_address,
        a.description,
        a.location,
        a.category,
        p.latency,
        p.status,
        p.created_at    -- dito kukunin ang timestamp
    FROM add_ip a
    JOIN ping_logs p 
      ON p.ip_id = a.id
    $whereClause
    ORDER BY p.created_at DESC
");

    
    $stmt->execute($params);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Define business hours window
    $businessStart = '08:00:00';
    $businessEnd = '18:00:00';
    $businessHoursPerDay = (strtotime($businessEnd) - strtotime($businessStart)) / 3600; // 10 hours
    $intervalsPerDay = $businessHoursPerDay * 4; // 4 pings per hour (40 pings per day)
    
    // Get stats for each device
    foreach ($devices as &$device) {
        // Get monthly stats
        $stmt = $conn->prepare("
            SELECT 
                AVG(latency) AS avg_latency,
                MIN(latency) AS min_latency,
                MAX(latency) AS max_latency,
                COUNT(*) AS total_checks,
                SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) AS offline_count,
                COUNT(DISTINCT DATE_FORMAT(created_at, '%Y-%m-%d')) AS days_running
            FROM ping_logs
            WHERE ip_id = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ?
        ");
        
        $stmt->execute([$device['id'], $selectedYear, $selectedMonth]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($stats && $stats['total_checks'] > 0) {
            $device['avg_latency'] = $stats['avg_latency'];
            $device['min_latency'] = $stats['min_latency'];
            $device['max_latency'] = $stats['max_latency'];
            $device['total_checks'] = $stats['total_checks'];
            $device['offline_count'] = $stats['offline_count'];
            $device['days_running'] = $stats['days_running'];
            $device['uptime_percent'] = 100 - (($stats['offline_count'] / $stats['total_checks']) * 100);
            
            // Add status text based on uptime percentage
            $u = $device['uptime_percent'];
            if ($u == 100) {
                $device['status_text'] = 'Excellent';
                $device['status_class'] = 'bg-success text-white';
            } elseif ($u >= 99.5) {
                $device['status_text'] = 'Very Good';
                $device['status_class'] = 'bg-success text-white';
            } elseif ($u >= 95) {
                $device['status_text'] = 'Average';
                $device['status_class'] = 'bg-warning';
            } else {
                $device['status_text'] = 'Poor';
                $device['status_class'] = 'bg-danger text-white';
            }
        } else {
            // No data for this device
            $device['avg_latency'] = 0;
            $device['min_latency'] = 0;
            $device['max_latency'] = 0;
            $device['total_checks'] = 0;
            $device['offline_count'] = 0;
            $device['days_running'] = 0;
            $device['uptime_percent'] = 0;
            $device['status_text'] = 'No Data';
            $device['status_class'] = 'bg-secondary text-white';
        }
    }
    
    return $devices;
}