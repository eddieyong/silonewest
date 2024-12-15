<?php
session_start();
require_once 'permissions.php';
require_once 'functions.php';

// At the very top of the file, after session_start()
error_reporting(0); // Disable error reporting for AJAX requests
ini_set('display_errors', 0);

// Check if user is logged in and has admin role
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

// Get all orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Search functionality
$search_condition = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $mysqli->real_escape_string($_GET['search']);
    $search_condition = " WHERE 
        o.order_id LIKE '%$search%' OR 
        o.customer_username LIKE '%$search%' OR 
        o.pickup_address LIKE '%$search%' OR 
        o.delivery_address LIKE '%$search%' OR 
        c.email LIKE '%$search%' OR 
        c.contact LIKE '%$search%'";
}

// Get total number of orders
$total_orders = $mysqli->query("
    SELECT COUNT(*) as count 
    FROM orders o 
    LEFT JOIN customer c ON o.customer_username = c.username
    $search_condition
")->fetch_assoc()['count'];
$total_pages = ceil($total_orders / $items_per_page);

// Get orders for current page with customer details
$orders = $mysqli->query("
    SELECT o.*, c.email as customer_email, c.contact as customer_contact 
    FROM orders o 
    LEFT JOIN customer c ON o.customer_username = c.username 
    $search_condition
    ORDER BY o.created_at DESC 
    LIMIT $offset, $items_per_page
");

// Get order statistics
$stats = $mysqli->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved_orders,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected_orders
    FROM orders
")->fetch_assoc();

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    // Clear all previous output and buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Start fresh buffer
    ob_start();
    
    header('Content-Type: application/json');
    
    try {
        if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
            throw new Exception("Missing required parameters");
        }

        $order_id = (int)$_POST['order_id'];
        $new_status = $_POST['status'];
        
        // Validate status - exactly matching the ENUM values from the database
        $valid_statuses = ['Pending', 'Approved', 'In Transit', 'Delivered', 'Cancelled'];
        if (!in_array($new_status, $valid_statuses)) {
            throw new Exception("Invalid status value: " . $new_status);
        }
        
        // Start transaction
        $mysqli->begin_transaction();
        
        // Get current status for logging
        $stmt = $mysqli->prepare("SELECT status FROM orders WHERE order_id = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $mysqli->error);
        }
        
        $stmt->bind_param("i", $order_id);
        if (!$stmt->execute()) {
            throw new Exception("Database error: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Order not found");
        }
        
        $current_status = $result->fetch_assoc()['status'];
        
        // Update status
        $stmt = $mysqli->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $mysqli->error);
        }
        
        $stmt->bind_param("si", $new_status, $order_id);
        if (!$stmt->execute()) {
            throw new Exception("Database error: " . $stmt->error);
        }
        
        if ($stmt->affected_rows === 0 && $current_status !== $new_status) {
            throw new Exception("Failed to update status. Current status: " . $current_status . ", New status: " . $new_status);
        }
        
        // Log the activity if the function exists
        $description = "Updated Order status (Order ID: $order_id) from $current_status to $new_status";
        if (function_exists('logActivity')) {
            logActivity($mysqli, 'order', $description);
        }
        
        $mysqli->commit();
        
        // Clear buffer before JSON output
        if (ob_get_length()) ob_clean();
        
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully!'
        ]);
        
    } catch (Exception $e) {
        $mysqli->rollback();
        
        // Clear buffer before JSON output
        if (ob_get_length()) ob_clean();
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
    // End output buffer and exit
    ob_end_flush();
    exit();
}

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

    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: bold;
        margin: 10px 0;
    }

    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }

    .table-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }

    .table tbody tr:hover {
        background: #f8f9fa;
    }

    .badge {
        padding: 6px 12px !important;
        border-radius: 20px !important;
        font-size: 0.85rem !important;
        font-weight: 500 !important;
        display: inline-block !important;
        text-align: center;
        min-width: 90px;
    }

    .bg-warning {
        background-color: #fff3cd !important;
        color: #856404 !important;
        border: 1px solid #ffeeba !important;
    }

    .bg-success {
        background-color: #d4edda !important;
        color: #155724 !important;
        border: 1px solid #c3e6cb !important;
    }

    .bg-danger {
        background-color: #f8d7da !important;
        color: #721c24 !important;
        border: 1px solid #f5c6cb !important;
    }

    .bg-info {
        background-color: #d1ecf1 !important;
        color: #0c5460 !important;
        border: 1px solid #bee5eb !important;
    }

    .form-select.status-select {
        padding: 6px 30px 6px 12px !important;
        border: 1px solid #ced4da !important;
        border-radius: 4px !important;
        font-size: 0.9rem !important;
        color: #495057 !important;
        background-color: #fff !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
        background-repeat: no-repeat !important;
        background-position: right 0.75rem center !important;
        background-size: 16px 12px !important;
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        cursor: pointer !important;
        min-width: 140px !important;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .btn-view {
        background: #e3f2fd;
        color: #1976d2;
        padding: 6px 12px;
        border-radius: 4px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.9rem;
        min-width: 80px;
        justify-content: center;
    }

    .btn-view:hover {
        background: #c8e6ff;
        color: #0056b3;
    }

    .search-box {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-bottom: 30px;
        display: flex;
        gap: 10px;
    }

    .search-box input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.9rem;
    }

    .search-box button {
        background: #5c1f00;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.9rem;
    }

    .search-box button:hover {
        background: #7a2900;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }

    .alert-success {
        color: #0f5132;
        background-color: #d1e7dd;
        border-color: #badbcc;
    }

    .alert-danger {
        color: #842029;
        background-color: #f8d7da;
        border-color: #f5c2c7;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 20px;
    }

    .pagination a {
        padding: 8px 12px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        color: #333;
        text-decoration: none;
    }

    .pagination a:hover {
        background: #f8f9fa;
    }

    .pagination a.active {
        background: #5c1f00;
        color: white;
        border-color: #5c1f00;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Customer Orders Management</h1>
    </div>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['success_msg'];
                unset($_SESSION['success_msg']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger">
            <?php 
                echo $_SESSION['error_msg'];
                unset($_SESSION['error_msg']);
            ?>
        </div>
    <?php endif; ?>

    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #856404;"><?php echo $stats['pending_orders']; ?></div>
            <div class="stat-label">Pending Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #0c5460;"><?php echo $stats['approved_orders']; ?></div>
            <div class="stat-label">Approved Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #155724;"><?php echo $stats['completed_orders']; ?></div>
            <div class="stat-label">Completed Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #721c24;"><?php echo $stats['cancelled_orders']; ?></div>
            <div class="stat-label">Cancelled Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #721c24;"><?php echo $stats['rejected_orders']; ?></div>
            <div class="stat-label">Rejected Orders</div>
        </div>
    </div>

    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search orders..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button onclick="searchOrders()">
            <i class="fas fa-search"></i> Search
        </button>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Pickup Address</th>
                    <th>Delivery Address</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders && $orders->num_rows > 0): ?>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($order['customer_username']); ?><br>
                                <small><?php echo htmlspecialchars($order['customer_email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($order['customer_contact']); ?></td>
                            <td><?php echo htmlspecialchars($order['pickup_address']); ?></td>
                            <td><?php echo htmlspecialchars($order['delivery_address']); ?></td>
                            <td>
                                <?php
                                $status = trim($order['status']);
                                $badge_class = '';
                                switch($status) {
                                    case 'Pending':
                                        $badge_class = 'badge bg-warning';
                                        break;
                                    case 'Approved':
                                        $badge_class = 'badge bg-info';
                                        break;
                                    case 'Completed':
                                        $badge_class = 'badge bg-success';
                                        break;
                                    case 'Cancelled':
                                    case 'Rejected':
                                        $badge_class = 'badge bg-danger';
                                        break;
                                    default:
                                        $badge_class = 'badge bg-secondary';
                                }
                                echo "<span class='$badge_class'>" . htmlspecialchars($status) . "</span>";
                                ?>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view-order.php?id=<?php echo $order['order_id']; ?>" class="btn-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if ($order['status'] !== 'Completed' && $order['status'] !== 'Cancelled'): ?>
                                        <select class="form-select status-select" onchange="updateStatus(this, '<?php echo $order['order_id']; ?>')">
                                            <option value="">Change Status</option>
                                            <option value="Approved">Approved</option>
                                            <option value="In Transit">In Transit</option>
                                            <option value="Delivered">Delivered</option>
                                            <option value="Cancelled">Cancelled</option>
                                        </select>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No orders found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" 
                   class="<?php echo $page === $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function searchOrders() {
    const searchTerm = document.getElementById('searchInput').value;
    window.location.href = `view-orders.php?search=${encodeURIComponent(searchTerm)}`;
}

document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchOrders();
    }
});

function updateStatus(selectElement, orderId) {
    const newStatus = selectElement.value;
    if (!newStatus) return;

    if (confirm('Are you sure you want to update the status to ' + newStatus + '?')) {
        const formData = new FormData();
        formData.append('update_status', '1');
        formData.append('order_id', orderId);
        formData.append('status', newStatus);

        fetch('view-orders.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                throw new Error(data.message || 'Error updating status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating status: ' + error.message);
            if (selectElement && selectElement.options) {
                selectElement.selectedIndex = 0; // Reset to first option
            }
        });
    } else {
        if (selectElement && selectElement.options) {
            selectElement.selectedIndex = 0; // Reset to first option
        }
    }
}
</script>

<?php $mysqli->close(); ?>