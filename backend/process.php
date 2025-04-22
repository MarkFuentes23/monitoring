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
        $sql  = "SELECT id, username, password FROM users WHERE username = :username";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':username' => $username]);

        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
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

function registerUser($username, $email, $password, $confirm_password) {
    global $conn;
    $response = ['success' => false, 'message' => ''];

    if (empty($username) || empty($email) || empty($password)) {
        $response['message'] = "Please fill all required fields";
        return $response;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Please enter a valid email address";
        return $response;
    }
    if (strlen($password) < 6) {
        $response['message'] = "Password must be at least 6 characters long";
        return $response;
    }
    if ($password !== $confirm_password) {
        $response['message'] = "Passwords do not match";
        return $response;
    }

    try {
        $sql  = "SELECT id FROM users WHERE username = :username OR email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':username' => $username,
            ':email'    => $email
        ]);
        if ($stmt->rowCount() > 0) {
            $response['message'] = "Username or email already exists";
            return $response;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $sql    = "INSERT INTO users (username, email, password)
                   VALUES (:username, :email, :password)";
        $stmt   = $conn->prepare($sql);
        $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':password' => $hashed
        ]);

        $response['success'] = true;
        $response['message'] = "Registration successful! You can now login.";
    } catch (PDOException $e) {
        $response['message'] = "Database error: " . $e->getMessage();
    }

    return $response;
}

// --- Data Table Functions ---

function addDataRow($date, $ip, $description, $location) {
    global $conn;
    $response = ['success' => false, 'message' => ''];

    if (!$date || !$ip || !$description || !$location) {
        $response['message'] = "All fields are required.";
        return $response;
    }

    try {
        $sql  = "INSERT INTO add_ip (date, ip_address, description, location)
                 VALUES (:date, :ip, :desc, :loc)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':date' => $date,
            ':ip'   => $ip,
            ':desc' => $description,
            ':loc'  => $location,
        ]);
        $newId = $conn->lastInsertId();

        // Ping and update that row
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
            $res = addDataRow(
                $_POST['date'],
                sanitize($_POST['ip_address']),
                sanitize($_POST['description']),
                sanitize($_POST['location'])
            );
            $_SESSION[$res['success'] ? 'success' : 'error'] = $res['message'];
            header("Location: /views/monitoring.php");
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
    }
}