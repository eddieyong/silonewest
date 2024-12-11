<?php
session_start();

// Check if user is logged in and has Customer role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Customer') {
    header("Location: ../mainlogin.html");
    exit();
}

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get the logged-in customer's information
$username = $_SESSION['username'];
$sql = "SELECT * FROM customer WHERE username = '$username'";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
    $customer = $result->fetch_assoc();
} else {
    die("Customer not found.");
}

include 'user-header.php';
?>

<div class="container">
    <h1 class="welcome-message">
      User Profile
    </h1>

    <div class="profile-info">
        <p><strong>Username:</strong> <?php echo htmlspecialchars($customer['username']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
        <p><strong>Contact:</strong> <?php echo htmlspecialchars($customer['contact']); ?></p>
        <p><strong>Role:</strong> <?php echo htmlspecialchars($customer['role']); ?></p>
        <p><strong>Account Created At:</strong> <?php echo htmlspecialchars($customer['created_at']); ?></p>
    </div>

    <a href="edit-customer.php" class="btn btn-primary">Update Profile</a>
</div>

<?php $mysqli->close(); ?>
