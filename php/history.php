<?php
session_start();
require_once 'functions.php';

// Set timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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

// Get activities with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Items per page
$offset = ($page - 1) * $limit;

// Get total number of activities
$total_result = $mysqli->query("SELECT COUNT(*) as count FROM activities");
$total_row = $total_result->fetch_assoc();
$total_activities = $total_row['count'];
$total_pages = ceil($total_activities / $limit);

// Get activities for current page with explicit ordering and proper timezone conversion
$query = "SELECT *, 
          CONVERT_TZ(created_at, @@session.time_zone, '+08:00') as created_at_local 
          FROM activities 
          ORDER BY created_at DESC, id DESC 
          LIMIT ? OFFSET ?";
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

    .activity-icon.inventory {
        background: #e8f5e9;
        color: #388e3c;
    }

    .activity-icon.supplier {
        background: #e8eaf6;
        color: #3f51b5;
    }

    .activity-icon.user {
        background: #f3e5f5;
        color: #9c27b0;
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
        <button onclick="window.location.reload()" class="refresh-btn">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>

    <div class="activity-list">
        <?php if ($activities->num_rows > 0): ?>
            <?php while ($activity = $activities->fetch_assoc()): ?>
                <div class="activity-item">
                    <div class="activity-icon <?php echo htmlspecialchars($activity['activity_type']); ?>">
                        <?php 
                        switch($activity['activity_type']) {
                            case 'stock_in':
                                echo '<i class="fas fa-arrow-circle-up"></i>';
                                break;
                            case 'stock_out':
                                echo '<i class="fas fa-arrow-circle-down"></i>';
                                break;
                            case 'stock_alert':
                                echo '<i class="fas fa-exclamation-triangle"></i>';
                                break;
                            case 'supplier':
                                echo '<i class="fas fa-building"></i>';
                                break;
                            case 'user':
                                echo '<i class="fas fa-user-cog"></i>';
                                break;
                            case 'vehicle':
                                echo '<i class="fas fa-car"></i>';
                                break;
                            case 'inventory':
                            default:
                                echo '<i class="fas fa-box"></i>';
                                break;
                        }
                        ?>
                    </div>
                    <div class="activity-content">
                        <div class="activity-time">
                            <?php 
                            $timestamp = strtotime($activity['created_at_local']);
                            echo date('M d, Y h:i A', $timestamp); 
                            if (isset($activity['created_by']) && !empty($activity['created_by'])): ?>
                                by <?php echo htmlspecialchars($activity['created_by']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="activity-description">
                            <?php echo formatActivityDescription($activity['description']); ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-activities">
                <p>No activities found.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" <?php echo ($page === $i) ? 'class="active"' : ''; ?>>
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Auto-refresh the page every 30 seconds
setTimeout(function() {
    window.location.reload();
}, 30000);
</script>

<?php $mysqli->close(); ?>
