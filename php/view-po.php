<?php
session_start();
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../admin-login.html");
    exit();
}

// Check if user has permission to access Purchase Orders
if (!in_array($_SESSION['role'], ['Admin', 'Storekeeper', 'Coordinator', 'Driver'])) {
    header("Location: admin.php");
    exit();
}

// Set view-only mode for Coordinator and Driver
$isViewOnly = ($_SESSION['role'] === 'Coordinator' || $_SESSION['role'] === 'Driver');

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get PO number from URL
if (!isset($_GET['po'])) {
    header("Location: purchase-orders.php");
    exit();
}

$po_number = $_GET['po'];

// Get PO header details
$stmt = $mysqli->prepare("SELECT * FROM purchase_orders WHERE po_number = ?");
$stmt->bind_param("s", $po_number);
$stmt->execute();
$po_result = $stmt->get_result();

if ($po_result->num_rows === 0) {
    header("Location: purchase-orders.php");
    exit();
}

$po_details = $po_result->fetch_assoc();

// Get PO items
$stmt = $mysqli->prepare("
    SELECT pi.*, i.inventory_item 
    FROM po_items pi 
    JOIN inventory i ON pi.bar_code = i.bar_code 
    WHERE pi.po_number = ?
    ORDER BY pi.id ASC
");
$stmt->bind_param("s", $po_number);
$stmt->execute();
$items_result = $stmt->get_result();

// Add debug information
if ($items_result->num_rows === 0) {
    echo '<div class="alert alert-warning">No items found for this purchase order.</div>';
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

    .po-details {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }

    .po-header {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
        margin-bottom: 30px;
    }

    .po-info {
        margin-bottom: 20px;
    }

    .po-info label {
        font-weight: 500;
        color: #666;
        display: block;
        margin-bottom: 5px;
    }

    .po-info .value {
        color: #333;
        font-size: 1.1rem;
    }

    .status-badge {
        display: inline-block;
        padding: 5px 15px;
        border-radius: 15px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .status-pending {
        background: #fff3e0;
        color: #f57c00;
    }

    .status-approved {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .status-rejected {
        background: #ffebee;
        color: #c62828;
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
        border-bottom: 1px solid #eee;
    }

    .items-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }

    .items-table tr:last-child td {
        border-bottom: none;
    }

    .grand-total {
        text-align: right;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid #eee;
    }

    .grand-total .label {
        font-weight: 600;
        color: #333;
        margin-right: 10px;
    }

    .grand-total .amount {
        font-size: 1.2rem;
        font-weight: 600;
        color: #5c1f00;
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
        transition: background-color 0.3s;
    }

    .btn-back:hover {
        background: #7a2900;
        color: white;
    }

    .remarks {
        margin-top: 20px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 5px;
    }

    .remarks label {
        font-weight: 500;
        color: #666;
        display: block;
        margin-bottom: 10px;
    }

    .remarks .text {
        color: #333;
        white-space: pre-wrap;
    }

    .btn-print {
        background: #28a745;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-right: 10px;
        transition: background-color 0.3s;
    }

    .btn-print:hover {
        background: #218838;
        color: white;
        text-decoration: none;
    }

    @media print {
        .admin-header,
        .btn-back,
        .btn-print,
        .page-header,
        .po-info .status,
        .status-badge,
        nav,
        header,
        footer,
        .admin-name,
        .url-info {
            display: none !important;
        }

        /* Hide status information */
        .po-info:has(> label:contains("Status")),
        div[class*="status"],
        .status,
        div:contains("localhost") {
            display: none !important;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 0;
            background: none;
            min-height: auto;
        }

        .po-details {
            box-shadow: none;
            padding: 0;
            margin-top: 20px;
        }

        .print-header {
            display: flex !important;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 40px;
            padding-top: 20px;
        }

        .print-header img {
            width: 200px;
            margin-bottom: 20px;
        }

        .print-header .company-address {
            font-size: 1rem;
            line-height: 1.5;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            width: 100%;
        }

        .items-table {
            margin-top: 30px;
            width: 100%;
        }

        .items-table th {
            background: none !important;
            color: black;
            border-bottom: 2px solid #000;
        }

        .items-table th,
        .items-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        .grand-total {
            margin-top: 30px;
            border-top: 2px solid #000;
            padding-top: 15px;
            text-align: right;
            font-weight: bold;
        }

        /* Prevent URLs from showing in print */
        @page {
            margin: 0.5cm;
        }

        @page :first {
            margin-bottom: 0;
        }
    }

    /* Hide elements in normal view */
    .url-info,
    .admin-name {
        display: none;
    }

    .print-header {
        display: none;
    }
</style>

<div class="container">
    <div class="print-header">
        <img src="../img/logo.png" alt="SILO Logo">
        <div class="company-address">
            <p>No.112, Jalan 28/10A,<br>
               Taman Perindustrian IKS Mukim Batu,<br>
               68100 Kuala Lumpur, Malaysia</p>
        </div>
    </div>

    <div class="page-header">
        <h1 class="page-title">View Purchase Order</h1>
        <div>
            <a href="export-po-pdf.php?po=<?php echo urlencode($po_details['po_number']); ?>" class="btn-print" style="background: #28a745;">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
            <a href="purchase-orders.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="po-details">
        <div class="po-header">
            <div>
                <div class="po-info">
                    <label>PO Number</label>
                    <div class="value"><?php echo htmlspecialchars($po_details['po_number']); ?></div>
                </div>
                <div class="po-info">
                    <label>Supplier</label>
                    <div class="value"><?php echo htmlspecialchars($po_details['supplier_name']); ?></div>
                </div>
                <div class="po-info">
                    <label>Delivery Address</label>
                    <div class="value"><?php echo nl2br(htmlspecialchars($po_details['delivery_address'])); ?></div>
                </div>
                <div class="po-info">
                    <label>Status</label>
                    <div class="value">
                        <?php
                        $status = trim($po_details['status']);
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
            </div>
            <div>
                <div class="po-info">
                    <label>Order Date</label>
                    <div class="value"><?php echo date('M d, Y', strtotime($po_details['order_date'])); ?></div>
                </div>
            </div>
        </div>

        <h3>Items</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Price (RM)</th>
                    <th>Total (RM)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $grand_total = 0;
                while ($item = $items_result->fetch_assoc()): 
                    $total = $item['quantity'] * $item['unit_price'];
                    $grand_total += $total;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['inventory_item']); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td><?php echo number_format($total, 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="grand-total">
            <span class="label">Grand Total:</span>
            <span class="amount">RM <?php echo number_format($grand_total, 2); ?></span>
        </div>

        <?php if (!empty($po_details['remarks'])): ?>
        <div class="remarks">
            <label>Remarks</label>
            <div class="text"><?php echo nl2br(htmlspecialchars($po_details['remarks'])); ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $mysqli->close(); ?> 