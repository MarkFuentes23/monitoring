<?php
// File: ../backend/get_latency_data.php
ini_set('display_errors', 0);
error_reporting(0);

require_once '../config/db.php';
requireLogin();

// Get parameters
$category = isset($_GET['category']) ? $_GET['category'] : 'LAN';
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$ipId = isset($_GET['ip_id']) ? $_GET['ip_id'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$viewType = isset($_GET['view_type']) ? $_GET['view_type'] : 'month';

// Define business hours
$businessStart = '08:00:00';
$businessEnd = '18:00:00';

try {
    if ($viewType == 'year') {
        $data = getYearlyLatencyData($conn, $year, $category, $ipId, $location, $businessStart, $businessEnd);
    } else {
        $data = getDailyLatencyData($conn, $month, $year, $category, $ipId, $location, $businessStart, $businessEnd);
    }
    
    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
} catch (Exception $e) {
    // Return error
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Get daily latency data for a specific month
function getDailyLatencyData($conn, $month, $year, $category, $ipId, $location, $businessStart, $businessEnd) {
    // Build query conditions
    $conditions = ["p.status = 'online'"];
    $params = [];
    
    // Category condition
    $conditions[] = "a.category = ?";
    $params[] = $category;
    
    // Add year and month conditions
    $conditions[] = "YEAR(p.created_at) = ?";
    $params[] = $year;
    $conditions[] = "MONTH(p.created_at) = ?";
    $params[] = $month;
    
    // Business hours condition
    $conditions[] = "TIME(p.created_at) BETWEEN ? AND ?";
    $params[] = $businessStart;
    $params[] = $businessEnd;
    
    // Optional IP filter
    if (!empty($ipId)) {
        $conditions[] = "a.id = ?";
        $params[] = $ipId;
    }
    
    // Optional location filter
    if (!empty($location)) {
        $conditions[] = "a.location = ?";
        $params[] = $location;
    }
    
    // Build the WHERE clause
    $whereClause = implode(' AND ', $conditions);
    
    // Main query for daily latency
    $query = "
        SELECT 
            DATE_FORMAT(p.created_at, '%Y-%m-%d') AS log_date,
            ROUND(AVG(p.latency), 2) AS avg_latency
        FROM ping_logs p
        JOIN add_ip a ON p.ip_id = a.id
        WHERE $whereClause
        GROUP BY log_date
        ORDER BY log_date ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get monthly latency data for a specific year
function getYearlyLatencyData($conn, $year, $category, $ipId, $location, $businessStart, $businessEnd) {
    // Build query conditions
    $conditions = ["p.status = 'online'"];
    $params = [];
    
    // Category condition
    $conditions[] = "a.category = ?";
    $params[] = $category;
    
    // Add year condition
    $conditions[] = "YEAR(p.created_at) = ?";
    $params[] = $year;
    
    // Business hours condition
    $conditions[] = "TIME(p.created_at) BETWEEN ? AND ?";
    $params[] = $businessStart;
    $params[] = $businessEnd;
    
    // Optional IP filter
    if (!empty($ipId)) {
        $conditions[] = "a.id = ?";
        $params[] = $ipId;
    }
    
    // Optional location filter
    if (!empty($location)) {
        $conditions[] = "a.location = ?";
        $params[] = $location;
    }
    
    // Build the WHERE clause
    $whereClause = implode(' AND ', $conditions);
    
    // Query for monthly averages across the year
    $query = "
        SELECT 
            MONTH(p.created_at) AS month,
            ROUND(AVG(p.latency), 2) AS avg_latency
        FROM ping_logs p
        JOIN add_ip a ON p.ip_id = a.id
        WHERE $whereClause
        GROUP BY month
        ORDER BY month ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}