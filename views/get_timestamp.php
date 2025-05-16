<?php
// get_timestamp.php - Get the latest update timestamp from server

// Set headers to prevent caching
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: application/json');

// Path to timestamp file
$file_path = __DIR__ . '/../data/last_update.txt';

// Check if file exists
if (file_exists($file_path)) {
    // Read timestamp from file
    $timestamp = file_get_contents($file_path);
    
    // Validate timestamp
    if (is_numeric($timestamp)) {
        echo json_encode(['timestamp' => $timestamp]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Invalid timestamp format in file']);
    }
} else {
    // No timestamp file found
    echo json_encode(['timestamp' => 0]);
}
?>