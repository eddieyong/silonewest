<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$mysqli = new mysqli("localhost", "root", "", "fyp");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Create purchase_orders table if it doesn't exist
$create_po_table = "CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(20) UNIQUE NOT NULL,
    supplier_name VARCHAR(100) NOT NULL,
    order_date DATE NOT NULL,
    status ENUM('Pending', 'Completed', 'Cancelled', 'Received') NOT NULL DEFAULT 'Pending',
    remarks TEXT,
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($mysqli->query($create_po_table)) {
    echo "Purchase orders table created or already exists.\n";
} else {
    echo "Error creating purchase orders table: " . $mysqli->error . "\n";
}

// Create po_items table if it doesn't exist
$create_items_table = "CREATE TABLE IF NOT EXISTS po_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(20) NOT NULL,
    bar_code VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (po_number) REFERENCES purchase_orders(po_number) ON DELETE CASCADE
)";

if ($mysqli->query($create_items_table)) {
    echo "PO items table created or already exists.\n";
} else {
    echo "Error creating PO items table: " . $mysqli->error . "\n";
}

// Update any NULL or empty statuses to 'Pending'
$update_null_statuses = "UPDATE purchase_orders SET status = 'Pending' WHERE status IS NULL OR status = ''";
if ($mysqli->query($update_null_statuses)) {
    echo "\nUpdated any NULL or empty statuses to 'Pending'.\n";
    if ($mysqli->affected_rows > 0) {
        echo "Fixed {$mysqli->affected_rows} records.\n";
    }
} else {
    echo "\nError updating NULL statuses: " . $mysqli->error . "\n";
}

// Check if tables exist and show their structure
$tables = ['purchase_orders', 'po_items'];
foreach ($tables as $table) {
    $result = $mysqli->query("SHOW CREATE TABLE $table");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "\nTable structure for $table:\n";
        echo $row['Create Table'] . "\n";
    } else {
        echo "\nError getting structure for $table: " . $mysqli->error . "\n";
    }
}

// Show sample data
$result = $mysqli->query("SELECT po_number, status FROM purchase_orders LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "\nSample PO statuses:\n";
    while ($row = $result->fetch_assoc()) {
        echo "PO: {$row['po_number']}, Status: {$row['status']}\n";
    }
} else {
    echo "\nNo purchase orders found.\n";
}

$mysqli->close();
?> 