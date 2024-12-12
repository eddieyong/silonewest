<?php
// Start the session
session_start();

// Check if the user is logged in and has the 'Admin' role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin-login.html");
    exit();
}

// Check if PO number is provided
if (!isset($_GET['po'])) {
    header("Location: purchase-orders.php");
    exit();
}

$po_number = $_GET['po'];

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get PO details
$query = "SELECT * FROM purchase_orders WHERE po_number = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $po_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: purchase-orders.php");
    exit();
}

$po = $result->fetch_assoc();

// Get PO items
$items_query = "SELECT pi.*, i.inventory_item 
                FROM po_items pi 
                LEFT JOIN inventory i ON pi.bar_code = i.bar_code 
                WHERE pi.po_number = ?";
$stmt = $mysqli->prepare($items_query);
$stmt->bind_param("s", $po_number);
$stmt->execute();
$items_result = $stmt->get_result();

// Calculate total amount
$total_amount = 0;
$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
    $total_amount += $item['quantity'] * $item['unit_price'];
}

// Set headers for PDF
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - <?php echo htmlspecialchars($po_number); ?></title>
    <style>
        @page {
            size: portrait;
            margin: 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #fff;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #5c1f00;
        }
        .logo {
            max-width: 150px;
            height: auto;
            margin-bottom: 10px;
        }
        .report-title {
            color: #5c1f00;
            font-size: 24px;
            margin: 10px 0;
            font-weight: bold;
        }
        .report-info {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 12px;
            color: #666;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 12px;
            margin-bottom: 3px;
        }
        .info-value {
            color: #333;
            font-size: 14px;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge.completed {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .badge.pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .badge.cancelled {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #5c1f00;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 2px solid #5c1f00;
            font-size: 11px;
            color: #666;
            page-break-inside: avoid;
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #5c1f00;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
        }
        .total-section {
            margin-top: 20px;
            text-align: right;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .total-label {
            font-weight: bold;
            font-size: 14px;
            color: #5c1f00;
        }
        .total-amount {
            font-size: 18px;
            font-weight: bold;
            color: #5c1f00;
            margin-left: 10px;
        }
        @media print {
            .print-button {
                display: none;
            }
            body {
                padding: 0;
            }
            .badge {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .total-section {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            th {
                background-color: #5c1f00 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color: white !important;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">
        <i class="fas fa-print"></i> Print PO
    </button>

    <div class="header">
        <img src="../img/logo.png" alt="SILO Logo" class="logo">
        <h1 class="report-title">Purchase Order</h1>
        <div class="report-info">
            <span>Generated by: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <span>Date: <?php echo date('Y-m-d H:i:s'); ?></span>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">PO Number</div>
            <div class="info-value"><?php echo htmlspecialchars($po['po_number']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Order Date</div>
            <div class="info-value"><?php echo date('M d, Y', strtotime($po['order_date'])); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Supplier Name</div>
            <div class="info-value"><?php echo htmlspecialchars($po['supplier_name']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Created By</div>
            <div class="info-value"><?php echo htmlspecialchars($po['created_by']); ?></div>
        </div>
    </div>

    <?php if (!empty($po['remarks'])): ?>
    <div class="info-item" style="margin-top: 20px;">
        <div class="info-label">Remarks</div>
        <div class="info-value"><?php echo nl2br(htmlspecialchars($po['remarks'])); ?></div>
    </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Bar Code</th>
                <th>Quantity</th>
                <th>Unit Price (RM)</th>
                <th>Total Price (RM)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['inventory_item']); ?></td>
                <td style="text-align: center;"><?php echo htmlspecialchars($item['bar_code']); ?></td>
                <td style="text-align: right;"><?php echo number_format($item['quantity']); ?></td>
                <td style="text-align: right;"><?php echo number_format($item['unit_price'], 2); ?></td>
                <td style="text-align: right;"><?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-section">
        <span class="total-label">Total Amount:</span>
        <span class="total-amount">RM <?php echo number_format($total_amount, 2); ?></span>
    </div>

    <div class="footer">
        <p>© <?php echo date('Y'); ?> SILO (M) Sdn. Bhd. All Rights Reserved</p>
        <p>This document is system generated. For any queries, please contact the administrator.</p>
    </div>

    <script>
        window.onload = function() {
            // Add a small delay before opening print dialog
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>

<?php $mysqli->close(); ?> 