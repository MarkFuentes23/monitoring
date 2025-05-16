<?php
require_once '../config/db.php';
requireLogin();

// Set headers for JSON response
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed'
    ]);
    exit;
}

// Validate input parameters
if (empty($_POST['date']) || empty($_POST['report_id']) || empty($_POST['action'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$date = $_POST['date'];
$report_id = intval($_POST['report_id']);
$action = $_POST['action'];
$checked_remarks = !empty($_POST['checked_remarks']) ? json_decode($_POST['checked_remarks'], true) : [];

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid date format'
    ]);
    exit;
}

// Connect to database (using the global $conn from db.php)
global $conn;

try {
    if ($action === 'clear_offline') {
        // Assuming user_id is stored in session, use a default if not available
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
        
        // Create offline_exclusions table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS offline_exclusions (
            id INT(11) NOT NULL AUTO_INCREMENT,
            ip_id INT(11) NOT NULL,
            exclusion_date DATE NOT NULL,
            excluded_by INT(11) DEFAULT NULL,
            excluded_at DATETIME NOT NULL,
            reason VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY ip_date (ip_id, exclusion_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $conn->exec($sql);
        
        // First check if the needed columns exist in offline_remarks
        $columnCheckStmt = $conn->prepare("SHOW COLUMNS FROM offline_remarks LIKE 'is_excluded'");
        $columnCheckStmt->execute();
        if ($columnCheckStmt->rowCount() == 0) {
            // Add the column if it doesn't exist
            $conn->exec("ALTER TABLE offline_remarks ADD COLUMN is_excluded TINYINT(1) DEFAULT 0");
            $conn->exec("ALTER TABLE offline_remarks ADD COLUMN excluded_by INT(11) DEFAULT NULL");
            $conn->exec("ALTER TABLE offline_remarks ADD COLUMN excluded_at DATETIME DEFAULT NULL");
        }
        
        // Get the IDs of the remarks from their element IDs
        $excluded_remark_ids = [];
        foreach ($checked_remarks as $remark) {
            // Check if we have time information
            if (isset($remark['time'])) {
                $time_text = $remark['time'];
                // Extract the from time (12:57pm format to 12:57:00 format)
                $parts = explode(' – ', $time_text);
                if (!empty($parts[0])) {
                    $from_time = trim($parts[0]);
                    
                    // Convert 12-hour format to 24-hour format
                    $time_obj = DateTime::createFromFormat('h:ia', $from_time);
                    if ($time_obj) {
                        $formatted_time = $time_obj->format('H:i:s');
                        
                        // Now get the database ID for this remark
                        $startOfDay = $date . ' 00:00:00';
                        $endOfDay = $date . ' 23:59:59';
                        
                        $remarkStmt = $conn->prepare("
                            SELECT id FROM offline_remarks 
                            WHERE ip_id = ? 
                            AND date_from BETWEEN ? AND ?
                            AND TIME(date_from) = ?
                            AND is_excluded = 0
                            LIMIT 1
                        ");
                        $remarkStmt->execute([
                            $report_id,
                            $startOfDay,
                            $endOfDay,
                            $formatted_time
                        ]);
                        
                        $result = $remarkStmt->fetch(PDO::FETCH_ASSOC);
                        if ($result) {
                            $excluded_remark_ids[] = $result['id'];
                        }
                    }
                }
            }
        }
        
        // If we couldn't find any matching remarks through time format, try to get all remarks for this day
        if (empty($excluded_remark_ids)) {
            $startOfDay = $date . ' 00:00:00';
            $endOfDay = $date . ' 23:59:59';
            
            $allRemarksStmt = $conn->prepare("
                SELECT id FROM offline_remarks 
                WHERE ip_id = ? 
                AND date_from BETWEEN ? AND ?
                AND is_excluded = 0
            ");
            $allRemarksStmt->execute([
                $report_id,
                $startOfDay,
                $endOfDay
            ]);
            
            while ($row = $allRemarksStmt->fetch(PDO::FETCH_ASSOC)) {
                $excluded_remark_ids[] = $row['id'];
            }
        }
        
        // If we have remarks to exclude
        if (!empty($excluded_remark_ids)) {
            // NOW start a transaction since we'll be making multiple related updates
            $conn->beginTransaction();
            
            try {
                // Insert a record into the exclusions table for tracking
                $stmt = $conn->prepare("
                    INSERT INTO offline_exclusions 
                    (ip_id, exclusion_date, excluded_by, excluded_at, reason) 
                    VALUES (?, ?, ?, NOW(), 'Manually cleared by user')
                ");
                
                $stmt->execute([
                    $report_id,
                    $date,
                    $user_id
                ]);
                
                // Get the exclusion ID to relate to the remarks
                $exclusion_id = $conn->lastInsertId();
                
                // Update the offline_remarks for the checked items
                $placeholders = rtrim(str_repeat('?,', count($excluded_remark_ids)), ',');
                $query = "
                    UPDATE offline_remarks
                    SET is_excluded = 1, 
                        excluded_by = ?, 
                        excluded_at = NOW()
                    WHERE id IN ($placeholders)
                ";
                
                $params = [$user_id];
                foreach ($excluded_remark_ids as $id) {
                    $params[] = $id;
                }
                
                $stmtUpdate = $conn->prepare($query);
                $stmtUpdate->execute($params);
                
                // Commit transaction
                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Offline events cleared successfully',
                    'excluded_count' => count($excluded_remark_ids)
                ]);
            } catch (PDOException $innerException) {
                // Rollback transaction on error
                $conn->rollBack();
                throw $innerException; 
            }
        } else {
            // No remarks found or matched
            echo json_encode([
                'success' => false,
                'message' => 'No matching offline events found to clear',
                'debug' => [
                    'date' => $date,
                    'report_id' => $report_id,
                    'checked_remarks' => $checked_remarks
                ]
            ]);
        }
    } 
    // NEW CODE: Handle the restore_offline action
    else if ($action === 'restore_offline') {
        // Get all excluded remarks for this date
        $startOfDay = $date . ' 00:00:00';
        $endOfDay = $date . ' 23:59:59';
        
        // Start a transaction
        $conn->beginTransaction();
        
        try {
            // Get the total count of excluded remarks to restore for this day
            $countStmt = $conn->prepare("
                SELECT COUNT(*) as count FROM offline_remarks 
                WHERE ip_id = ? 
                AND date_from BETWEEN ? AND ?
                AND is_excluded = 1
            ");
            $countStmt->execute([
                $report_id,
                $startOfDay,
                $endOfDay
            ]);
            $restoredCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Un-exclude all remarks for this day
            $updateStmt = $conn->prepare("
                UPDATE offline_remarks
                SET is_excluded = 0, 
                    excluded_by = NULL, 
                    excluded_at = NULL
                WHERE ip_id = ? 
                AND date_from BETWEEN ? AND ?
                AND is_excluded = 1
            ");
            $updateStmt->execute([
                $report_id,
                $startOfDay,
                $endOfDay
            ]);
            
            // Delete from exclusions table
            $deleteStmt = $conn->prepare("
                DELETE FROM offline_exclusions
                WHERE ip_id = ? AND exclusion_date = ?
            ");
            $deleteStmt->execute([
                $report_id,
                $date
            ]);
            
            // Commit transaction
            $conn->commit();

            $offlineCount = $restoredCount;
            
            // Get business hours constants - must match getMonthlyAverageData
            $businessHoursPerDay = 10; // 8am-6pm = 10 hours
            $intervalsPerDay = $businessHoursPerDay * 4; // 40 pings/day
            
            // Calculate uptime percentage
            $uptimePercent = 100 - (($offlineCount / $intervalsPerDay) * 100);
            $formattedUptime = number_format($uptimePercent, 2);
            
            echo json_encode([
                'success' => true,
                'message' => 'Offline events restored successfully',
                'restored_count' => $restoredCount,
                'offline_count' => $offlineCount,
                'uptime_percent' => $formattedUptime
            ]);
        } catch (PDOException $innerException) {
            // Rollback transaction on error
            $conn->rollBack();
            throw $innerException;
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}