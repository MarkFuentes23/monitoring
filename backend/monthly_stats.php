<?php
/**
 * Get monthly uptime statistics for all devices using PDO
 * 
 * @param PDO $conn Database connection
 * @return array Monthly uptime statistics for each device
 */
function getMonthlyUptimeStats($conn) {
    $currentMonth = date('m');
    $currentYear = date('Y');
    $results = [];
    
    // Get all devices
    $query = "SELECT id, ip_address, location, category, description FROM add_ip";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($devices as $device) {
        $deviceId = $device['id'];
        
        // Get ping logs for the current month during business hours
        $query = "SELECT DATE(created_at) as log_date, 
                         TIME(created_at) as log_time, 
                         status 
                  FROM ping_logs 
                  WHERE ip_id = ? 
                    AND MONTH(created_at) = ? 
                    AND YEAR(created_at) = ?
                    AND TIME(created_at) BETWEEN '08:00:00' AND '18:00:00'
                  ORDER BY created_at";
                  
        $stmt = $conn->prepare($query);
        $stmt->execute([$deviceId, $currentMonth, $currentYear]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $offlineEvents = 0;
        $totalDays = 0;
        $dailyOfflineCount = [];
        $lastDay = null;
        $consecutiveOffline = 0;
        $previousStatuses = ['online', 'online', 'online']; // Default statuses
        
        // Process logs to count offline events
        foreach ($logs as $log) {
            $currentDay = $log['log_date'];
            $status = $log['status'] === 'offline' ? 'offline' : 'online';
            
            // Initialize counts for new day
            if ($currentDay !== $lastDay) {
                if ($lastDay !== null) {
                    $totalDays++;
                }
                $lastDay = $currentDay;
                if (!isset($dailyOfflineCount[$currentDay])) {
                    $dailyOfflineCount[$currentDay] = 0;
                }
            }
            
            // Track consecutive offline statuses
            array_shift($previousStatuses);
            $previousStatuses[] = $status;
            
            // Check for 3 consecutive offline statuses
            if ($previousStatuses[0] === 'offline' && 
                $previousStatuses[1] === 'offline' && 
                $previousStatuses[2] === 'offline') {
                // Count as offline event only once per sequence
                if ($consecutiveOffline === 0) {
                    $dailyOfflineCount[$currentDay]++;
                    $offlineEvents++;
                }
                $consecutiveOffline++;
            } else if ($status === 'online') {
                $consecutiveOffline = 0;
            }
        }
        
        // Count the last day
        if ($lastDay !== null) {
            $totalDays++;
        }
        
        // Calculate uptime percentages
        $totalDailyPercentages = 0;
        $daysWithData = count($dailyOfflineCount);
        
        foreach ($dailyOfflineCount as $offlineCount) {
            // 40 pings per day (15-minute intervals over 10 hours)
            $dailyUptimePercentage = max(0, 100 - ($offlineCount / 40 * 100));
            $totalDailyPercentages += $dailyUptimePercentage;
        }
        
        // Calculate monthly average
        $monthlyUptimePercentage = $daysWithData > 0 ? $totalDailyPercentages / $daysWithData : 100;
        
        // Add to results
        $results[] = [
            'ip_address' => $device['ip_address'],
            'location' => $device['location'],
            'category' => $device['category'],
            'description' => $device['description'],
            'offline_events' => $offlineEvents,
            'uptime_percentage' => $monthlyUptimePercentage
        ];
    }
    
    return $results;
}

