<?php
session_start();

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

<div class="container">
    <h1 class="welcome-message">
        Admin Profile
    </h1>

    <div class="profile-info">
        <p><strong>Username:</strong> <?php echo htmlspecialchars($admin['username']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($admin['email']); ?></p>
        <p><strong>Contact:</strong> <?php echo htmlspecialchars($admin['contact']); ?></p>
        <p><strong>Role:</strong> <?php echo htmlspecialchars($admin['role']); ?></p>
        <p><strong>Account Created At:</strong> <?php echo htmlspecialchars($admin['created_at']); ?></p>
    </div>

    <a href="edit-admin.php" class="btn btn-primary">Update Profile</a>
</div>

<?php $mysqli->close(); ?>
