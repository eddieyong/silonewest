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

// Get category from URL parameter, default to 'all'
$category = isset($_GET['category']) ? strtolower($_GET['category']) : 'all';

// Build query based on role and category
$query = "SELECT a.*, DATE_FORMAT(a.created_at, '%b %d, %Y %H:%i:%S') as formatted_date, 
          COALESCE(a.created_by, 'System') as created_by 
          FROM activities a 
          WHERE 1";

// First apply role-specific restrictions
if ($_SESSION['role'] === 'Coordinator') {
    $query .= " AND a.activity_type IN ('purchase_order', 'delivery_order', 'order')";
} elseif ($_SESSION['role'] === 'Driver') {
    $query .= " AND a.activity_type IN ('delivery_order')";
} elseif ($_SESSION['role'] === 'Storekeeper') {
    $query .= " AND a.activity_type IN ('stock_in', 'stock_out', 'inventory', 'purchase_order', 'order')";
}

// Then apply category filter if not 'all'
if ($category !== 'all') {
    switch($category) {
        case 'stock':
            $query .= " AND a.activity_type IN ('stock_in', 'stock_out')";
            break;
        case 'inventory':
            $query .= " AND a.activity_type = 'inventory'";
            break;
        case 'vehicles':
            $query .= " AND a.activity_type = 'vehicles'";
            break;
        case 'user':
            $query .= " AND a.activity_type = 'user'";
            break;
        case 'supplier':
            $query .= " AND a.activity_type = 'supplier'";
            break;
        case 'purchase_order':
            $query .= " AND a.activity_type = 'purchase_order'";
            break;
        case 'delivery_order':
            $query .= " AND a.activity_type = 'delivery_order'";
            break;
        case 'order':
            $query .= " AND a.activity_type = 'order'";
            break;
    }
}

$query .= " ORDER BY a.created_at DESC, a.id DESC LIMIT 100";
$activities = $mysqli->query($query);

include 'admin-header.php';
?>

<div class="container">
    <h1>Activity History</h1>

    <div class="filter-section">
        <?php if ($_SESSION['role'] === 'Coordinator'): ?>
            <div class="coordinator-filters">
                <a href="?category=all" class="filter-btn <?php echo $category === 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> All Activities
                </a>
                <a href="?category=order" class="filter-btn <?php echo $category === 'order' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="?category=purchase_order" class="filter-btn <?php echo $category === 'purchase_order' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice"></i> Purchase Orders
                </a>
                <a href="?category=delivery_order" class="filter-btn <?php echo $category === 'delivery_order' ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i> Delivery Orders
                </a>
            </div>
        <?php else: ?>
            <a href="?category=all" class="filter-btn <?php echo $category === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> All Activities
            </a>
            <?php if ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Storekeeper'): ?>
                <a href="?category=stock" class="filter-btn <?php echo $category === 'stock' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> Stock In / Out
                </a>
                <a href="?category=inventory" class="filter-btn <?php echo $category === 'inventory' ? 'active' : ''; ?>">
                    <i class="fas fa-warehouse"></i> Inventory
                </a>
            <?php endif; ?>
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <a href="?category=vehicles" class="filter-btn <?php echo $category === 'vehicles' ? 'active' : ''; ?>">
                    <i class="fas fa-car"></i> Vehicles
                </a>
                <a href="?category=user" class="filter-btn <?php echo $category === 'user' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> User
                </a>
                <a href="?category=supplier" class="filter-btn <?php echo $category === 'supplier' ? 'active' : ''; ?>">
                    <i class="fas fa-industry"></i> Supplier
                </a>
            <?php endif; ?>
            <?php if ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Storekeeper'): ?>
                <a href="?category=order" class="filter-btn <?php echo $category === 'order' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
            <?php endif; ?>
            <?php if ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Storekeeper' || $_SESSION['role'] === 'Coordinator'): ?>
                <a href="?category=purchase_order" class="filter-btn <?php echo $category === 'purchase_order' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice"></i> Purchase Orders
                </a>
            <?php endif; ?>
            <?php if ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Driver'): ?>
                <a href="?category=delivery_order" class="filter-btn <?php echo $category === 'delivery_order' ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i> Delivery Orders
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="activity-list">
        <?php if ($activities && $activities->num_rows > 0): ?>
            <?php while ($activity = $activities->fetch_assoc()): ?>
                <div class="activity-item">
                    <div class="activity-time">
                        <?php echo $activity['formatted_date']; ?>
                    </div>
                    <div class="activity-content">
                        <div class="activity-description">
                            <?php echo htmlspecialchars($activity['description']); ?>
                        </div>
                        <div class="activity-meta">
                            <?php
                            $icon_class = '';
                            switch($activity['activity_type']) {
                                case 'inventory':
                                    $icon_class = 'fas fa-warehouse';
                                    break;
                                case 'stock_in':
                                    $icon_class = 'fas fa-box';
                                    break;
                                case 'stock_out':
                                    $icon_class = 'fas fa-box-open';
                                    break;
                                case 'purchase_order':
                                    $icon_class = 'fas fa-file-invoice';
                                    break;
                                case 'delivery_order':
                                    $icon_class = 'fas fa-truck';
                                    break;
                                case 'order':
                                    $icon_class = 'fas fa-shopping-cart';
                                    break;
                                case 'supplier':
                                    $icon_class = 'fas fa-industry';
                                    break;
                                case 'user':
                                    $icon_class = 'fas fa-user';
                                    break;
                                case 'vehicles':
                                    $icon_class = 'fas fa-car';
                                    break;
                                default:
                                    $icon_class = 'fas fa-history';
                            }
                            ?>
                            <i class="<?php echo $icon_class; ?>"></i>
                            <span class="activity-type"><?php echo ucfirst(str_replace('_', ' ', $activity['activity_type'])); ?></span>
                            <span class="activity-user">by <?php echo htmlspecialchars($activity['created_by'] ?? 'System'); ?></span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-activities">
                <i class="fas fa-info-circle"></i>
                <p>No activities found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .container {
        padding: 20px 30px;
        max-width: 1200px;
        margin: 0 auto;
        min-height: calc(100vh - 72px);
        background: #f8f9fa;
        position: relative;
        width: 100%;
    }

    h1 {
        color: #5c1f00;
        font-size: 24px;
        margin-bottom: 20px;
        padding: 15px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .filter-section {
        margin-bottom: 20px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        background: white;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    /* For Coordinator role, ensure buttons are always visible */
    .coordinator-filters {
        display: flex;
        gap: 10px;
        flex-wrap: nowrap;
        overflow-x: auto;
        padding-bottom: 5px;
        width: 100%;
    }

    .filter-btn {
        padding: 8px 16px;
        border-radius: 20px;
        background: #f0f0f0;
        color: #333;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: fit-content;
        white-space: nowrap;
        border: 1px solid #e0e0e0;
    }

    .filter-btn i {
        font-size: 14px;
        width: 16px;
        text-align: center;
    }

    .filter-btn:hover {
        background: #e0e0e0;
    }

    .filter-btn.active {
        background: #5c1f00;
        color: white;
        border-color: #5c1f00;
    }

    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
        padding: 15px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .activity-item {
        background: white;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        display: flex;
        gap: 20px;
    }

    .activity-time {
        color: #666;
        font-size: 14px;
        min-width: 150px;
    }

    .activity-content {
        flex-grow: 1;
    }

    .activity-description {
        margin-bottom: 8px;
        color: #333;
    }

    .activity-meta {
        display: flex;
        gap: 15px;
        font-size: 14px;
    }

    .activity-type {
        color: #5c1f00;
        font-weight: 500;
    }

    .activity-user {
        color: #666;
    }

    .no-activities {
        text-align: center;
        padding: 40px;
        background: white;
        border-radius: 8px;
        color: #666;
    }

    .no-activities i {
        font-size: 48px;
        margin-bottom: 10px;
        color: #5c1f00;
    }

    @media (max-width: 768px) {
        .activity-item {
            flex-direction: column;
            gap: 10px;
        }

        .activity-time {
            min-width: unset;
        }

        .activity-meta {
            flex-direction: column;
            gap: 5px;
        }
    }
</style>

<?php $mysqli->close(); ?>
