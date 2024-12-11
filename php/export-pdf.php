<?php
// Start the session
session_start();

// Check if the user is logged in and has the 'Admin' role
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

// Calculate totals
$totals_query = "SELECT 
    SUM(balance_brought_forward) as total_bf,
    SUM(stock_in) as total_in,
    SUM(stock_out) as total_out,
    SUM(balance) as total_balance
FROM inventory";
$totals_result = $mysqli->query($totals_query);
$totals = $totals_result->fetch_assoc();

// Fetch all inventory items
$result = $mysqli->query("SELECT item_number, inventory_item, bar_code, mfg_date, exp_date, balance_brought_forward, stock_in, stock_out, balance, remarks, created_at FROM inventory ORDER BY item_number ASC");

if (!$result) {
    die("Query failed: " . $mysqli->error);
}

// Set headers for PDF
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Report - <?php echo date('Y-m-d'); ?></title>
    <style>
        @page {
            size: landscape;
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
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #5c1f00;
        }
        .logo {
            max-width: 120px;
            margin-bottom: 15px;
        }
        .report-title {
            color: #5c1f00;
            font-size: 24px;
            margin: 10px 0;
        }
        .report-info {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            font-size: 12px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
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
            white-space: nowrap;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .summary-section {
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            page-break-inside: avoid;
        }
        .summary-title {
            color: #5c1f00;
            font-size: 14px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .summary-item {
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .summary-label {
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
        }
        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #5c1f00;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
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
        @media print {
            .print-button {
                display: none;
            }
            body {
                padding: 0;
            }
            .header {
                position: fixed;
                top: 0;
                width: 100%;
                background: white;
            }
            .footer {
                position: fixed;
                bottom: 0;
                width: 100%;
                background: white;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            thead {
                display: table-header-group;
            }
            tfoot {
                display: table-footer-group;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">
        <i class="fas fa-print"></i> Print Report
    </button>

    <div class="header">
        <img src="../img/logo.png" alt="SILO Logo" class="logo">
        <h1 class="report-title">Inventory Report</h1>
        <div class="report-info">
            <span>Generated by: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <span>Date: <?php echo date('Y-m-d H:i:s'); ?></span>
        </div>
    </div>

    <div class="summary-section">
        <h2 class="summary-title">Inventory Summary</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Total Balance B/F</div>
                <div class="summary-value"><?php echo number_format($totals['total_bf']); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Stock In</div>
                <div class="summary-value"><?php echo number_format($totals['total_in']); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Stock Out</div>
                <div class="summary-value"><?php echo number_format($totals['total_out']); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Current Total Balance</div>
                <div class="summary-value"><?php echo number_format($totals['total_balance']); ?></div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item No.</th>
                <th>Item Name</th>
                <th>Bar Code</th>
                <th>Mfg Date</th>
                <th>Exp Date</th>
                <th>Balance B/F</th>
                <th>Stock In</th>
                <th>Stock Out</th>
                <th>Balance</th>
                <th>Remarks</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $row_number = 1;
            while($row = $result->fetch_assoc()): 
                $mfg_date = date('Y-m-d', strtotime($row['mfg_date']));
                $exp_date = date('Y-m-d', strtotime($row['exp_date']));
                $created_at = date('Y-m-d H:i', strtotime($row['created_at']));
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['item_number']); ?></td>
                <td><?php echo htmlspecialchars($row['inventory_item']); ?></td>
                <td><?php echo htmlspecialchars($row['bar_code']); ?></td>
                <td><?php echo $mfg_date; ?></td>
                <td><?php echo $exp_date; ?></td>
                <td style="text-align: right;"><?php echo number_format($row['balance_brought_forward']); ?></td>
                <td style="text-align: right;"><?php echo number_format($row['stock_in']); ?></td>
                <td style="text-align: right;"><?php echo number_format($row['stock_out']); ?></td>
                <td style="text-align: right;"><?php echo number_format($row['balance']); ?></td>
                <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                <td><?php echo $created_at; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>© <?php echo date('Y'); ?> SILO (M) Sdn. Bhd. All Rights Reserved</p>
        <p>This report is system generated. For any queries, please contact the administrator.</p>
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