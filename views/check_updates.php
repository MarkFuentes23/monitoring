<?php
// check_updates.php - Endpoint to check if there are server-side updates

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Get the client's last update timestamp
$lastClientUpdate = isset($_GET['last_update']) ? (int)$_GET['last_update'] : 0;

// Read the server's last update timestamp from file
$updateFile = 'last_update.txt';
$serverTimestamp = 0;

if (file_exists($updateFile)) {
    $serverTimestamp = (int)file_get_contents($updateFile);
} else {
    // Create the file if it doesn't exist
    file_put_contents($updateFile, time() * 1000);
    $serverTimestamp = time() * 1000;
}

// Check if the server has newer data than the client
$hasUpdate = ($serverTimestamp > $lastClientUpdate);

// Return JSON response
echo json_encode([
    'hasUpdate' => $hasUpdate,
    'serverTimestamp' => $serverTimestamp
]);
?>