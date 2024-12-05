<?php
// Create new MySQL interface object
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // Retrieve and sanitize form data
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validate that fields are not empty
    if (empty($username) || empty($password)) {
        die("<script>alert('Username and password are required.'); window.location.href = '../customer-login.html';</script>");
    }

    // Prepare and execute the query to prevent SQL injection
    $stmt = $mysqli->prepare("SELECT username FROM customer WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a user was found
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        // Set a session instead of a cookie for security
        session_start();
        $_SESSION['username'] = $row['username'];

        echo "<script>alert('Welcome " . htmlspecialchars($row['username']) . "!'); window.location.href = 'customer-dashboard.php';</script>";
    } else {
        echo "<script>alert('Invalid username or password.'); window.location.href = '../customer-login.html';</script>";
    }

    // Close the statement and connection
    $stmt->close();
    $mysqli->close();
}
?>

