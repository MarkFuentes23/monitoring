<?php
// update_timestamp.php - Save update timestamp to server

// Set headers to prevent caching
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: application/json');

// Check if timestamp is provided
if (!isset($_POST['timestamp'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No timestamp provided']);
    exit;
}

// Get the timestamp
$timestamp = $_POST['timestamp'];

// Validate timestamp is a number
if (!is_numeric($timestamp)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid timestamp format']);
    exit;
}

// Path to timestamp file (in a secure location)
$file_path = __DIR__ . '/../data/last_update.txt';

// Create directory if it doesn't exist
$dir_path = dirname($file_path);
if (!is_dir($dir_path)) {
    mkdir($dir_path, 0755, true);
}

// Save timestamp to file
if (file_put_contents($file_path, $timestamp)) {
    echo json_encode(['success' => true, 'timestamp' => $timestamp]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save timestamp']);
}
?>