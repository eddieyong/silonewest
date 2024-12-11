<?php
$mysqli = new mysqli('localhost', 'root', '', 'fyp');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$sql = "ALTER TABLE purchase_orders ADD COLUMN delivery_address TEXT AFTER supplier_name";

if ($mysqli->query($sql)) {
    echo "Added delivery_address column successfully.\n";
} else {
    echo "Error: " . $mysqli->error . "\n";
}

// Show the current structure
$result = $mysqli->query("SHOW CREATE TABLE purchase_orders");
if ($result) {
    $row = $result->fetch_assoc();
    echo "\nUpdated table structure:\n";
    echo $row['Create Table'] . "\n";
}

$mysqli->close();
?> 