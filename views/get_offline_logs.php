<?php
// Database connection will be available from the include
require_once __DIR__ . '/../config/db.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if required parameters are provided
if (!isset($_GET['ip_id']) || !isset($_GET['date'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameters: ip_id and date are required'
    ]);
    exit;
}

// Get parameters
$ip_id = intval($_GET['ip_id']);
$date = $_GET['date'];

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid date format. Use YYYY-MM-DD'
    ]);
    exit;
}

try {
    // Query to get offline logs for the specified IP and date
    $sql = "SELECT * FROM ping_logs 
            WHERE ip_id = ? 
            AND DATE(created_at) = ? 
            AND status = 'offline'
            ORDER BY created_at ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $ip_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $date, PDO::PARAM_STR);
    $stmt->execute();
    
    // Fetch all logs
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the logs as JSON
    echo json_encode($logs);
    
} catch (PDOException $e) {
    // Return error
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>