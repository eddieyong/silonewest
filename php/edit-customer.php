<?php
session_start();

// Check if user is logged in and has Customer role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Customer') {
    header("Location: mainlogin.html");
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

// Get the logged-in customer's information
$username = $_SESSION['username'];
$sql = "SELECT * FROM customer WHERE username = '$username'";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
    $customer = $result->fetch_assoc();
} else {
    die("Customer not found.");
}

// Handle the form submission for updating details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_contact = trim($_POST['contact']);
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate input
    if (empty($new_username) || empty($new_email)) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } else {
        // Check if username already exists
        $stmt = $mysqli->prepare("SELECT username FROM customer WHERE username = ? AND username != ?");
        $stmt->bind_param("ss", $new_username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "Username already exists. Please choose a different username.";
            $messageType = "error";
        } else {
            // Verify the current password
            if (!password_verify($current_password, $customer['password'])) {
                $message = "Current password is incorrect.";
                $messageType = "error";
            } elseif ($new_password !== $confirm_password) {
                $message = "New password and confirmation do not match.";
                $messageType = "error";
            } else {
                // Update customer details
                $update_sql = "UPDATE customer SET username = ?, email = ?, contact = ? WHERE username = ?";
                $stmt = $mysqli->prepare($update_sql);
                $stmt->bind_param("ssss", $new_username, $new_email, $new_contact, $username);

                if ($stmt->execute()) {
                    // Update password if it is changed
                    if (!empty($new_password)) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $password_update_sql = "UPDATE customer SET password = ? WHERE username = ?";
                        $stmt = $mysqli->prepare($password_update_sql);
                        $stmt->bind_param("ss", $hashed_password, $new_username);
                        $stmt->execute();
                    }
                    
                    $_SESSION['username'] = $new_username;
                    $message = "Profile updated successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error updating profile: " . $mysqli->error;
                    $messageType = "error";
                }
            }
        }
        $stmt->close();
    }
}

include 'user-header.php';
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
        <h1 class="page-title">Update Customer Profile</h1>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="user-form">
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($customer['username']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="tel" id="contact" name="contact" value="<?php echo htmlspecialchars($customer['contact']); ?>">
            </div>

            <!-- Password Reset Section -->
            <div class="form-group">
                <label for="current_password">Current Password *</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <button type="submit" class="btn-submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php $mysqli->close(); ?>
