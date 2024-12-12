<?php
session_start();
require_once 'functions.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin-login.html");
    exit();
}

$mysqli = new mysqli("localhost", "root", "", "fyp");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$po_number = isset($_GET['po_number']) ? $_GET['po_number'] : '';

if (empty($po_number)) {
    echo "No PO number provided";
    exit();
}

// Join with inventory table to get item names
$query = "SELECT p.*, i.inventory_item 
          FROM po_items p 
          LEFT JOIN inventory i ON p.bar_code = i.bar_code 
          WHERE p.po_number = ? 
          ORDER BY p.id ASC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $po_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<table class="items-table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Bar Code</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>';
    
    while ($row = $result->fetch_assoc()) {
        echo '<tr>
                <td>' . htmlspecialchars($row['inventory_item']) . '</td>
                <td>' . htmlspecialchars($row['bar_code']) . '</td>
                <td>' . htmlspecialchars($row['quantity']) . '</td>
              </tr>';
    }
    
    echo '</tbody></table>';
} else {
    echo '<p class="error-message">No items found for this PO number.</p>';
}

$stmt->close();
$mysqli->close();
?> 