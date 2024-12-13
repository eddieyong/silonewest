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

// Get today's stock movements
$today_stock_in = $mysqli->query("
    SELECT COALESCE(SUM(stock_in), 0) as total 
    FROM inventory 
    WHERE DATE(updated_at) = CURDATE()"
)->fetch_assoc()['total'];

$today_stock_out = $mysqli->query("
    SELECT COALESCE(SUM(stock_out), 0) as total 
    FROM inventory 
    WHERE DATE(updated_at) = CURDATE()"
)->fetch_assoc()['total'];

$low_stock = $mysqli->query("SELECT COUNT(*) as count FROM inventory WHERE balance < 10")->fetch_assoc()['count'];

// Get recent activities
$recent_activities = $mysqli->query("
    SELECT *, CONVERT_TZ(created_at, @@session.time_zone, '+08:00') as created_at_local 
    FROM activities 
    ORDER BY created_at DESC 
    LIMIT 5
");

// Get low stock alerts
$low_stock_items = $mysqli->query("
    SELECT bar_code, inventory_item as item_name, balance 
    FROM inventory 
    WHERE balance < 10 
    ORDER BY balance ASC 
    LIMIT 5
");

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
        justify-content: space-between;
    }

    .view-all {
        font-size: 0.9rem;
        color: #0066cc;
        text-decoration: none;
    }

    .view-all:hover {
        text-decoration: underline;
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
    }

    .notification-item.alert {
        border-left-color: #dc3545;
    }

    .notification-item.activity {
        border-left-color: #28a745;
    }

    .notification-title {
        font-weight: 500;
        color: #333;
        margin-bottom: 5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .notification-time {
        font-size: 0.8rem;
        color: #666;
    }

    .notification-subtitle {
        color: #666;
        font-size: 0.9rem;
    }

    .tab-container {
        margin-bottom: 15px;
    }

    .tab-button {
        padding: 8px 15px;
        border: none;
        background: none;
        color: #666;
        cursor: pointer;
        border-bottom: 2px solid transparent;
    }

    .tab-button.active {
        color: #5c1f00;
        border-bottom-color: #5c1f00;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
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
                <span><i class="fas fa-chart-line"></i> System Overview</span>
            </h2>

            <div class="overview-item">
                <div>
                    <div class="overview-title">
                        <i class="fas fa-box"></i> Total Items
                    </div>
                    <div class="overview-subtitle">Total unique items in inventory</div>
                </div>
                <div><?php echo number_format($total_items); ?></div>
            </div>

            <div class="overview-item">
                <div>
                    <div class="overview-title">
                        <i class="fas fa-arrow-circle-up"></i> Today's Stock In
                    </div>
                    <div class="overview-subtitle">Total items received today</div>
                </div>
                <div><?php echo number_format($today_stock_in); ?></div>
            </div>

            <div class="overview-item">
                <div>
                    <div class="overview-title">
                        <i class="fas fa-arrow-circle-down"></i> Today's Stock Out
                    </div>
                    <div class="overview-subtitle">Total items dispatched today</div>
                </div>
                <div><?php echo number_format($today_stock_out); ?></div>
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
                <span><i class="fas fa-bell"></i> Notifications & Activities</span>
                <a href="history.php" class="view-all">View All</a>
            </h2>

            <div class="tab-container">
                <button class="tab-button active" onclick="switchTab('alerts')">
                    <i class="fas fa-exclamation-circle"></i> Alerts
                </button>
                <button class="tab-button" onclick="switchTab('activities')">
                    <i class="fas fa-history"></i> Recent Activities
                </button>
            </div>

            <div id="alerts-tab" class="tab-content active">
                <?php if ($low_stock_items && $low_stock_items->num_rows > 0): ?>
                    <?php while ($item = $low_stock_items->fetch_assoc()): ?>
                        <div class="notification-item alert">
                            <div class="notification-title">
                                <span>Low Stock Alert: <?php echo htmlspecialchars($item['item_name']); ?></span>
                            </div>
                            <div class="notification-subtitle">
                                Current balance: <?php echo $item['balance']; ?> units
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="notification-item">
                        <div class="notification-title">No Low Stock Alerts</div>
                        <div class="notification-subtitle">All items are above minimum stock levels</div>
                    </div>
                <?php endif; ?>
            </div>

            <div id="activities-tab" class="tab-content">
                <?php if ($recent_activities && $recent_activities->num_rows > 0): ?>
                    <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                        <div class="notification-item activity">
                            <div class="notification-title">
                                <span><?php echo htmlspecialchars($activity['activity_type']); ?></span>
                                <span class="notification-time">
                                    <?php echo date('M d, H:i', strtotime($activity['created_at_local'])); ?>
                                </span>
                            </div>
                            <div class="notification-subtitle">
                                <?php echo htmlspecialchars($activity['description']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="notification-item">
                        <div class="notification-title">No Recent Activities</div>
                        <div class="notification-subtitle">No system activities recorded yet</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Deactivate all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Show selected tab content and activate button
    document.getElementById(tabName + '-tab').classList.add('active');
    event.currentTarget.classList.add('active');
}

// Auto-refresh the page every 60 seconds
setInterval(function() {
    window.location.reload();
}, 60000);
</script>

<?php $mysqli->close(); ?>
