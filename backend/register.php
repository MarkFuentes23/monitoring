<?php
session_start();
require_once '../config/db.php';

// User Actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'register':
            registerUser($conn);
            break;
        case 'add_user':
            addUser($conn);
            break;
        case 'edit_user':
            editUser($conn);
            break;
        default:
            $_SESSION['error'] = "Invalid action";
            header("Location: ../index.php");
            exit;
    }
}

// Register a new user
function registerUser($conn) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    // Default role for self-registration is employee
    $role = 'employee';
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: ../register.php");
        exit;
    }
    
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: ../register.php");
        exit;
    }
    
    if (strlen($password) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters long";
        header("Location: ../register.php");
        exit;
    }
    
    try {
        // Check if username or email already exists
        $checkQuery = "SELECT * FROM users WHERE username = :username OR email = :email";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user['username'] === $username) {
                $_SESSION['error'] = "Username already exists";
            } else {
                $_SESSION['error'] = "Email already exists";
            }
            header("Location: ../register.php");
            exit;
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $insertQuery = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        
        $_SESSION['success'] = "Registration successful! You can now login.";
        header("Location: ../login.php");
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Registration failed: " . $e->getMessage();
        header("Location: ../register.php");
        exit;
    }
}

// Add a new user (admin function)
function addUser($conn) {
    // Check if admin is logged in

    
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'] ?? 'employee'; // Get role, default to employee if not set
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: ../register.php");
        exit;
    }
    
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: ../register.php");
        exit;
    }
    
    if (strlen($password) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters long";
        header("Location: ../register.php");
        exit;
    }
    
    try {
        // Check if username or email already exists
        $checkQuery = "SELECT * FROM users WHERE username = :username OR email = :email";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user['username'] === $username) {
                $_SESSION['error'] = "Username already exists";
            } else {
                $_SESSION['error'] = "Email already exists";
            }
            header("Location: ../register.php");
            exit;
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user with role
        $insertQuery = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        
        $_SESSION['success'] = "User added successfully";
        header("Location: ../register.php");
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to add user: " . $e->getMessage();
        header("Location: ../register.php");
        exit;
    }
}

// Edit existing user
function editUser($conn) {
    // Check if admin is logged in

    
    $user_id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'] ?? 'employee'; // Get role, default to employee if not set
    
    // Validate inputs
    if (empty($user_id) || empty($username) || empty($email)) {
        $_SESSION['error'] = "User ID, username and email are required";
        header("Location: ../register.php");
        exit;
    }
    
    try {
        // Check if username or email already exists for other users
        $checkQuery = "SELECT * FROM users WHERE (username = :username OR email = :email) AND id != :user_id";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user['username'] === $username) {
                $_SESSION['error'] = "Username already exists";
            } else {
                $_SESSION['error'] = "Email already exists";
            }
            header("Location: ../register.php");
            exit;
        }
        
        // Update user
        if (!empty($password)) {
            // Password provided, validate and update
            if ($password !== $confirm_password) {
                $_SESSION['error'] = "Passwords do not match";
                header("Location: ../register.php");
                exit;
            }
            
            if (strlen($password) < 6) {
                $_SESSION['error'] = "Password must be at least 6 characters long";
                header("Location: ../register.php");
                exit;
            }
            
            // Hash new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update with password and role
            $updateQuery = "UPDATE users SET username = :username, email = :email, password = :password, role = :role WHERE id = :user_id";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bindParam(':password', $hashed_password);
        } else {
            // Update without password but with role
            $updateQuery = "UPDATE users SET username = :username, email = :email, role = :role WHERE id = :user_id";
            $stmt = $conn->prepare($updateQuery);
        }
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $_SESSION['success'] = "User updated successfully";
        header("Location: ../register.php");
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to update user: " . $e->getMessage();
        header("Location: ../register.php");
        exit;
    }
}

// Helper functions
