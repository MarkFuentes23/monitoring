<?php
// File: ../backend/get_filter_options.php
ini_set('display_errors', 0);
error_reporting(0);

require_once '../config/db.php';
requireLogin();

try {
    $filterOptions = [
        'lan' => getFilterOptionsForCategory($conn, 'LAN'),
        'internet' => getFilterOptionsForCategory($conn, 'Internet')
    ];
    
    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode($filterOptions);
    exit;
} catch (Exception $e) {
    // Return error
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Get IP addresses and locations for a category
function getFilterOptionsForCategory($conn, $category) {
    // Get IPs for this category
    $ipStmt = $conn->prepare("
        SELECT id, ip_address, description
        FROM add_ip
        WHERE category = ? AND status != 'deleted'
        ORDER BY ip_address
    ");
    $ipStmt->execute([$category]);
    $ips = $ipStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get distinct locations for this category
    $locationStmt = $conn->prepare("
        SELECT DISTINCT location
        FROM add_ip
        WHERE category = ? AND location IS NOT NULL AND location != '' AND status != 'deleted'
        ORDER BY location
    ");
    $locationStmt->execute([$category]);
    $locations = $locationStmt->fetchAll(PDO::FETCH_COLUMN);
    
    return [
        'ips' => $ips,
        'locations' => $locations
    ];
}