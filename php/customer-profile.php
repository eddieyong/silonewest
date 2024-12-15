<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
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
$sql = "SELECT * FROM customer WHERE username = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $customer = $result->fetch_assoc();
    // Set the role in session if not already set
    $_SESSION['role'] = 'Customer';
} else {
    die("Customer not found.");
}

include 'user-header.php';
?>

<style>
    .profile-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 2rem;
    }

    .profile-header {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        text-align: center;
        position: relative;
        width: 100%;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: #5c1f00;
        margin: 0 auto 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .profile-avatar i {
        font-size: 3rem;
        color: white;
    }

    .profile-name {
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .profile-role {
        color: #666;
        font-size: 1.1rem;
    }

    .profile-sections {
        display: flex;
        flex-direction: column;
        gap: 2rem;
        margin-top: 2rem;
        width: 100%;
    }

    .profile-section {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        width: 100%;
    }

    .section-title {
        color: #5c1f00;
        font-size: 1.2rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #5c1f00;
    }

    .info-item {
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .info-label {
        color: #666;
        font-size: 1rem;
        font-weight: 500;
        flex: 1;
    }

    .info-value {
        color: #333;
        font-weight: 500;
        flex: 2;
        text-align: right;
    }

    .profile-actions {
        text-align: center;
        margin-top: 2rem;
    }

    .btn-update-profile {
        background: #5c1f00;
        color: white;
        padding: 0.8rem 2rem;
        border-radius: 5px;
        text-decoration: none;
        display: inline-block;
        transition: background-color 0.3s;
    }

    .btn-update-profile:hover {
        background: #7a2900;
        color: white;
        text-decoration: none;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 2rem;
    }

    .stat-card {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .stat-value {
        font-size: 1.5rem;
        color: #5c1f00;
        font-weight: 600;
    }

    .stat-label {
        color: #666;
        font-size: 0.9rem;
        margin-top: 0.3rem;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .info-item {
            flex-direction: column;
            text-align: center;
        }

        .info-value {
            text-align: center;
            margin-top: 0.5rem;
        }
    }
</style>

<div class="profile-container">
    <div class="profile-header">
        <div class="profile-avatar">
            <i class="fas fa-user"></i>
        </div>
        <h1 class="profile-name"><?php echo htmlspecialchars($customer['username']); ?></h1>
        <div class="profile-role">
            <i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($customer['role']); ?>
        </div>
    </div>

    <div class="profile-sections">
        <div class="profile-section">
            <h2 class="section-title">
                <i class="fas fa-info-circle"></i> Basic Information
            </h2>
            <div class="info-item">
                <div class="info-label">Email Address</div>
                <div class="info-value">
                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($customer['email']); ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Contact Number</div>
                <div class="info-value">
                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['contact']); ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Account Created</div>
                <div class="info-value">
                    <i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($customer['created_at']); ?>
                </div>
            </div>
        </div>

        <div class="profile-section">
            <h2 class="section-title">
                <i class="fas fa-chart-bar"></i> Account Statistics
            </h2>
            <?php
            // Get order statistics
            $stats_sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_orders
                FROM orders 
                WHERE customer_username = ?";
            $stmt = $mysqli->prepare($stats_sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stats_result = $stmt->get_result();
            $stats = $stats_result->fetch_assoc();
            ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['completed_orders'] ?? 0; ?></div>
                    <div class="stat-label">Completed Orders</div>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-actions">
        <a href="edit-customer.php" class="btn-update-profile">
            <i class="fas fa-edit"></i> Update Profile
        </a>
    </div>
</div>

<?php $mysqli->close(); ?>

