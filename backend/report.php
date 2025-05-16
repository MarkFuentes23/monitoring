<?php
require_once '../config/db.php';
requireLogin();

// Validate report ID
if (!isset($_GET['report']) || !is_numeric($_GET['report'])) {
    $_SESSION['error'] = "Invalid report ID.";
    header("Location: monitoring.php");
    exit;
}
$report_id = (int)$_GET['report'];

// Default to current month
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$selectedYear = date('Y');

// New: View mode filters
$viewMode = isset($_GET['view_mode']) ? $_GET['view_mode'] : 'all';

// Date filters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '01';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('t', mktime(0,0,0,$selectedMonth,1,$selectedYear));

// Single day view
$singleDay = isset($_GET['single_day']) ? $_GET['single_day'] : date('d');

// Time filters
$startTime = isset($_GET['start_time']) ? $_GET['start_time'] : '00:00';
$endTime = isset($_GET['end_time']) ? $_GET['end_time'] : '23:59';

// Build query based on view mode
$whereClause = "WHERE p.ip_id = ? AND YEAR(p.created_at) = ? AND MONTH(p.created_at) = ?";
$params = [$report_id, $selectedYear, $selectedMonth];

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
    case 'all':
    default:
        // No additional filters
        break;
}

// Add weekday filter only for non-single day views
if ($viewMode !== 'day') {
    $whereClause .= " AND WEEKDAY(p.created_at) < 6";
}

// Fetch detailed logs with applied filters
$stmt = $conn->prepare("
    SELECT 
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
$monthly_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch device info
$stmt = $conn->prepare("SELECT * FROM add_ip WHERE id = ?");
$stmt->execute([$report_id]);
$device_data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$device_data) {
    $_SESSION['error'] = "Device not found!";
    header("Location: monitoring.php");
    exit;
}

// Fetch first ping date
$stmt = $conn->prepare("
    SELECT MIN(created_at) AS first_date
    FROM ping_logs
    WHERE ip_id = ?
");
$stmt->execute([$report_id]);
$first_ping = $stmt->fetchColumn();

$first_date = $first_ping ?: $device_data['date'];
$days_running = ceil((time() - strtotime($first_date)) / 86400);

// Calculate monthly averages by day
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m-%d') AS log_date,
        AVG(latency) AS avg_latency,
        MIN(latency) AS min_latency,
        MAX(latency) AS max_latency,
        COUNT(*) AS total_checks
    FROM ping_logs
    WHERE ip_id = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d')
    ORDER BY log_date
");
$stmt->execute([$report_id, $selectedYear, $selectedMonth]);
$monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>