<?php
session_start();
require_once 'functions.php';

// Check if user is logged in and has Admin role
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['Admin', 'Storekeeper', 'Coordinator'])) {
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

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base query
$base_query = "FROM activities WHERE 1";

// Add role-based filtering
if ($_SESSION['role'] === 'Storekeeper') {
    $base_query .= " AND activity_type IN ('inventory', 'stock_in', 'stock_out', 'purchase_order', 'delivery_order')";
} elseif ($_SESSION['role'] === 'Coordinator') {
    $base_query .= " AND activity_type IN ('inventory', 'purchase_order', 'delivery_order')";
}

// Modify query based on category
switch($category) {
    case 'stock':
        $base_query .= " AND activity_type IN ('stock_in', 'stock_out')";
        break;
    case 'inventory':
        $base_query .= " AND activity_type = 'inventory'";
        break;
    case 'vehicles':
        $base_query .= " AND activity_type = 'vehicles'";
        break;
    case 'user':
        $base_query .= " AND activity_type = 'user'";
        break;
    case 'supplier':
        $base_query .= " AND activity_type = 'supplier'";
        break;
    case 'purchase_order':
        $base_query .= " AND activity_type = 'purchase_order'";
        break;
    case 'delivery_order':
        $base_query .= " AND activity_type = 'delivery_order'";
        break;
}

// Get total number of activities for pagination
$total_query = "SELECT COUNT(*) as count " . $base_query;
$total_stmt = $mysqli->prepare($total_query);
$total_stmt->execute();
$total_row = $total_stmt->get_result()->fetch_assoc();
$total_activities = $total_row['count'];
$total_pages = ceil($total_activities / $limit);

// Get activities for current page
$query = "SELECT *, DATE_FORMAT(created_at, '%b %d, %Y %H:%i:%S') as formatted_date " 
       . $base_query 
       . " ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$activities = $stmt->get_result();

include 'admin-header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Activity History</h1>
    </div>

    <div class="filter-section">
        <a href="?category=all" class="filter-btn <?php echo $category === 'all' ? 'active' : ''; ?>">
            <i class="fas fa-list"></i> All Activities
        </a>
        <?php if ($_SESSION['role'] !== 'Coordinator'): ?>
            <a href="?category=stock" class="filter-btn <?php echo $category === 'stock' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Stock In / Out
            </a>
        <?php endif; ?>
        <a href="?category=inventory" class="filter-btn <?php echo $category === 'inventory' ? 'active' : ''; ?>">
            <i class="fas fa-warehouse"></i> Inventory
        </a>
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
        <a href="?category=purchase_order" class="filter-btn <?php echo $category === 'purchase_order' ? 'active' : ''; ?>">
            <i class="fas fa-file-invoice"></i> Purchase Orders
        </a>
        <a href="?category=delivery_order" class="filter-btn <?php echo $category === 'delivery_order' ? 'active' : ''; ?>">
            <i class="fas fa-truck"></i> Delivery Orders
        </a>
    </div>

    <div class="activity-list">
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
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php
            $range = 2; // How many pages to show on each side of current page
            
            // Always show first page
            echo '<a href="?page=1&category=' . $category . '" 
                   class="page-link ' . ($page === 1 ? 'active' : '') . '">1</a>';

            // Show dots after first page if necessary
            if ($page - $range > 2) {
                echo '<span class="page-dots">...</span>';
            }

            // Show pages around current page
            for ($i = max(2, $page - $range); $i <= min($page + $range, $total_pages - 1); $i++) {
                echo '<a href="?page=' . $i . '&category=' . $category . '" 
                       class="page-link ' . ($page === $i ? 'active' : '') . '">' . $i . '</a>';
            }

            // Show dots before last page if necessary
            if ($page + $range < $total_pages - 1) {
                echo '<span class="page-dots">...</span>';
            }

            // Always show last page if there is more than one page
            if ($total_pages > 1) {
                echo '<a href="?page=' . $total_pages . '&category=' . $category . '" 
                       class="page-link ' . ($page === $total_pages ? 'active' : '') . '">' . $total_pages . '</a>';
            }
            ?>
        </div>
    <?php endif; ?>
</div>

<style>
.container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 20px;
}

.page-header h1 {
    color: #5c1f00;
    font-size: 24px;
    margin: 0;
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

.pagination {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    gap: 5px;
}

.page-link {
    padding: 8px 12px;
    border-radius: 4px;
    background: #f0f0f0;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
    min-width: 35px;
    text-align: center;
}

.page-link:hover {
    background: #e0e0e0;
}

.page-link.active {
    background: #5c1f00;
    color: white;
}

.page-dots {
    color: #666;
    padding: 0 5px;
}
</style>

<?php $mysqli->close(); ?>
