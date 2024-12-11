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

// Check if the user requested CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Set headers for Excel export
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Inventory_Report_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: max-age=0');

    // Output column headers
    echo "Item No.,Item Name,Bar Code,Mfg Date,Exp Date,Balance B/F,Stock In,Stock Out,Balance,Remarks,Created At\n";

    // Output rows
    while ($row = $result->fetch_assoc()) {
        echo implode(",", [
            $row['item_number'],
            $row['inventory_item'],
            $row['bar_code'],
            date('Y-m-d', strtotime($row['mfg_date'])),
            date('Y-m-d', strtotime($row['exp_date'])),
            $row['balance_brought_forward'],
            $row['stock_in'],
            $row['stock_out'],
            $row['balance'],
            str_replace(",", " ", $row['remarks']), // Replace commas to avoid breaking CSV format
            date('Y-m-d H:i:s', strtotime($row['created_at']))
        ]) . "\n";
    }

    // Close database connection
    $mysqli->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Report - <?php echo date('Y-m-d'); ?></title>
    <style>
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
            font-size: 15px;
            color: #666;
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
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background-color: #f4f4f4;
            font-weight: bold;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #5c1f00;
            font-size: 15px;
            color: #666;
            page-break-inside: avoid;
        }
        .download-button {
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
    </style>
</head>
<body>

    <div class="header">
        <img src="../img/logo.png" alt="SILO Logo" class="logo">
        <h1 class="report-title">Inventory Excel Report</h1>
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

    <h1>Inventory Report</h1>
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
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['item_number']); ?></td>
                <td><?php echo htmlspecialchars($row['inventory_item']); ?></td>
                <td><?php echo htmlspecialchars($row['bar_code']); ?></td>
                <td><?php echo date('Y-m-d', strtotime($row['mfg_date'])); ?></td>
                <td><?php echo date('Y-m-d', strtotime($row['exp_date'])); ?></td>
                <td><?php echo htmlspecialchars($row['balance_brought_forward']); ?></td>
                <td><?php echo htmlspecialchars($row['stock_in']); ?></td>
                <td><?php echo htmlspecialchars($row['stock_out']); ?></td>
                <td><?php echo htmlspecialchars($row['balance']); ?></td>
                <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                <td><?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <form action="" method="get">
        <button class ="download-button" type="submit" name="export" value="csv">Export to CSV / Download</button>
    </form>
    <div class="footer">
        <p>© <?php echo date('Y'); ?> SILO (M) Sdn. Bhd. All Rights Reserved</p>
        <p>This report is system generated. For any queries, please contact the administrator.</p>
    </div>
</body>
</html>

<?php
// Close database connection
$mysqli->close();
?>
