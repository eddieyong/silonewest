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

// Get quick stats
$total_items = $mysqli->query("SELECT COUNT(*) as count FROM inventory")->fetch_assoc()['count'];
$total_stock = $mysqli->query("SELECT SUM(balance) as total FROM inventory")->fetch_assoc()['total'];
$low_stock = $mysqli->query("SELECT COUNT(*) as count FROM inventory WHERE balance < 10")->fetch_assoc()['count'];

include 'admin-header.php';
?>

<style>
    .container {
        padding: 20px 30px;
        background: #f8f9fa;
        min-height: calc(100vh - 72px);
    }

    .welcome-message {
        font-size: 2rem;
        color: #333;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .quick-access-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .quick-access-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
    }

    .card-icon {
        font-size: 2rem;
        color: #5c1f00;
        margin-bottom: 10px;
    }

    .card-title {
        font-size: 1.2rem;
        color: #333;
        margin-bottom: 15px;
    }

    .card-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .card-btn {
        padding: 10px;
        background: #0066cc;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        transition: background-color 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .card-btn:hover {
        background: #0052a3;
        color: white;
        text-decoration: none;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .dashboard-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .section-title {
        font-size: 1.2rem;
        color: #333;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .overview-item {
        padding: 15px;
        border-left: 4px solid #5c1f00;
        background: #f8f9fa;
        margin-bottom: 10px;
        cursor: pointer;
        transition: transform 0.3s;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .overview-item:hover {
        transform: translateX(5px);
    }

    .overview-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #333;
    }

    .overview-subtitle {
        color: #666;
        font-size: 0.9rem;
    }

    .notification-item {
        padding: 15px;
        border-left: 4px solid #ffc107;
        background: #f8f9fa;
        margin-bottom: 10px;
        cursor: pointer;
    }

    .notification-title {
        font-weight: 500;
        color: #333;
        margin-bottom: 5px;
    }

    .notification-subtitle {
        color: #666;
        font-size: 0.9rem;
    }
</style>

<div class="container">
    <h1 class="welcome-message">
        Welcome, admin! <span style="font-size: 2rem;">👋</span>
    </h1>

    <div class="quick-access-grid">
        <div class="quick-access-card">
            <div class="card-icon">📦</div>
            <h2 class="card-title">Inventory</h2>
            <div class="card-buttons">
                <a href="inventory.php" class="card-btn">
                    <i class="fas fa-boxes"></i> Manage Inventory
                </a>
                <a href="inventory.php" class="card-btn">
                    <i class="fas fa-eye"></i> View Stock
                </a>
            </div>
        </div>

        <div class="quick-access-card">
            <div class="card-icon">👥</div>
            <h2 class="card-title">Users</h2>
            <div class="card-buttons">
                <a href="add-user.php" class="card-btn">
                    <i class="fas fa-user-plus"></i> Register Users
                </a>
                <a href="manage-users.php" class="card-btn">
                    <i class="fas fa-users"></i> Manage Users
                </a>
            </div>
        </div>

        <div class="quick-access-card">
            <div class="card-icon">🛒</div>
            <h2 class="card-title">Suppliers</h2>
            <div class="card-buttons">
                <a href="add-supplier.php" class="card-btn">
                    <i class="fas fa-plus"></i> Add Supplier
                </a>
                <a href="view-suppliers.php" class="card-btn">
                    <i class="fas fa-list"></i> View Suppliers
                </a>
            </div>
        </div>

        <div class="quick-access-card">
            <div class="card-icon">🚚</div>
            <h2 class="card-title">Vehicles</h2>
            <div class="card-buttons">
                <a href="add-vehicle.php" class="card-btn">
                    <i class="fas fa-truck"></i> Add Vehicle
                </a>
                <a href="view-vehicles.php" class="card-btn">
                    <i class="fas fa-shipping-fast"></i> View Vehicles
                </a>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-section">
            <h2 class="section-title">
                <i class="fas fa-chart-line"></i> System Overview
            </h2>

            <div class="overview-item">
                <div>
                    <div class="overview-title">
                        <i class="fas fa-box"></i> Total Items in Stock
                    </div>
                    <div class="overview-subtitle">Click to view details</div>
                </div>
                <div><?php echo number_format($total_items); ?></div>
            </div>

            <div class="overview-item">
                <div>
                    <div class="overview-title">
                        <i class="fas fa-cubes"></i> Total Stock Balance
                    </div>
                    <div class="overview-subtitle">Click to view details</div>
                </div>
                <div><?php echo number_format($total_stock); ?></div>
            </div>

            <div class="overview-item">
                <div>
                    <div class="overview-title">
                        <i class="fas fa-exclamation-triangle"></i> Low Stock Alerts
                    </div>
                    <div class="overview-subtitle">Click to view details</div>
                </div>
                <div><?php echo number_format($low_stock); ?></div>
            </div>
        </div>

        <div class="dashboard-section">
            <h2 class="section-title">
                <i class="fas fa-bell"></i> Recent Activities
            </h2>

            <div class="notification-item">
                <div class="notification-title">Inventory Updates</div>
                <div class="notification-subtitle">Check recent inventory changes</div>
            </div>

            <div class="notification-item">
                <div class="notification-title">Stock Alerts</div>
                <div class="notification-subtitle">View low stock notifications</div>
            </div>

            <div class="notification-item">
                <div class="notification-title">System Updates</div>
                <div class="notification-subtitle">View latest system changes</div>
            </div>
        </div>
    </div>
</div>

<?php $mysqli->close(); ?>


