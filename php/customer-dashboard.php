<?php
// Start the session
session_start();

// Check if the 'username' session variable is set before using it
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';  

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get customer's order statistics
$stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_orders,
    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_orders
    FROM orders 
    WHERE customer_username = ?";
$stmt = $mysqli->prepare($stats_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Get recent orders
$recent_orders = $mysqli->prepare("
    SELECT * FROM orders 
    WHERE customer_username = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recent_orders->bind_param("s", $username);
$recent_orders->execute();
$recent_orders_result = $recent_orders->get_result();
?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - SILO</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            margin-bottom: 10px;
            color: #5c1f00;
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

        .overview-item {
            padding: 15px;
            border-left: 4px solid #5c1f00;
            background: #f8f9fa;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .overview-title {
            font-weight: 500;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .overview-subtitle {
            color: #666;
            font-size: 0.9rem;
        }

        .overview-value {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .overview-value.pending { color: #ffc107; }
        .overview-value.completed { color: #28a745; }
        .overview-value.cancelled { color: #dc3545; }

        .recent-orders {
            margin-top: 20px;
        }

        .order-item {
            padding: 15px;
            border-left: 4px solid #5c1f00;
            background: #f8f9fa;
            margin-bottom: 10px;
            transition: transform 0.3s;
        }

        .order-item:hover {
            transform: translateX(5px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .order-id {
            font-weight: 500;
            color: #333;
        }

        .order-date {
            color: #666;
            font-size: 0.9rem;
        }

        .order-details {
            color: #666;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .view-all {
            font-size: 0.9rem;
            color: #0066cc;
            text-decoration: none;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php include 'user-header.php'; ?>

<div class="container">
    <h1 class="welcome-message">
        Welcome, <?php echo htmlspecialchars($username); ?>! <span style="font-size: 2rem;">👋</span>
    </h1>

    <div class="quick-access-grid">
        <div class="quick-access-card">
            <div class="card-icon">📦</div>
            <h2 class="card-title">My Orders</h2>
            <div class="card-buttons">
                <a href="place-order.php" class="card-btn">
                    <i class="fas fa-plus"></i> Place New Order
                </a>
                <a href="my-orders.php" class="card-btn">
                    <i class="fas fa-list"></i> View My Orders
                </a>
            </div>
        </div>

        <div class="quick-access-card">
            <div class="card-icon">👤</div>
            <h2 class="card-title">My Profile</h2>
            <div class="card-buttons">
                <a href="customer-profile.php" class="card-btn">
                    <i class="fas fa-user"></i> View Profile
                </a>
                <a href="edit-customer.php" class="card-btn">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>
        </div>

        <div class="quick-access-card">
            <div class="card-icon">📱</div>
            <h2 class="card-title">Contact Support</h2>
            <div class="card-buttons">
                <a href="https://wa.me/60367317663" class="card-btn" target="_blank">
                    <i class="fab fa-whatsapp"></i> WhatsApp Support
                </a>
                <a href="mailto:service@silomsdnbhd.com" class="card-btn">
                    <i class="fas fa-envelope"></i> Email Support
                </a>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-section">
            <h2 class="section-title">
                <span><i class="fas fa-chart-line"></i> Order Statistics</span>
            </h2>

            <div class="overview-item">
                <div>
                    <div class="overview-title">
                        <i class="fas fa-shopping-cart"></i> Total Orders
                    </div>
                    <div class="overview-subtitle">All time orders placed</div>
                </div>
                <div class="overview-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
            </div>

            <div class="overview-item">
                <div>
                    <div class="overview-title">
                        <i class="fas fa-clock"></i> Pending Orders
                    </div>
                    <div class="overview-subtitle">Orders awaiting completion</div>
                </div>
                <div class="overview-value pending"><?php echo $stats['pending_orders'] ?? 0; ?></div>
            </div>

            <div class="overview-item">
                <div>
                    <div class="overview-title">
                        <i class="fas fa-check-circle"></i> Completed Orders
                    </div>
                    <div class="overview-subtitle">Successfully delivered orders</div>
                </div>
                <div class="overview-value completed"><?php echo $stats['completed_orders'] ?? 0; ?></div>
            </div>

            <div class="overview-item">
                <div>
                    <div class="overview-title">
                        <i class="fas fa-times-circle"></i> Cancelled Orders
                    </div>
                    <div class="overview-subtitle">Orders that were cancelled</div>
                </div>
                <div class="overview-value cancelled"><?php echo $stats['cancelled_orders'] ?? 0; ?></div>
            </div>
        </div>

        <div class="dashboard-section">
            <h2 class="section-title">
                <span><i class="fas fa-history"></i> Recent Orders</span>
                <a href="my-orders.php" class="view-all">View All</a>
            </h2>

            <div class="recent-orders">
                <?php if ($recent_orders_result && $recent_orders_result->num_rows > 0): ?>
                    <?php while ($order = $recent_orders_result->fetch_assoc()): ?>
                        <div class="order-item">
                            <div class="order-header">
                                <span class="order-id">Order #<?php echo htmlspecialchars($order['order_id']); ?></span>
                                <span class="order-date"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="order-details">
                                <div>Pickup: <?php echo htmlspecialchars($order['pickup_address']); ?></div>
                                <div>Delivery: <?php echo htmlspecialchars($order['delivery_address']); ?></div>
                                <div>
                                    Status: 
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="order-item">
                        <div class="order-header">
                            <span class="order-id">No Orders Yet</span>
                        </div>
                        <div class="order-details">
                            You haven't placed any orders yet. Click "Place New Order" to get started!
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="footer-content">
        <div class="footer-left">
            <h3>Contact Us</h3>
            <p>HQ Office Contact: <a href="tel:+60367317663">+03-6731 7663</a></p>
            <p>Email: <a href="mailto:service@silomsdnbhd.com">service@silomsdnbhd.com</a></p>
            <p><a href="https://wa.me/60367317663" class="green-button" target="_blank"> <img src="img/whatsapp.png" alt="WhatsApp" class="cwhatsapp-icon">WhatsApp: Chat with us !!</img></a></p>
        </div>
        <div class="footer-right">
            <h3>Follow Us On Our Social Media:</h3>
            <div class="social-icons">
                <a href="https://www.facebook.com/profile.php/?id=100063545650222" class="social-icon" target="_blank"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.instagram.com/siloforyou/" class="social-icon" target="_blank"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>©2024 Silo (M) Sdn. Bhd. All Rights Reserved</p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script>
    // Auto-refresh the page every 60 seconds to keep order status updated
    setInterval(function() {
        window.location.reload();
    }, 60000);
</script>

<?php $mysqli->close(); ?>
</body>
</html>
 