<?php
session_start();
require_once 'functions.php';

// Check if user is logged in and has Admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin-login.html");
    exit();
}

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $contact = trim($_POST['contact']);
    $role = trim($_POST['role']);

    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } else {
        // Determine which table to use based on role
        $table = ($role === 'Customer') ? 'customer' : 'admin';
        
        // Check if username already exists in both tables
        $stmt = $mysqli->prepare("SELECT username FROM admin WHERE username = ? UNION SELECT username FROM customer WHERE username = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "Username already exists. Please choose a different username.";
            $messageType = "error";
        } else {
            // Insert new user into appropriate table with plain text password
            $stmt = $mysqli->prepare("INSERT INTO $table (username, email, password, contact, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            
            // Hash the password before storing
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt->bind_param("sssss", $username, $email, $hashed_password, $contact, $role);
            
            if ($stmt->execute()) {
                // Log the activity with role-specific details
                $description = "Added new {$role}: {$username} (Email: {$email}, Contact: {$contact})";
                if ($role === 'Driver') {
                    $description .= " - Will have access to DO, PO, and vehicle schedules";
                } elseif ($role === 'Storekeeper') {
                    $description .= " - Will manage inventory and stock operations";
                } elseif ($role === 'Coordinator') {
                    $description .= " - Will have view access to inventory and deliveries";
                }
                
                $activity_type = 'user';
                $admin_username = $_SESSION['username'];
                $timestamp = date('Y-m-d H:i:s');
                $full_description = "By $admin_username: $description";
                
                $log_stmt = $mysqli->prepare("INSERT INTO activities (activity_type, description, created_by, created_at) VALUES (?, ?, ?, ?)");
                $log_stmt->bind_param("ssss", $activity_type, $full_description, $admin_username, $timestamp);
                
                if ($log_stmt->execute()) {
                    $_SESSION['success_msg'] = "User added successfully!";
                } else {
                    $_SESSION['success_msg'] = "User added but failed to log activity: " . $mysqli->error;
                }
                $log_stmt->close();
                
                header("Location: manage-users.php");
                exit();
            } else {
                $message = "Error adding user: " . $mysqli->error;
                $messageType = "error";
            }
        }
        $stmt->close();
    }
}

// Include the header
include 'admin-header.php';
?>

<style>
    .container {
        padding: 20px 30px;
        background: #f8f9fa;
        min-height: calc(100vh - 72px);
    }

    .page-header {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }

    .page-title {
        margin: 0;
        font-size: 1.5rem;
        color: #333;
    }

    .user-form {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        max-width: 600px;
        margin: 0 auto;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-weight: 500;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    .form-group input:focus,
    .form-group select:focus {
        border-color: #5c1f00;
        outline: none;
    }

    .btn-submit {
        background: #5c1f00;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        transition: background-color 0.3s;
    }

    .btn-submit:hover {
        background: #7a2900;
    }

    .message {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }

    .message.success {
        background: #e8f5e9;
        color: #2e7d32;
        border: 1px solid #c8e6c9;
    }

    .message.error {
        background: #ffebee;
        color: #c62828;
        border: 1px solid #ffcdd2;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Add New User</h1>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="user-form">
        <form method="POST" action="add-user.php">
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="tel" id="contact" name="contact">
            </div>

            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" required>
                    <option value="Admin">Admin</option>
                    <option value="Storekeeper">Storekeeper</option>
                    <option value="Coordinator">Coordinator</option>
                    <option value="Driver">Driver</option>
                    <option value="Customer">Customer</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <button type="submit" class="btn-submit">Add User</button>
            </div>
        </form>
    </div>
</div>

<?php $mysqli->close(); ?> 