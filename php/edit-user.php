<?php
// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$message = '';
$user = null;
$errors = [];

// Get username and table name from URL
if (!isset($_GET['username']) || !isset($_GET['table'])) {
    header("Location: manage-users.php");
    exit();
}

$username = $_GET['username'];
$table = $_GET['table'];

// Validate table name to prevent SQL injection
if ($table !== 'admin' && $table !== 'customer') {
    header("Location: manage-users.php");
    exit();
}

// Fetch user data
$stmt = $mysqli->prepare("SELECT username, email, contact, role FROM $table WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage-users.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']); // Optional
    $contact = trim($_POST['contact']);
    $role = trim($_POST['role']);

    // Validation
    if (empty($new_username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Check if new username exists (excluding current user)
    if ($new_username !== $username) {
        $stmt = $mysqli->prepare("SELECT username FROM $table WHERE username = ? AND username != ?");
        $stmt->bind_param("ss", $new_username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Username already exists";
        }
        $stmt->close();
    }

    // If no errors, proceed with update
    if (empty($errors)) {
        if (!empty($password)) {
            // Update with new plain text password
            $stmt = $mysqli->prepare("UPDATE $table SET username = ?, email = ?, password = ?, contact = ?, role = ? WHERE username = ?");
            $stmt->bind_param("ssssss", $new_username, $email, $password, $contact, $role, $username);
        } else {
            // Update without changing password
            $stmt = $mysqli->prepare("UPDATE $table SET username = ?, email = ?, contact = ?, role = ? WHERE username = ?");
            $stmt->bind_param("sssss", $new_username, $email, $contact, $role, $username);
        }

        if ($stmt->execute()) {
            $message = "User updated successfully!";
            // Redirect after successful update
            header("Location: manage-users.php");
            exit();
        } else {
            $errors[] = "Error updating user: " . $mysqli->error;
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
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .page-title {
        margin: 0;
        font-size: 1.5rem;
        color: #333;
    }

    .form-container {
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

    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    .form-control:focus {
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

    .btn-cancel {
        background: #f8f9fa;
        color: #333;
        padding: 12px 24px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        text-decoration: none;
        margin-right: 10px;
        transition: background-color 0.3s;
    }

    .btn-cancel:hover {
        background: #e9ecef;
    }

    .error-message {
        color: #dc3545;
        background: #ffebee;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .success-message {
        color: #28a745;
        background: #e8f5e9;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .password-note {
        color: #666;
        font-size: 0.9rem;
        margin-top: 5px;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit User</h1>
    </div>

    <div class="form-container">
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control">
                <p class="password-note">Leave blank to keep current password</p>
            </div>

            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="tel" id="contact" name="contact" class="form-control" 
                       value="<?php echo htmlspecialchars($user['contact']); ?>">
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="Customer" <?php echo $user['role'] === 'Customer' ? 'selected' : ''; ?>>Customer</option>
                    <option value="Admin" <?php echo $user['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <a href="manage-users.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Update User</button>
            </div>
        </form>
    </div>
</div>

<?php $mysqli->close(); ?> 