<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../mainlogin.html");
    exit();
}

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$username = $_SESSION['username'];

// Get all orders for the current user with their items
$query = "
    SELECT 
        o.order_id,
        o.pickup_address,
        o.delivery_address,
        o.pickup_date,
        o.pickup_contact,
        o.delivery_contact,
        o.status,
        o.created_at,
        GROUP_CONCAT(
            CONCAT(
                oi.quantity,
                ' x ',
                oi.goods_type,
                ' (',
                oi.weight,
                'kg)'
            ) SEPARATOR '<br>'
        ) as items
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.customer_username = ?
    GROUP BY o.order_id
    ORDER BY o.created_at DESC";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

include 'user-header.php';
?>

<style>
    .container {
        max-width: 90%;
        margin: 1rem auto;
        padding: 1rem;
    }

    .page-header {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
        text-align: center;
    }

    .page-title {
        color: #5c1f00;
        font-size: 1.8rem;
        margin-bottom: 0.3rem;
    }

    .page-description {
        color: #666;
        font-size: 1.1rem;
    }

    .orders-container {
        display: grid;
        gap: 1rem;
    }

    .order-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        padding: 1.2rem;
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.8rem;
        padding-bottom: 0.8rem;
        border-bottom: 1px solid #eee;
    }

    .order-id {
        font-size: 1.2rem;
        font-weight: 600;
        color: #5c1f00;
    }

    .order-date {
        color: #666;
        font-size: 0.9rem;
    }

    .order-status {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-approved {
        background: #d4edda;
        color: #155724;
    }

    .status-in-transit {
        background: #cce5ff;
        color: #004085;
    }

    .status-delivered {
        background: #d1e7dd;
        color: #0f5132;
    }

    .order-details {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 0.8rem;
    }

    .detail-group {
        margin-bottom: 0.8rem;
    }

    .detail-label {
        font-weight: 500;
        color: #666;
        margin-bottom: 0.2rem;
    }

    .detail-value {
        color: #333;
    }

    .items-section {
        background: #f8f9fa;
        padding: 0.8rem;
        border-radius: 5px;
        margin-top: 0.8rem;
    }

    .items-title {
        font-weight: 500;
        color: #5c1f00;
        margin-bottom: 0.3rem;
    }

    .no-orders {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    }

    .no-orders i {
        font-size: 3rem;
        color: #5c1f00;
        margin-bottom: 1rem;
    }

    .no-orders p {
        color: #666;
        margin-bottom: 1rem;
    }

    .place-order-btn {
        display: inline-block;
        background: #5c1f00;
        color: white;
        padding: 0.8rem 1.5rem;
        border-radius: 5px;
        text-decoration: none;
        transition: background-color 0.3s;
    }

    .place-order-btn:hover {
        background: #7a2900;
    }

    @media (max-width: 992px) {
        .order-details {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .container {
            max-width: 95%;
            padding: 0.5rem;
        }
        
        .order-details {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">My Orders</h1>
        <p class="page-description">View and track your transportation orders</p>
    </div>

    <div class="orders-container">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($order = $result->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-id">Order #<?php echo $order['order_id']; ?></div>
                            <div class="order-date">
                                Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                            </div>
                        </div>
                        <div class="order-status status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </div>
                    </div>

                    <div class="order-details">
                        <div class="detail-group">
                            <div class="detail-label">Pickup Details</div>
                            <div class="detail-value">
                                <?php echo nl2br(htmlspecialchars($order['pickup_address'])); ?><br>
                                Contact: <?php echo htmlspecialchars($order['pickup_contact']); ?>
                            </div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label">Delivery Details</div>
                            <div class="detail-value">
                                <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?><br>
                                Contact: <?php echo htmlspecialchars($order['delivery_contact']); ?>
                            </div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label">Pickup Date</div>
                            <div class="detail-value">
                                <?php echo date('F j, Y', strtotime($order['pickup_date'])); ?>
                            </div>
                        </div>
                    </div>

                    <div class="items-section">
                        <div class="items-title">Items in this Order</div>
                        <div class="items-list">
                            <?php echo $order['items']; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-orders">
                <i class="fas fa-box-open"></i>
                <p>You haven't placed any orders yet.</p>
                <a href="place-order.php" class="place-order-btn">
                    <i class="fas fa-plus"></i> Place Your First Order
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$stmt->close();
$mysqli->close(); 
?> 