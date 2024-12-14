<?php
session_start();

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['Admin', 'Storekeeper', 'Coordinator', 'Driver'])) {
    header("Location: ../admin-login.html");
    exit();
}

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get the logged-in admin's information
$username = $_SESSION['username'];
$sql = "SELECT * FROM admin WHERE username = '$username'";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
} else {
    die("Admin not found.");
}

include 'admin-header.php';
?>

<style>
    .profile-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 2rem;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    .profile-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #5c1f00;
    }

    .profile-header h1 {
        color: #5c1f00;
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .profile-info {
        background-color: #f9f9f9;
        padding: 2rem;
        border-radius: 6px;
        margin-bottom: 2rem;
    }

    .profile-info .info-item {
        display: flex;
        margin-bottom: 1rem;
        padding: 0.8rem;
        background-color: #fff;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .profile-info .info-label {
        font-weight: bold;
        width: 180px;
        color: #5c1f00;
    }

    .profile-info .info-value {
        flex: 1;
        color: #333;
    }

    .btn-update {
        background-color: #5c1f00;
        color: white;
        padding: 0.8rem 2rem;
        border: none;
        border-radius: 4px;
        text-decoration: none;
        display: inline-block;
        transition: background-color 0.3s;
    }

    .btn-update:hover {
        background-color: #7a2900;
        color: white;
    }

    .action-buttons {
        text-align: center;
    }
</style>

<div class="profile-container">
    <div class="profile-header">
        <h1>Admin Profile</h1>
        <p>Manage your account information</p>
    </div>

    <div class="profile-info">
        <div class="info-item">
            <div class="info-label">Username:</div>
            <div class="info-value"><?php echo htmlspecialchars($admin['username']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Email:</div>
            <div class="info-value"><?php echo htmlspecialchars($admin['email']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Contact:</div>
            <div class="info-value"><?php echo htmlspecialchars($admin['contact']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Role:</div>
            <div class="info-value"><?php echo htmlspecialchars($admin['role']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Account Created:</div>
            <div class="info-value"><?php echo htmlspecialchars($admin['created_at']); ?></div>
        </div>
    </div>

    <div class="action-buttons">
        <a href="edit-admin.php" class="btn-update">Update Profile</a>
    </div>
</div>

<?php $mysqli->close(); ?>
