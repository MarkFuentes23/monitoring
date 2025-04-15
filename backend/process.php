<?php
// start session & import DB + helpers
require_once 'db.php';

// --- Authentication Functions ---

function loginUser($username, $password) {
    global $conn;
    $response = ['success' => false, 'message' => ''];

    if (empty($username) || empty($password)) {
        $response['message'] = "Please enter both username and password";
        return $response;
    }

    try {
        $sql = "SELECT id, username, password FROM users WHERE username = :username";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':username' => $username]);

        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch();
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
        $sql = "SELECT id FROM users WHERE username = :username OR email = :email";
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
        $sql = "INSERT INTO users (username, email, password)
                VALUES (:username, :email, :password)";
        $stmt = $conn->prepare($sql);
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
        // Insert basic row
        $sql = "INSERT INTO add_ip (date, ip_address, description, location)
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
    // Ping 4 times
    $output = [];
    exec("ping -n 4 " . escapeshellarg($ip), $output);

    // Parse times
    $times = [];
    foreach ($output as $line) {
        if (preg_match('/time[=<]\s*(\d+)\s*ms/i', $line, $m)) {
            $times[] = (int)$m[1];
        }
    }

    // Compute average and status
    if (count($times) > 0) {
        $avg = array_sum($times) / count($times);
        // Format sa 2 decimal places
        $avg = number_format($avg, 2);
        $st  = 'online';
    } else {
        $avg = 0;
        $st  = 'offline';
    }

    // Update row
    $upd = "UPDATE add_ip SET latency = :lat, status = :st WHERE id = :id";
    $ustmt = $conn->prepare($upd);
    $ustmt->execute([
      ':lat' => $avg,
      ':st'  => $st,
      ':id'  => $id
    ]);
}

function refreshAllIPs() {
    global $conn;
    $response = ['success' => false, 'message' => ''];

    try {
        $rows = $conn->query("SELECT id, ip_address FROM add_ip")->fetchAll(PDO::FETCH_ASSOC);

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
            $res = loginUser(sanitize($_POST['username']), $_POST['password']);
            if ($res['success']) {
                header("Location: ../views/dashboard.php");
            } else {
                $_SESSION['error'] = $res['message'];
                header("Location: ../views/login.php");
            }
            exit;

        case 'register':
            $res = registerUser(
              sanitize($_POST['username']),
              sanitize($_POST['email']),
              $_POST['password'],
              $_POST['confirm_password']
            );
            if ($res['success']) {
                $_SESSION['success'] = $res['message'];
                header("Location: ../views/login.php");
            } else {
                $_SESSION['error'] = $res['message'];
                header("Location: ../views/register.php");
            }
            exit;

        case 'add_data':
            $res = addDataRow(
              $_POST['date'],
              sanitize($_POST['ip_address']),
              sanitize($_POST['description']),
              sanitize($_POST['location'])
            );
            $_SESSION[$res['success'] ? 'success' : 'error'] = $res['message'];
            header("Location: ../views/monitoring.php");
            exit;

        case 'refresh_all':
            $res = refreshAllIPs();
            $_SESSION[$res['success'] ? 'success' : 'error'] = $res['message'];
            header("Location: ../views/monitoring.php");
            exit;

        default:
            header("HTTP/1.1 400 Bad Request");
            echo "Unknown action.";
            exit;
    }
}
