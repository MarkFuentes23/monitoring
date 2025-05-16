<?php
require_once '../config/db.php';
requireLogin();

// First get month and year separately to avoid circular reference
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get filter parameters from URL
$filters = [
    'month' => $currentMonth,
    'year' => $currentYear,
    'view_mode' => isset($_GET['view_mode']) ? $_GET['view_mode'] : 'custom',
    'start_date' => isset($_GET['start_date']) ? $_GET['start_date'] : '01',
    'end_date' => isset($_GET['end_date']) ? $_GET['end_date'] : date('t', mktime(0,0,0,$currentMonth,1,$currentYear)),
    'single_day' => isset($_GET['single_day']) ? $_GET['single_day'] : date('d'),
    'start_time' => isset($_GET['start_time']) ? $_GET['start_time'] : '00:00',
    'end_time' => isset($_GET['end_time']) ? $_GET['end_time'] : '23:59',
    'location' => isset($_GET['location']) ? $_GET['location'] : '',
    'category' => isset($_GET['category']) ? $_GET['category'] : '',
    'status' => isset($_GET['status']) ? $_GET['status'] : '',
    'ip_id' => isset($_GET['ip_id']) ? $_GET['ip_id'] : '',
    // Add weekday filters
    'weekdays' => isset($_GET['weekdays']) ? $_GET['weekdays'] : ['1','2','3','4','5','6'] // Default Mon-Sat
];

// Then modify the getGlobalMonitoringData function in overall_report.php to include weekday filtering:

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
    $weekdays = $filters['weekdays'] ?? ['1','2','3','4','5','6']; // Default Mon-Sat

    
    
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
    
    // Add weekday filter based on selected weekdays (1=Monday, 7=Sunday in MySQL)
    if (!empty($weekdays)) {
        $placeholders = implode(',', array_fill(0, count($weekdays), '?'));
        $whereClause .= " AND WEEKDAY(p.created_at) IN ($placeholders)";
        $params = array_merge($params, $weekdays);
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
    
    // Add specific device filter
    if (!empty($filters['ip_id'])) {
        $whereClause .= " AND p.ip_id = ?";
        $params[] = $filters['ip_id'];
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
            'status' => $status,
            'weekdays' => $weekdays
        ]
    ];
}

$whereClauses = [];
$params       = [];  

// 2) build the same filters you use elsewhere
if (!empty($filters['location'])) {
    $whereClauses[]      = 'location = :location';
    $params[':location'] = $filters['location'];
}
if (!empty($filters['category'])) {
    $whereClauses[]       = 'category = :category';
    $params[':category']  = $filters['category'];
}
if (!empty($filters['ip_id'])) {
    $whereClauses[]    = 'id = :ip_id';
    $params[':ip_id']  = $filters['ip_id'];
}

// 3) assemble WHERE
$whereSQL = $whereClauses 
          ? 'WHERE ' . implode(' AND ', $whereClauses) 
          : '';

// 4) run the COUNT(*) query
$sql  = "SELECT COUNT(*) FROM add_ip $whereSQL";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$totalDevices = (int) $stmt->fetchColumn();
