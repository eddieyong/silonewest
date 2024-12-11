<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$mysqli = new mysqli("localhost", "root", "", "fyp");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Add delivery_address column if it doesn't exist
$check_column = $mysqli->query("SHOW COLUMNS FROM purchase_orders LIKE 'delivery_address'");
if ($check_column->num_rows === 0) {
    $add_column = "ALTER TABLE purchase_orders ADD COLUMN delivery_address TEXT AFTER supplier_name";
    if ($mysqli->query($add_column)) {
        echo "Added delivery_address column successfully.\n";
    } else {
        echo "Error adding delivery_address column: " . $mysqli->error . "\n";
    }
}

// First, update existing statuses to match new enum values
$mysqli->query("UPDATE purchase_orders SET status = 'Completed' WHERE status = 'Approved'");
$mysqli->query("UPDATE purchase_orders SET status = 'Received' WHERE status = 'Delivered'");

// Modify the status column
$alter_status = "ALTER TABLE purchase_orders 
    MODIFY COLUMN status ENUM('Pending', 'Completed', 'Cancelled', 'Received') NOT NULL DEFAULT 'Pending'";

if ($mysqli->query($alter_status)) {
    echo "Table structure updated successfully.\n";
} else {
    echo "Error updating table structure: " . $mysqli->error . "\n";
}

// Show the current structure
$result = $mysqli->query("SHOW CREATE TABLE purchase_orders");
if ($result) {
    $row = $result->fetch_assoc();
    echo "\nUpdated table structure:\n";
    echo $row['Create Table'] . "\n";
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