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
$query = "SELECT *, DATE_FORMAT(created_at, '%b %d, %Y %H:%i:%S') as formatted_date FROM activities WHERE 1";

// Add role-specific filters
if ($_SESSION['role'] === 'Coordinator') {
    $query .= " AND activity_type IN ('purchase_order', 'delivery_order')";
} elseif ($_SESSION['role'] === 'Driver') {
    $query .= " AND activity_type IN ('delivery_order')";
} else {
    // Modify query based on category
    switch($category) {
        case 'stock':
            $query .= " AND activity_type IN ('stock_in', 'stock_out')";
            break;
        case 'inventory':
            $query .= " AND activity_type = 'inventory'";
            break;
        case 'vehicles':
            $query .= " AND activity_type = 'vehicles'";
            break;
        case 'user':
            $query .= " AND activity_type = 'user'";
            break;
        case 'supplier':
            $query .= " AND activity_type = 'supplier'";
            break;
        case 'purchase_order':
            $query .= " AND activity_type = 'purchase_order'";
            break;
        case 'delivery_order':
            $query .= " AND activity_type = 'delivery_order'";
            break;
    }
}

$query .= " ORDER BY created_at DESC, id DESC LIMIT 100";
$activities = $mysqli->query($query);

include 'admin-header.php';
?>

<div class="container">
    <h1>Activity History</h1>

    <div class="filter-section">
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
        <?php if ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Storekeeper' || $_SESSION['role'] === 'Coordinator'): ?>
            <a href="?category=purchase_order" class="filter-btn <?php echo $category === 'purchase_order' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice"></i> Purchase Orders
            </a>
        <?php endif; ?>
        <a href="?category=delivery_order" class="filter-btn <?php echo $category === 'delivery_order' ? 'active' : ''; ?>">
            <i class="fas fa-truck"></i> Delivery Orders
        </a>
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
                            <span class="activity-type"><?php echo ucfirst(str_replace('_', ' ', $activity['activity_type'])); ?></span>
                            <span class="activity-user">by <?php echo htmlspecialchars($activity['created_by']); ?></span>
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
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    h1 {
        color: #5c1f00;
        font-size: 24px;
        margin-bottom: 20px;
    }

    .filter-section {
        margin-bottom: 20px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .filter-btn {
        padding: 8px 16px;
        border-radius: 20px;
        background: #f0f0f0;
        color: #333;
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-btn i {
        font-size: 14px;
    }

    .filter-btn:hover {
        background: #e0e0e0;
    }

    .filter-btn.active {
        background: #5c1f00;
        color: white;
    }

    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
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
