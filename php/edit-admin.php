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

$username = $_SESSION['username'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $mysqli->real_escape_string($_POST['email']);
    $contact = $mysqli->real_escape_string($_POST['contact']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Get current user data
    $stmt = $mysqli->prepare("SELECT password FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        $error_message = "Current password is incorrect.";
    } else {
        // Start building the update query
        $updates = array();
        $updates[] = "email = '$email'";
        $updates[] = "contact = '$contact'";

        // If new password is provided, update it
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                $error_message = "New passwords do not match.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updates[] = "password = '$hashed_password'";
            }
        }

        if (empty($error_message)) {
            $update_query = "UPDATE admin SET " . implode(", ", $updates) . " WHERE username = '$username'";
            if ($mysqli->query($update_query)) {
                $success_message = "Profile updated successfully!";
            } else {
                $error_message = "Error updating profile: " . $mysqli->error;
            }
        }
    }
}

// Get current user data for form
$stmt = $mysqli->prepare("SELECT email, contact, role FROM admin WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

include 'admin-header.php';
?>

<style>
    .edit-profile-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 2rem;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    .edit-profile-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #5c1f00;
    }

    .edit-profile-header h1 {
        color: #5c1f00;
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #5c1f00;
        font-weight: bold;
    }

    .form-group input {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }

    .password-section {
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #ddd;
    }

    .btn-container {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
    }

    .btn {
        padding: 0.8rem 2rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1rem;
        transition: background-color 0.3s;
    }

    .btn-primary {
        background-color: #5c1f00;
        color: white;
    }

    .btn-primary:hover {
        background-color: #7a2900;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
    }

    .alert {
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 4px;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<div class="edit-profile-container">
    <div class="edit-profile-header">
        <h1>Edit Profile</h1>
        <p>Update your account information</p>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Username</label>
            <input type="text" value="<?php echo htmlspecialchars($username); ?>" disabled>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="form-group">
            <label>Contact</label>
            <input type="text" name="contact" value="<?php echo htmlspecialchars($user['contact']); ?>" required>
        </div>

        <div class="form-group">
            <label>Role</label>
            <input type="text" value="<?php echo htmlspecialchars($user['role']); ?>" disabled>
        </div>

        <div class="password-section">
            <h3>Change Password</h3>
            <p>Leave password fields empty if you don't want to change it</p>

            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password">
            </div>

            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password">
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password">
            </div>
        </div>

        <div class="btn-container">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="admin-profile.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php $mysqli->close(); ?>
