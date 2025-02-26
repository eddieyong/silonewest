<?php
// Create a new MySQL interface object
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    exit();
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate input
    if (empty($username) || empty($password)) {
        echo "<script>alert('Username and password are required.'); window.location.href = '../admin-login.html';</script>";
        exit();
    }

    // Prepare SQL query to prevent SQL injection
    $stmt = $mysqli->prepare("SELECT password, role FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        // Verify the password
        if (password_verify($password, $row['password'])) {
            // Check if the user has a valid role
            $valid_roles = ['Admin', 'Storekeeper', 'Coordinator', 'Driver'];
            if (in_array($row['role'], $valid_roles)) {
                // Start session
                session_start();
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $row['role'];

                // Redirect to the admin panel
                header("Location: admin.php");
                exit();
            } else {
                echo "<script>alert('Invalid role.'); window.location.href = '../admin-login.html';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Invalid username or password.'); window.location.href = '../admin-login.html';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Invalid username or password.'); window.location.href = '../admin-login.html';</script>";
        exit();
    }
}

// Close the connection
$mysqli->close();
?>
