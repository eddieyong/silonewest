<?php
session_start();
require_once 'functions.php';

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

// Get category from URL parameter, default to 'all'
$category = isset($_GET['category']) ? strtolower($_GET['category']) : 'all';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base query
$base_query = "FROM activities WHERE 1";

// Modify query based on category
switch($category) {
    case 'stock':
        $base_query .= " AND activity_type IN ('stock_in', 'stock_out')";
        break;
    case 'inventory':
        $base_query .= " AND activity_type = 'inventory'";
        break;
    case 'vehicle':
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
$query = "SELECT *, CONVERT_TZ(created_at, @@session.time_zone, '+08:00') as created_at_local " 
       . $base_query 
       . " ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$activities = $stmt->get_result();

include 'admin-header.php';
?>

<style>
    .container {
        padding: 20px 30px;
        background: #f8f9fa;
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

    .category-filter {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        background: white;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .category-btn {
        padding: 10px 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: white;
        color: #666;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 0.95rem;
        text-decoration: none;
    }

    .category-btn:hover {
        border-color: #5c1f00;
        color: #5c1f00;
        background: #fff9f5;
    }

    .category-btn.active {
        background: #5c1f00;
        border-color: #5c1f00;
        color: white;
    }

    .activity-list {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .activity-item {
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .activity-icon.inventory {
        background: #e8f5e9;
        color: #388e3c;
    }

    .activity-icon.stock_in {
        background: #e3f2fd;
        color: #1976d2;
    }

    .activity-icon.stock_out {
        background: #fce4ec;
        color: #c2185b;
    }

    .activity-icon.stock_alert {
        background: #fff3e0;
        color: #f57c00;
    }

    .activity-icon.supplier {
        background: #e8eaf6;
        color: #3f51b5;
    }

    .activity-icon.user {
        background: #f3e5f5;
        color: #9c27b0;
    }

    .activity-icon.vehicle {
        background: #efebe9;
        color: #5d4037;
    }

    .activity-content {
        flex-grow: 1;
    }

    .activity-time {
        color: #666;
        font-size: 0.875rem;
        margin-bottom: 5px;
    }

    .activity-description {
        color: #333;
        line-height: 1.5;
    }

    .activity-description strong {
        color: #5c1f00;
        font-weight: 600;
    }

    .stock-in {
        color: #28a745;
        font-weight: 600;
    }

    .stock-out {
        color: #dc3545;
        font-weight: 600;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 30px;
    }

    .pagination a {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        color: #333;
        text-decoration: none;
        transition: all 0.3s;
    }

    .pagination a:hover {
        background: #f8f9fa;
        border-color: #5c1f00;
        color: #5c1f00;
    }

    .pagination a.active {
        background: #5c1f00;
        border-color: #5c1f00;
        color: white;
    }

    .pagination .dots {
        padding: 8px 12px;
        border: none;
        border-radius: 4px;
        color: #333;
        text-decoration: none;
        transition: all 0.3s;
    }

    .no-activities {
        text-align: center;
        padding: 40px 20px;
        color: #666;
    }

    .refresh-btn {
        background: #5c1f00;
        color: white;
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: opacity 0.3s;
    }
    
    .refresh-btn:hover {
        opacity: 0.9;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Activity History</h1>
    </div>

    <div class="category-filter">
        <a href="?category=all" class="category-btn <?php echo $category === 'all' ? 'active' : ''; ?>">
            All Activities
        </a>
        <a href="?category=inventory" class="category-btn <?php echo $category === 'inventory' ? 'active' : ''; ?>">
            Inventory
        </a>
        <a href="?category=vehicle" class="category-btn <?php echo $category === 'vehicle' ? 'active' : ''; ?>">
            Vehicles
        </a>
        <a href="?category=user" class="category-btn <?php echo $category === 'user' ? 'active' : ''; ?>">
            User
        </a>
        <a href="?category=supplier" class="category-btn <?php echo $category === 'supplier' ? 'active' : ''; ?>">
            Supplier
        </a>
        <a href="?category=stock" class="category-btn <?php echo $category === 'stock' ? 'active' : ''; ?>">
            Stock In / Out
        </a>
        <a href="?category=purchase_order" class="category-btn <?php echo $category === 'purchase_order' ? 'active' : ''; ?>">
            Purchase Orders
        </a>
        <a href="?category=delivery_order" class="category-btn <?php echo $category === 'delivery_order' ? 'active' : ''; ?>">
            Delivery Orders
        </a>
    </div>

    <div class="activity-list">
        <?php if ($activities->num_rows > 0): ?>
            <?php while ($activity = $activities->fetch_assoc()): ?>
                <div class="activity-item">
                    <div class="activity-icon <?php echo htmlspecialchars($activity['activity_type']); ?>">
                        <i class="fas fa-<?php 
                            echo match($activity['activity_type']) {
                                'inventory' => 'box',
                                'stock_in' => 'arrow-circle-up',
                                'stock_out' => 'arrow-circle-down',
                                'stock_alert' => 'exclamation-triangle',
                                'supplier' => 'truck',
                                'user' => 'users',
                                'vehicle' => 'car',
                                'purchase_order' => 'file-invoice',
                                'delivery_order' => 'truck',
                                default => 'circle'
                            };
                        ?>"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-time">
                            <?php echo date('M d, Y H:i:s', strtotime($activity['created_at_local'])); ?>
                        </div>
                        <div class="activity-description">
                            <?php 
                            $description = htmlspecialchars($activity['description']);
                            
                            // Replace "Stock Out X items" with colored version
                            $description = preg_replace(
                                '/Stock Out (\d+) items?/',
                                '<span class="stock-out">Stock Out $1 items</span>',
                                $description
                            );
                            
                            // Replace "Stock In X items" with colored version
                            $description = preg_replace(
                                '/Stock In (\d+) items?/',
                                '<span class="stock-in">Stock In $1 items</span>',
                                $description
                            );
                            
                            echo $description;
                            ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-activities">
                <i class="fas fa-info-circle"></i>
                <?php if ($category !== 'all'): ?>
                    No activities found for this category.
                <?php else: ?>
                    No activities recorded yet.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php
            $current_page = $page;
            $start_page = 1;
            $end_page = $total_pages;
            
            // Always show first page
            echo '<a href="?category=' . $category . '&page=1"' . ($current_page == 1 ? ' class="active"' : '') . '>1</a>';
            
            if ($total_pages > 7) {
                // Show dots after first page if necessary
                if ($current_page > 3) {
                    echo '<span class="dots">...</span>';
                }
                
                // Calculate range around current page
                $range_start = max(2, $current_page - 1);
                $range_end = min($total_pages - 1, $current_page + 1);
                
                // Adjust range if current page is near start or end
                if ($current_page <= 3) {
                    $range_end = 4;
                }
                if ($current_page >= $total_pages - 2) {
                    $range_start = $total_pages - 3;
                }
                
                // Show page numbers in range
                for ($i = $range_start; $i <= $range_end; $i++) {
                    echo '<a href="?category=' . $category . '&page=' . $i . '"' . 
                         ($current_page == $i ? ' class="active"' : '') . '>' . $i . '</a>';
                }
                
                // Show dots before last page if necessary
                if ($current_page < $total_pages - 2) {
                    echo '<span class="dots">...</span>';
                }
            } else {
                // If less than 8 pages, show all numbers
                for ($i = 2; $i < $total_pages; $i++) {
                    echo '<a href="?category=' . $category . '&page=' . $i . '"' . 
                         ($current_page == $i ? ' class="active"' : '') . '>' . $i . '</a>';
                }
            }
            
            // Always show last page if more than 1 page exists
            if ($total_pages > 1) {
                echo '<a href="?category=' . $category . '&page=' . $total_pages . '"' . 
                     ($current_page == $total_pages ? ' class="active"' : '') . '>' . $total_pages . '</a>';
            }
            
            // Next button
            if ($current_page < $total_pages) {
                echo '<a href="?category=' . $category . '&page=' . ($current_page + 1) . '" class="next">Next</a>';
            }
            ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Auto-refresh the page every 60 seconds
setInterval(function() {
    window.location.reload();
}, 60000);
</script>

<?php $mysqli->close(); ?>
