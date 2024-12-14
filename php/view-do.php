<?php
session_start();
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../admin-login.html");
    exit();
}

// Check if user has permission to access Delivery Orders
if (!in_array($_SESSION['role'], ['Admin', 'Storekeeper', 'Coordinator'])) {
    header("Location: inventory.php");
    $_SESSION['error_msg'] = "You don't have permission to view Delivery Orders.";
    exit();
}

// Set view-only mode for Coordinator
$isViewOnly = ($_SESSION['role'] === 'Coordinator');

if (!isset($_GET['do'])) {
    header("Location: delivery-orders.php");
    exit();
}

$mysqli = new mysqli("localhost", "root", "", "fyp");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$do_number = $_GET['do'];

// Get DO details with supplier name
$query = "SELECT do.*, po.supplier_name 
          FROM delivery_orders do 
          LEFT JOIN purchase_orders po ON do.po_number = po.po_number 
          WHERE do.do_number = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $do_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: delivery-orders.php");
    exit();
}

$do = $result->fetch_assoc();

// Get DO items with inventory details
$items_query = "SELECT di.*, i.inventory_item 
                FROM do_items di 
                LEFT JOIN inventory i ON di.bar_code = i.bar_code 
                WHERE di.do_number = ?";
$stmt = $mysqli->prepare($items_query);
$stmt->bind_param("s", $do_number);
$stmt->execute();
$items_result = $stmt->get_result();

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

    .btn-back {
        background: #5c1f00;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-back:hover {
        background: #7a2900;
        color: white;
        text-decoration: none;
    }

    .btn-print {
        margin-right: 10px;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-print:hover {
        opacity: 0.9;
        color: white;
        text-decoration: none;
    }

    .info-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-bottom: 30px;
        padding: 20px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .info-item {
        margin-bottom: 15px;
    }

    .info-label {
        font-weight: 600;
        color: #666;
        margin-bottom: 5px;
    }

    .info-value {
        color: #333;
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

    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .items-table th,
    .items-table td {
        padding: 12px;
        text-align: left;
        border: 1px solid #dee2e6;
    }

    .items-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }

    .items-table tbody tr:hover {
        background: #f8f9fa;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">View Delivery Order</h1>
        <div>
            <a href="export-do-pdf.php?do=<?php echo urlencode($do['do_number']); ?>" class="btn-print" style="background: #28a745;">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
            <a href="<?php echo $_SESSION['role'] === 'Admin' ? 'delivery-orders.php' : 'view-delivery-orders.php'; ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="info-card">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">DO Number</div>
                <div class="info-value"><?php echo htmlspecialchars($do['do_number']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">PO Number</div>
                <div class="info-value"><?php echo htmlspecialchars($do['po_number']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Supplier</div>
                <div class="info-value"><?php echo htmlspecialchars($do['supplier_name']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Delivery Date</div>
                <div class="info-value"><?php echo date('M d, Y', strtotime($do['delivery_date'])); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value">
                    <?php
                    $status = trim($do['status']);
                    $badge_class = '';
                    switch($status) {
                        case 'Pending':
                            $badge_class = 'badge bg-warning';
                            break;
                        case 'Completed':
                            $badge_class = 'badge bg-success';
                            break;
                        case 'Cancelled':
                            $badge_class = 'badge bg-danger';
                            break;
                        case 'Received':
                            $badge_class = 'badge bg-info';
                            break;
                    }
                    echo "<span class='$badge_class'>" . htmlspecialchars($status) . "</span>";
                    ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Created By</div>
                <div class="info-value"><?php echo htmlspecialchars($do['created_by']); ?></div>
            </div>
        </div>

        <div class="info-item" style="margin-top: 20px;">
            <div class="info-label">Delivery Address</div>
            <div class="info-value"><?php echo nl2br(htmlspecialchars($do['delivery_address'])); ?></div>
        </div>

        <div class="info-grid" style="margin-top: 20px;">
            <div class="info-item">
                <div class="info-label">Recipient Company</div>
                <div class="info-value"><?php echo htmlspecialchars($do['recipient_company']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Contact Person</div>
                <div class="info-value"><?php echo htmlspecialchars($do['contact_person']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Contact Number</div>
                <div class="info-value"><?php echo htmlspecialchars($do['contact_number']); ?></div>
            </div>
        </div>

        <?php if (!empty($do['vehicle_number']) || !empty($do['driver_name']) || !empty($do['driver_contact'])): ?>
            <div class="info-grid" style="margin-top: 20px;">
                <?php if (!empty($do['vehicle_number'])): ?>
                    <div class="info-item">
                        <div class="info-label">Vehicle Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($do['vehicle_number']); ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($do['driver_name'])): ?>
                    <div class="info-item">
                        <div class="info-label">Driver Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($do['driver_name']); ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($do['driver_contact'])): ?>
                    <div class="info-item">
                        <div class="info-label">Driver Contact</div>
                        <div class="info-value"><?php echo htmlspecialchars($do['driver_contact']); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($do['remarks'])): ?>
            <div class="info-item" style="margin-top: 20px;">
                <div class="info-label">Remarks</div>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($do['remarks'])); ?></div>
            </div>
        <?php endif; ?>

        <h3 style="margin-top: 30px;">Items</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Bar Code</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $items_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['inventory_item']); ?></td>
                        <td><?php echo htmlspecialchars($item['bar_code']); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $mysqli->close(); ?> 