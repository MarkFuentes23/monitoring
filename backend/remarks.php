<?php
// Include database connection
require_once 'db.php';

// Initialize response array
$response = array();

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $ip_id = $_POST['ip_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $time_from = $_POST['time_from'] ?? null;
    $time_to = $_POST['time_to'] ?? null;
    $remarks = $_POST['remarks'] ?? null;
    
    // Validate required fields
    if (!$ip_id || !$date || !$time_from || !$time_to || !$remarks) {
        $response = [
            'status' => 'error',
            'message' => 'All fields are required.'
        ];
    } else {
        try {
            // Format date and times for database
            $date_from = $date . ' ' . $time_from . ':00';
            $date_to = $date . ' ' . $time_to . ':00';
            
            // First, get IP information to store with remarks
            $ipQuery = "SELECT ip_address, description, category, location FROM add_ip WHERE id = :ip_id";
            $ipStmt = $conn->prepare($ipQuery);
            $ipStmt->bindParam(':ip_id', $ip_id, PDO::PARAM_INT);
            $ipStmt->execute();
            $ipInfo = $ipStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ipInfo) {
                throw new Exception("IP information not found.");
            }
            
            // Insert remark into database
            $query = "INSERT INTO offline_remarks (ip_id, ip_address, description, category, location, date_from, date_to, remarks, created_at) 
                      VALUES (:ip_id, :ip_address, :description, :category, :location, :date_from, :date_to, :remarks, NOW())";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':ip_id', $ip_id, PDO::PARAM_INT);
            $stmt->bindParam(':ip_address', $ipInfo['ip_address'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $ipInfo['description'], PDO::PARAM_STR);
            $stmt->bindParam(':category', $ipInfo['category'], PDO::PARAM_STR);
            $stmt->bindParam(':location', $ipInfo['location'], PDO::PARAM_STR);
            $stmt->bindParam(':date_from', $date_from, PDO::PARAM_STR);
            $stmt->bindParam(':date_to', $date_to, PDO::PARAM_STR);
            $stmt->bindParam(':remarks', $remarks, PDO::PARAM_STR);
            
            $stmt->execute();
            
            $response = [
                'status' => 'success',
                'message' => 'Remarks saved successfully.'
            ];
        } catch (Exception $e) {
            $response = [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else {
    // If not POST request, redirect to main page
    header("Location: index.php");
    exit;
}
?>