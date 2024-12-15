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

    // First, get the user's data by username only
    $stmt = $mysqli->prepare("SELECT * FROM customer WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $stored_password = $row['password'];

        // Check if the password is hashed (hashed passwords are typically longer than 40 characters)
        if (strlen($stored_password) > 40) {
            // Password is hashed, verify using password_verify
            $password_correct = password_verify($password, $stored_password);
        } else {
            // Password is in plain text, do direct comparison
            $password_correct = ($password === $stored_password);
            
            // Optionally hash the password for future use
            if ($password_correct) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $mysqli->prepare("UPDATE customer SET password = ? WHERE username = ?");
                $update_stmt->bind_param("ss", $hashed_password, $username);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }

        if ($password_correct) {
            // Password is correct (either hashed or plain text)
            session_start();
            $_SESSION['username'] = $row['username'];
            $_SESSION['customer_id'] = $row['customer_id'];

            echo "<script>alert('Welcome " . htmlspecialchars($row['username']) . "!'); window.location.href = 'customer-dashboard.php';</script>";
        } else {
            echo "<script>alert('Invalid username or password.'); window.location.href = '../customer-login.html';</script>";
        }
    } else {
        echo "<script>alert('Invalid username or password.'); window.location.href = '../customer-login.html';</script>";
    }

    // Close the statement and connection
    $stmt->close();
    $mysqli->close();
}
?>

