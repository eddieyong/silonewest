<?php
session_start();
require_once 'permissions.php';

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

// Get quick stats
$total_items = $mysqli->query("SELECT COUNT(*) as count FROM inventory")->fetch_assoc()['count'];

// Get today's stock movements
$today_stock_in = $mysqli->query("
    SELECT COALESCE(SUM(stock_in), 0) as total 
    FROM inventory 
    WHERE DATE(created_at) = CURDATE() 
    OR DATE(updated_at) = CURDATE()"
)->fetch_assoc()['total'];

$today_stock_out = $mysqli->query("
    SELECT COALESCE(SUM(stock_out), 0) as total 
    FROM inventory 
    WHERE DATE(created_at) = CURDATE() 
    OR DATE(updated_at) = CURDATE()"
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

// Get driver's pending deliveries if user is a driver
$driver_deliveries = null;
if ($_SESSION['role'] === 'Driver') {
    $driver_name = $_SESSION['username'];
    $driver_deliveries = $mysqli->query("
        SELECT do.*, po.supplier_name 
        FROM delivery_orders do 
        LEFT JOIN purchase_orders po ON do.po_number = po.po_number 
        WHERE do.driver_name = '$driver_name' 
        AND do.status = 'Pending' 
        ORDER BY do.delivery_date ASC
    ");
}

// Get driver's delivery statistics if user is a driver
$driver_stats = null;
if ($_SESSION['role'] === 'Driver') {
    $driver_name = $_SESSION['username'];
    $pending_deliveries = $mysqli->query("
        SELECT COUNT(*) as count 
        FROM delivery_orders 
        WHERE driver_name = '$driver_name' 
        AND status = 'Pending'"
    )->fetch_assoc()['count'];

    $completed_deliveries = $mysqli->query("
        SELECT COUNT(*) as count 
        FROM delivery_orders 
        WHERE driver_name = '$driver_name' 
        AND status = 'Completed'"
    )->fetch_assoc()['count'];

    $cancelled_deliveries = $mysqli->query("
        SELECT COUNT(*) as count 
        FROM delivery_orders 
        WHERE driver_name = '$driver_name' 
        AND status = 'Cancelled'"
    )->fetch_assoc()['count'];
}
?>

<?php
include 'admin-header.php';
?>

<div class="container">
    <h1 class="welcome-message">
        Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>! <span style="font-size: 2rem;">👋</span>
    </h1>

    <div class="quick-access-grid">
        <?php if ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Storekeeper'): ?>
            <div class="quick-access-card">
                <div class="card-icon">📦</div>
                <h2 class="card-title">Inventory</h2>
                <div class="card-buttons">
                    <a href="inventory.php" class="card-btn">
                        <i class="fas fa-boxes"></i> Manage Inventory
                    </a>
                    <?php if ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Storekeeper'): ?>
                        <a href="view-stock.php" class="card-btn">
                            <i class="fas fa-eye"></i> View Stock
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'Coordinator'): ?>
            <div class="quick-access-card">
                <div class="card-icon">📦</div>
                <h2 class="card-title">Inventory</h2>
                <div class="card-buttons">
                    <a href="inventory.php" class="card-btn">
                        <i class="fas fa-boxes"></i> View Inventory
                    </a>
                </div>
            </div>

            <div class="quick-access-card">
                <div class="card-icon">🚚</div>
                <h2 class="card-title">Deliveries</h2>
                <div class="card-buttons">
                    <a href="purchase-orders.php" class="card-btn">
                        <i class="fas fa-file-invoice"></i> Purchase Orders
                    </a>
                    <a href="delivery-orders.php" class="card-btn">
                        <i class="fas fa-shipping-fast"></i> Delivery Orders
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'Admin'): ?>
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
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'Storekeeper'): ?>
            <div class="quick-access-card">
                <div class="card-icon">📋</div>
                <h2 class="card-title">Orders</h2>
                <div class="card-buttons">
                    <a href="purchase-orders.php" class="card-btn">
                        <i class="fas fa-shopping-cart"></i> Purchase Orders
                    </a>
                    <a href="delivery-orders.php" class="card-btn">
                        <i class="fas fa-truck"></i> Delivery Orders
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'Driver'): ?>
            <div class="quick-access-card">
                <div class="card-icon">🚚</div>
                <h2 class="card-title">My Deliveries</h2>
                <div class="card-buttons">
                    <a href="#" class="card-btn" onclick="showMyDeliveries()">
                        <i class="fas fa-truck"></i> View My Deliveries
                    </a>
                </div>
            </div>
        <?php endif; ?>
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

        <?php if ($_SESSION['role'] === 'Driver'): ?>
        <div class="dashboard-section">
            <h2 class="section-title">
                <span><i class="fas fa-truck"></i> My Delivery Overview</span>
            </h2>

            <div class="overview-item">
                <div>
                    <div class="overview-title">
                        <i class="fas fa-clock"></i> Pending Deliveries
                    </div>
                    <div class="overview-subtitle">Deliveries waiting to be completed</div>
                </div>
                <div class="overview-count pending"><?php echo number_format($pending_deliveries); ?></div>
            </div>

            <div class="overview-item">
                <div>
                    <div class="overview-title">
                        <i class="fas fa-check-circle"></i> Completed Deliveries
                    </div>
                    <div class="overview-subtitle">Successfully delivered orders</div>
                </div>
                <div class="overview-count completed"><?php echo number_format($completed_deliveries); ?></div>
            </div>

            <div class="overview-item">
                <div>
                    <div class="overview-title">
                        <i class="fas fa-times-circle"></i> Cancelled Deliveries
                    </div>
                    <div class="overview-subtitle">Orders that were cancelled</div>
                </div>
                <div class="overview-count cancelled"><?php echo number_format($cancelled_deliveries); ?></div>
            </div>
        </div>
        <?php elseif ($_SESSION['role'] !== 'Driver'): ?>
        <div class="dashboard-section">
            <h2 class="section-title">
                <span><i class="fas fa-bell"></i> Notifications & Activities</span>
                <?php if ($_SESSION['role'] !== 'Coordinator'): ?>
                    <a href="history.php" class="view-all">View All</a>
                <?php endif; ?>
            </h2>

            <?php if ($_SESSION['role'] === 'Coordinator'): ?>
                <?php
                // Get recent delivery activities
                $recent_activities = $mysqli->query("
                    SELECT *, CONVERT_TZ(created_at, @@session.time_zone, '+08:00') as created_at_local 
                    FROM activities 
                    WHERE activity_type IN ('purchase_order', 'delivery_order')
                    ORDER BY created_at DESC 
                    LIMIT 10
                ");
                ?>

                <div class="activities-list">
                    <?php if ($recent_activities && $recent_activities->num_rows > 0): ?>
                        <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                            <div class="notification-item activity">
                                <div class="notification-title">
                                    <span><?php echo ucfirst(str_replace('_', ' ', $activity['activity_type'])); ?></span>
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
                            <div class="notification-subtitle">No delivery activities recorded yet</div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
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
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Driver Deliveries Modal -->
<div id="driverDeliveriesModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h2 class="modal-title">My Assigned Deliveries</h2>
            <button type="button" onclick="closeDeliveriesModal()" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <?php if ($driver_deliveries && $driver_deliveries->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>DO Number</th>
                            <th>Delivery Date</th>
                            <th>Recipient</th>
                            <th>Address</th>
                            <th>Contact Person</th>
                            <th>Contact Number</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($delivery = $driver_deliveries->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($delivery['do_number']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($delivery['delivery_date'])); ?></td>
                                <td><?php echo htmlspecialchars($delivery['recipient_company']); ?></td>
                                <td><?php echo htmlspecialchars($delivery['delivery_address']); ?></td>
                                <td><?php echo htmlspecialchars($delivery['contact_person']); ?></td>
                                <td><?php echo htmlspecialchars($delivery['contact_number']); ?></td>
                                <td>
                                    <a href="view-do.php?do=<?php echo $delivery['do_number']; ?>" class="btn-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-deliveries">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; margin-bottom: 20px;"></i>
                    <p>No pending deliveries assigned to you at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

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
        background: #5c1f00;
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
        background: #7a2900;
        color: white;
        text-decoration: none;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .dashboard-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        height: fit-content;
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

    /* Additional styles for Coordinator dashboard */
    .deliveries-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .delivery-item {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: transform 0.3s;
    }

    .delivery-item:hover {
        transform: translateX(5px);
    }

    .delivery-icon {
        width: 40px;
        height: 40px;
        background: #5c1f00;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .delivery-content {
        flex-grow: 1;
    }

    .delivery-title {
        font-weight: 500;
        color: #333;
        margin-bottom: 5px;
    }

    .delivery-meta {
        font-size: 0.9rem;
        color: #666;
    }

    .view-btn {
        padding: 8px;
        background: #5c1f00;
        color: white;
        border-radius: 5px;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.3s;
    }

    .view-btn:hover {
        background: #7a2900;
        color: white;
    }

    .no-deliveries {
        text-align: center;
        padding: 20px;
        color: #666;
    }

    .activities-list {
        max-height: 500px;
        overflow-y: auto;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .modal-content {
        background: white;
        margin: 50px auto;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        padding: 20px;
        background: #5c1f00;
        color: white;
        border-radius: 10px 10px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .close-btn {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
    }

    .modal-body {
        padding: 20px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .table th,
    .table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .table th {
        background: #f8f9fa;
        font-weight: 600;
    }

    .btn-view {
        padding: 6px 12px;
        background: #e3f2fd;
        color: #1976d2;
        border-radius: 4px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.9rem;
    }

    .btn-view:hover {
        background: #c8e6ff;
        color: #0056b3;
    }

    .no-deliveries {
        text-align: center;
        padding: 40px 20px;
        color: #666;
    }

    .overview-count {
        font-size: 1.5rem;
        font-weight: 600;
    }

    .overview-count.pending {
        color: #ffc107;
    }

    .overview-count.completed {
        color: #28a745;
    }

    .overview-count.cancelled {
        color: #dc3545;
    }
</style>

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

function showMyDeliveries() {
    document.getElementById('driverDeliveriesModal').style.display = 'block';
}

function closeDeliveriesModal() {
    document.getElementById('driverDeliveriesModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('driverDeliveriesModal');
    if (event.target == modal) {
        closeDeliveriesModal();
    }
}
</script>

<?php $mysqli->close(); ?>
