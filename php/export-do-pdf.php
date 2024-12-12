<?php
// Start the session
session_start();

// Check if the user is logged in and has the 'Admin' role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin-login.html");
    exit();
}

// Check if DO number is provided
if (!isset($_GET['do'])) {
    header("Location: delivery-orders.php");
    exit();
}

$do_number = $_GET['do'];

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

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

// Set headers for PDF
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="robots" content="noindex, nofollow">
    <title>Delivery Order - <?php echo htmlspecialchars($do_number); ?></title>
    <style>
        @page {
            size: portrait;
            margin: 15mm;
        }
        
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #333;
            font-family: Arial, sans-serif;
        }

        @media print {
            body {
                visibility: hidden;
            }
            .print-container {
                visibility: visible;
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }

        /* Hide URL in print */
        @page {
            size: portrait;
            margin: 15mm;
        }

        @page :blank {
            margin: 0;
        }

        @media print {
            body {
                visibility: hidden;
            }
            .container {
                visibility: visible;
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .header, .info-grid, .address-section, table, .footer {
                visibility: visible;
            }
            body::after {
                content: none !important;
            }
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #fff;
            color: #333;
        }

        /* Hide URL display */
        body::after {
            display: none !important;
        }
        
        @media print {
            /* Hide URL and other browser elements */
            body::after,
            .url-display,
            .browser-info,
            .page-info {
                display: none !important;
            }
            
            /* Force hide any generated content */
            *::after {
                display: none !important;
            }
            
            /* Additional URL hiding */
            @page {
                size: portrait;
                margin: 15mm;
            }
            
            @page :first {
                margin-top: 0;
            }
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
        .address-section {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
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
            .address-section {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <button class="print-button" onclick="window.print()">
            <i class="fas fa-print"></i> Print DO
        </button>

        <div class="header">
            <img src="../img/logo.png" alt="SILO Logo" class="logo">
            <h1 class="report-title">Delivery Order</h1>
            <div class="report-info">
                <span>Generated by: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <span>Date: <?php echo date('Y-m-d H:i:s'); ?></span>
            </div>
        </div>

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
                <div class="info-label">Created By</div>
                <div class="info-value"><?php echo htmlspecialchars($do['created_by']); ?></div>
            </div>
        </div>

        <div class="address-section">
            <div class="info-item">
                <div class="info-label">Delivery Address</div>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($do['delivery_address'])); ?></div>
            </div>

            <div class="info-grid" style="margin-top: 15px;">
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
        </div>

        <?php if (!empty($do['vehicle_number']) || !empty($do['driver_name']) || !empty($do['driver_contact'])): ?>
        <div class="info-grid">
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

        <table>
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
                    <td style="text-align: center;"><?php echo htmlspecialchars($item['bar_code']); ?></td>
                    <td style="text-align: right;"><?php echo number_format($item['quantity']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

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
    </div>
</body>
</html>

<?php $mysqli->close(); ?> 