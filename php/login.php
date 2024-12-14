<?php
session_start();
require_once 'functions.php';

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // First check admin table for all admin roles (Admin, Storekeeper, Coordinator, Driver)
    $stmt = $mysqli->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Verify password
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $user['role'];
            
            // Log the successful login with specific role
            logActivity($mysqli, 'user', ucfirst($user['role']) . " logged in successfully");
            
            // All admin roles go to admin.php
            header("Location: admin.php");
            exit();
        }
    }
    
    // If not found in admin, check customer table
    $stmt = $mysqli->prepare("SELECT * FROM customer WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Verify password
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'Customer';
            
            // Log the successful login
            logActivity($mysqli, 'user', "Customer logged in successfully");
            
            header("Location: customer-dashboard.php");
            exit();
        }
    }
    
    // If we get here, login failed
    $_SESSION['login_error'] = "Invalid username or password";
    header("Location: ../admin-login.html");
    exit();
}

$mysqli->close();
?> 