<?php
require_once '../config/db.php';
requireLogin();

/**
 * Get global monitoring data across all devices with filters
 * 
 * @param array $filters Array containing filter parameters
 * @return array All monitoring data with filters applied
 */
function getGlobalMonitoringData($filters = []) {
    global $conn;
    
    // Extract filter values with defaults
    $selectedMonth = $filters['month'] ?? date('n');
    $selectedYear = $filters['year'] ?? date('Y');
    $viewMode = $filters['view_mode'] ?? 'all';
    $startDate = $filters['start_date'] ?? '01';
    $endDate = $filters['end_date'] ?? date('t', mktime(0,0,0,$selectedMonth,1,$selectedYear));
    $singleDay = $filters['single_day'] ?? date('d');
    $startTime = $filters['start_time'] ?? '00:00';
    $endTime = $filters['end_time'] ?? '23:59';
    $location = $filters['location'] ?? '';
    $category = $filters['category'] ?? '';
    $status = $filters['status'] ?? '';
    
    // Build base query
    $whereClause = "WHERE YEAR(p.created_at) = ? AND MONTH(p.created_at) = ?";
    $params = [$selectedYear, $selectedMonth];
    
    // Add filters based on view mode
    switch ($viewMode) {
        case 'day':
            $whereClause .= " AND DAY(p.created_at) = ?";
            $params[] = $singleDay;
            break;
        case 'date_range':
            $whereClause .= " AND DAY(p.created_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            break;
        case 'time_only':
            $whereClause .= " AND TIME(p.created_at) BETWEEN ? AND ?";
            $params[] = $startTime;
            $params[] = $endTime;
            break;
        case 'custom':
            $whereClause .= " AND DAY(p.created_at) BETWEEN ? AND ? AND TIME(p.created_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $params[] = $startTime;
            $params[] = $endTime;
            break;
    }
    
    // Add weekday filter for business days (Monday-Saturday)
    if ($viewMode !== 'day') {
        $whereClause .= " AND WEEKDAY(p.created_at) < 6";
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
    
    // Add status filter
    if (!empty($status)) {
        $whereClause .= " AND p.status = ?";
        $params[] = $status;
    }
    
    // Execute query to get detailed logs with applied filters
    $stmt = $conn->prepare("
        SELECT 
            p.id,
            p.ip_id,
            p.created_at, 
            p.latency, 
            p.status,
            a.ip_address,
            a.location,
            a.category,
            a.description
        FROM ping_logs p
        JOIN add_ip a ON p.ip_id = a.id
        $whereClause
        ORDER BY p.created_at
    ");
    
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'logs' => $logs,
        'filters' => [
            'month' => $selectedMonth,
            'year' => $selectedYear,
            'view_mode' => $viewMode,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'single_day' => $singleDay,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'location' => $location,
            'category' => $category,
            'status' => $status
        ]
    ];
}

/**
 * Get available filters options (locations, categories)
 * 
 * @return array All filter options
 */
function getFilterOptions() {
    global $conn;
    
    // Get all locations
    $stmt = $conn->prepare("SELECT DISTINCT location FROM add_ip ORDER BY location");
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get all categories
    $stmt = $conn->prepare("SELECT DISTINCT category FROM add_ip ORDER BY category");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get all IPs
    $stmt = $conn->prepare("SELECT id, ip_address, description FROM add_ip ORDER BY ip_address");
    $stmt->execute();
    $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'locations' => $locations,
        'categories' => $categories,
        'ips' => $ips
    ];
}