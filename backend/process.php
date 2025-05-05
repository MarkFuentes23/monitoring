<?php
// start session & import DB + helpers
session_start();
require_once __DIR__ . '/../config/db.php';        // adjust path as needed


// --- Authentication Functions ---

function loginUser($username, $password) {
    global $conn;
    $response = ['success' => false, 'message' => ''];

    if (empty($username) || empty($password)) {
        $response['message'] = "Please enter both username and password";
        return $response;
    }

    try {
        // Add role to the SELECT statement
        $sql  = "SELECT id, username, password, role FROM users WHERE username = :username";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':username' => $username]);

        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role']; // Add this line to store role
                $response['success']  = true;
            } else {
                $response['message'] = "Invalid username or password";
            }
        } else {
            $response['message'] = "Invalid username or password";
        }
    } catch (PDOException $e) {
        $response['message'] = "Database error: " . $e->getMessage();
    }

    return $response;
}


// --- Data Table Functions ---

function addDataRow($ip, $description, $location, $category) {
    global $conn;
    $response = ['success' => false, 'message' => ''];

    // Validate required fields (date is now auto-handled by the DB)
    if (!$ip || !$description || !$location || !$category) {
        $response['message'] = "All fields are required.";
        return $response;
    }

    try {
        // Insert without the date column; database default will set the current date
        $sql = "INSERT INTO add_ip (ip_address, description, location, category)
                VALUES (:ip, :desc, :loc, :cat)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':ip'   => $ip,
            ':desc' => $description,
            ':loc'  => $location,
            ':cat'  => $category
        ]);
        $newId = $conn->lastInsertId();


        pingAndUpdate($newId, $ip);

        $response['success'] = true;
        $response['message'] = "Data added and pinged successfully.";
    } catch (PDOException $e) {
        $response['message'] = "Database error: " . $e->getMessage();
    }

    return $response;
}


function pingAndUpdate($id, $ip) {
    global $conn;
    $output = [];
    exec("ping -n 4 " . escapeshellarg($ip), $output);

    $times = [];
    foreach ($output as $line) {
        if (preg_match('/time[=<]\s*(\d+)\s*ms/i', $line, $m)) {
            $times[] = (int)$m[1];
        }
    }

    if (count($times) > 0) {
        $avg = number_format(array_sum($times) / count($times), 2);
        $st  = 'online';
    } else {
        $avg = 0;
        $st  = 'offline';
    }

    $sql  = "UPDATE add_ip SET latency = :lat, status = :st WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':lat' => $avg,
        ':st'  => $st,
        ':id'  => $id
    ]);
}

function refreshAllIPs() {
    global $conn;
    $response = ['success' => false, 'message' => ''];

    try {
        $rows = $conn->query("SELECT id, ip_address FROM add_ip")
                    ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $r) {
            pingAndUpdate($r['id'], $r['ip_address']);
        }

        $response['success'] = true;
        $response['message'] = "Refreshed " . count($rows) . " IP(s).";
    } catch (PDOException $e) {
        $response['message'] = "DB error: " . $e->getMessage();
    }

    return $response;
}

// --- Routing ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'login':
            $res = loginUser(
                sanitize($_POST['username']),
                $_POST['password']
            );
            if ($res['success']) {
                header("Location: /views/dashboard.php");
                exit;
            } else {
                $_SESSION['error'] = $res['message'];
                header("Location: /login.php");
                exit;
            }

        case 'register':
            $res = registerUser(
                sanitize($_POST['username']),
                sanitize($_POST['email']),
                $_POST['password'],
                $_POST['confirm_password']
            );
            if ($res['success']) {
                $_SESSION['success'] = $res['message'];
                header("Location: /login.php");
                exit;
            } else {
                $_SESSION['error'] = $res['message'];
                header("Location: /register.php");
                exit;
            }

            case 'add_data':
                // Single vs Bulk detection:
                if (is_array($_POST['ip_address'])) {
                    // BULK MODE
                    $ips   = $_POST['ip_address'];
                    $descs = $_POST['description'];
                    $locs  = $_POST['location'];
                    $cats  = $_POST['category'];
            
                    $total       = count($ips);
                    $added       = 0;
                    $skipped     = 0;
            
                    foreach ($ips as $i => $rawIp) {
                        // Sanitize each field individually
                        $ip          = sanitize($rawIp);
                        $description = sanitize($descs[$i]);
                        $location    = sanitize($locs[$i]);
                        $category    = sanitize($cats[$i]);
            
                        $res = addDataRow($ip, $description, $location, $category);
                        if ($res['success']) {
                            $added++;
                        } else {
                            $skipped++;
                            // Optionally, log $res['message'] somewhere
                        }
                    }
            
                    $_SESSION['success'] = "Bulk add complete: $added of $total entries added.";
                    if ($skipped) {
                        $_SESSION['error'] = "$skipped entries skipped (missing/invalid fields).";
                    }
                } else {
                    // SINGLE MODE (original logic)
                    $ip          = sanitize($_POST['ip_address']);
                    $description = sanitize($_POST['description']);
                    $location    = sanitize($_POST['location']);
                    $category    = sanitize($_POST['category']);
            
                    $res = addDataRow($ip, $description, $location, $category);
                    $_SESSION[$res['success'] ? 'success' : 'error'] = $res['message'];
                }
            
                header("Location: ../views/monitoring.php");
                exit;
            
            
        case 'add_location_category':
            $location = !empty($_POST['location']) ? sanitize($_POST['location']) : null;
            $category = !empty($_POST['category']) ? sanitize($_POST['category']) : null;
            
            if (!$location && !$category) {
                $_SESSION['error'] = "Please provide at least a location or category";
                header("Location: /views/monitoring.php");
                exit;
            }
            
            try {
                if ($location) {
                    $stmt = $conn->prepare("INSERT INTO locations (location) VALUES (:location)");
                    $stmt->execute([':location' => $location]);
                }
                
                if ($category) {
                    $stmt = $conn->prepare("INSERT INTO categories (category) VALUES (:category)");
                    $stmt->execute([':category' => $category]);
                }
                
                $_SESSION['success'] = "Added successfully!";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Database error: " . $e->getMessage();
            }
            
            header("Location: /views/monitoring.php");
            exit;

        case 'refresh_all':
            $res = refreshAllIPs();
            $_SESSION[$res['success'] ? 'success' : 'error'] = $res['message'];
            header("Location: /views/monitoring.php");
            exit;

        default:
            header("HTTP/1.1 400 Bad Request");
            echo "Unknown action.";
            exit;
            case 'update_data':
                // Redirect to edit page, o direktang i-handle yung update sa POST
                header("Location: ../views/edit_data.php?id=" . intval($_POST['id']));
                exit;

            case 'delete_data':
                $id = intval($_POST['id']);
                $stmt = $conn->prepare("DELETE FROM add_ip WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $_SESSION['success'] = "Entry #$id deleted successfully.";
                header("Location: /views/monitoring.php");
                exit;

                case 'update_data':
                    // Redirect to edit page, o direktang i-handle yung update sa POST
                    header("Location: ../views/edit_data.php?id=" . intval($_POST['id']));
                    exit;
                
                case 'delete_data':
                    $id = intval($_POST['id']);
                    $stmt = $conn->prepare("DELETE FROM add_ip WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $_SESSION['success'] = "Entry #$id deleted successfully.";
                    header("Location: ../views/monitoring.php");
                    exit;
                }
            }                