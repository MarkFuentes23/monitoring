<?php
// Include database connection
require_once 'db.php';

// Check if IP ID is provided
if (!isset($_GET['ip_id']) || empty($_GET['ip_id'])) {
    header('Location: index.php');
    exit();
}

$ip_id = $_GET['ip_id'];

// Get IP details
$ipQuery = "SELECT ip_address, location, category, description FROM add_ip WHERE id = ?";
$ipStmt = $conn->prepare($ipQuery);
$ipStmt->execute([$ip_id]);
$ipDetails = $ipStmt->fetch(PDO::FETCH_ASSOC);

if (!$ipDetails) {
    header('Location: index.php');
    exit();
}

// Initialize filter variables
$filterMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$filterYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
$filterDay = isset($_GET['day']) ? $_GET['day'] : '';
$filterStartTime = isset($_GET['start_time']) ? $_GET['start_time'] : '';
$filterEndTime = isset($_GET['end_time']) ? $_GET['end_time'] : '';

// Build query with filters
$query = "SELECT pl.id, pl.latency, pl.status, pl.created_at 
          FROM ping_logs pl
          JOIN add_ip ip ON pl.ip_id = ip.id
          WHERE pl.ip_id = ? AND pl.status = 'offline'";

$params = [$ip_id];

// Apply filters
if (!empty($filterMonth) && !empty($filterYear)) {
    $query .= " AND MONTH(pl.created_at) = ? AND YEAR(pl.created_at) = ?";
    $params[] = $filterMonth;
    $params[] = $filterYear;
}

if (!empty($filterDay)) {
    $query .= " AND DAY(pl.created_at) = ?";
    $params[] = $filterDay;
}

if (!empty($filterStartTime) && !empty($filterEndTime)) {
    $query .= " AND HOUR(pl.created_at) BETWEEN ? AND ?";
    $params[] = $filterStartTime;
    $params[] = $filterEndTime;
} elseif (!empty($filterStartTime)) {
    $query .= " AND HOUR(pl.created_at) >= ?";
    $params[] = $filterStartTime;
} elseif (!empty($filterEndTime)) {
    $query .= " AND HOUR(pl.created_at) <= ?";
    $params[] = $filterEndTime;
}

$query .= " ORDER BY pl.created_at DESC";

// Get ping logs
$stmt = $conn->prepare($query);
$stmt->execute($params);
$pingLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique locations and categories for filter dropdowns
$locationQuery = "SELECT DISTINCT location FROM add_ip ORDER BY location";
$locationStmt = $conn->prepare($locationQuery);
$locationStmt->execute();
$locations = $locationStmt->fetchAll(PDO::FETCH_COLUMN);

$categoryQuery = "SELECT DISTINCT category FROM add_ip ORDER BY category";
$categoryStmt = $conn->prepare($categoryQuery);
$categoryStmt->execute();
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
?>

