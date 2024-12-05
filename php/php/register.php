<?php
// Create a new MySQL interface object
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    exit();
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // Retrieve form data without additional sanitization
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $role = $_POST['role']; // Role determines table selection

    // Perform basic validation
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email) || empty($contact) || empty($role)) {
        echo "<script>alert('All fields are required.'); window.location.href = 'register.html';</script>";
    } elseif ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match.'); window.location.href = 'register.html';</script>";
    } else {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Determine which table to insert data into based on role
        if ($role === 'Admin') {
            $table = 'admin';
        } elseif ($role === 'Customer') {
            $table = 'customer';
        } else {
            echo "<script>alert('Invalid role selected.'); window.location.href = 'register.html';</script>";
            exit();
        }

        // Create the SQL query using the determined table
        $query = "INSERT INTO $table (username, password, email, contact, role) 
                  VALUES ('$username', '$hashed_password', '$email', '$contact', '$role')";

        // Execute the query
        if ($mysqli->query($query)) {
            echo "<script>alert('User registered successfully.'); window.location.href = 'admin.php';</script>";
        } else {
            echo "<script>alert('Error: " . $mysqli->error . "'); window.location.href = 'register.html';</script>";
        }
    }
}

// Close the database connection
$mysqli->close();
?>
