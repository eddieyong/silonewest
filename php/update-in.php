<?php
// Start the session at the beginning of the script
session_start();

// Database connection
$servername = "localhost";  // Change this if necessary
$username = "root";         // Change this if necessary
$password = "";             // Change this if necessary
$dbname = "fyp";  // Change this to your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// update-inventory.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Access form data
    $barcode = $_POST['barcode'];  // Get the barcode entered in the form
    $action = $_POST['action'];    // Get the action value (which is "stock_in" in this case)

    // Function to process barcode input
    function processBarcode($barcode, $type) {
        // Determine the increment or decrement value based on barcode prefix and type
        $incrementValue = (strpos($barcode, '9') === 0) ? 12 : 1;

        // If the action is stock-out, we need to decrement the value
        if ($type === 'stock_out') {
            $incrementValue = -$incrementValue;
        }

        return $incrementValue;
    }

    // Capture barcode input (ensure this matches your form's input field)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['barcode']) && isset($_POST['action'])) {
        $barcode = $_POST['barcode'];
        $action = $_POST['action']; // 'stock_in' or 'stock_out'

        // Fetch current stock_in, stock_out, and balance fields for the barcode
        $query = "SELECT stock_in, stock_out, balance FROM inventory WHERE bar_code = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update inventory count
            $row = $result->fetch_assoc();
            $increment = processBarcode($barcode, $action);

            // Update stock_in, stock_out, and balance based on the action
            if ($action === 'stock_in') {
                $newStockIn = $row['stock_in'] + $increment;
                $newBalance = $row['balance'] + $increment;
                $updateQuery = "UPDATE inventory SET stock_in = ?, balance = ? WHERE bar_code = ?";
            } else { // stock_out
                $newStockOut = $row['stock_out'] + abs($increment); // Ensure positive value for stock_out
                $newBalance = $row['balance'] + $increment;
                $updateQuery = "UPDATE inventory SET stock_out = ?, balance = ? WHERE bar_code = ?";
            }

            $updateStmt = $conn->prepare($updateQuery);

            if ($action === 'stock_in') {
                $updateStmt->bind_param("iis", $newStockIn, $newBalance, $barcode);
            } else { // stock_out
                $updateStmt->bind_param("iis", $newStockOut, $newBalance, $barcode);
            }

            if ($updateStmt->execute()) {
                // Set session variable with success message
                $_SESSION['message'] = "Inventory updated successfully!";
                $activityDescription = "$action for barcode: $barcode. Stock In: $newStockIn, Stock Out: $newStockOut, Balance: $newBalance";
                
                // Log the activity in the activities table
                $activityQuery = "INSERT INTO activities (activity_type, description) VALUES ('inventory', ?)";
                $activityStmt = $conn->prepare($activityQuery);
                $activityStmt->bind_param("s", $activityDescription);
                $activityStmt->execute();
                $activityStmt->close();
            } else {
                // Set session variable with error message
                $_SESSION['message'] = "Error updating inventory: " . $conn->error;
            }
        } else {
            // Set session variable with barcode not found message
            $_SESSION['message'] = "Barcode not found in inventory!";
        }

        $stmt->close();
        $conn->close();

        // Redirect to inventory.php after setting the session message
        header("Location: inventory.php");
        exit();
    }
}
?>
