<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fyp";

// Database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_GET['action']) ? $_GET['action'] : null; // 'stock_in' or 'stock_out'
    $items = $_POST['items']; // Array of items: ['barcode' => '...', 'amount' => ...]

    if (!empty($items) && ($action === 'stock_in' || $action === 'stock_out')) {
        foreach ($items as $item) {
            $barcode = trim($item['barcode']);
            $amount = intval($item['amount']);

            if (!empty($barcode) && $amount > 0) {
                // Check if barcode exists in inventory
                $query = "SELECT inventory_item, balance FROM inventory WHERE bar_code = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $barcode);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $inventory = $result->fetch_assoc();
                    $inventoryItem = $inventory['inventory_item'];
                    $newBalance = $inventory['balance'];

                    // Update stock and balance based on action
                    if ($action === 'stock_in') {
                        $newBalance += $amount;
                        $updateQuery = "UPDATE inventory SET stock_in = stock_in + ?, balance = ? WHERE bar_code = ?";
                    } elseif ($action === 'stock_out') {
                        $newBalance -= $amount;
                        if ($newBalance < 0) {
                            $_SESSION['message'] .= "Insufficient stock for barcode: $barcode.<br>";
                            continue; // Skip to the next item
                        }
                        $updateQuery = "UPDATE inventory SET stock_out = stock_out + ?, balance = ? WHERE bar_code = ?";
                    }

                    // Execute update query
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bind_param("iis", $amount, $newBalance, $barcode);

                    if ($updateStmt->execute()) {
                        // Log activity in the activities table
                        $activityType = ($action === 'stock_in') ? "Stock In" : "Stock Out";
                        $activityDescription = ucfirst(str_replace('_', ' ', $action)) . " : $inventoryItem,Barcode: $barcode | Amount: $amount items.";

                        $logQuery = "INSERT INTO activities (activity_type, description, created_at) VALUES (?, ?, NOW())";
                        $logStmt = $conn->prepare($logQuery);
                        if ($logStmt) {
                            $logStmt->bind_param("ss", $activityType, $activityDescription);
                            if (!$logStmt->execute()) {
                                $_SESSION['message'] .= "Error logging activity for barcode: $barcode. Debug: " . $logStmt->error . "<br>";
                            }
                            $logStmt->close();
                        } else {
                            $_SESSION['message'] .= "Error preparing activity log statement: " . $conn->error . "<br>";
                        }
                    } else {
                        $_SESSION['message'] .= "Error updating inventory for barcode: $barcode.<br>";
                    }

                    $updateStmt->close();
                } else {
                    $_SESSION['message'] .= "Barcode $barcode not found in inventory!<br>";
                }

                $stmt->close();
            } else {
                $_SESSION['message'] .= "Invalid input for barcode: $barcode. Ensure all fields are correctly filled.<br>";
            }
        }

        $conn->close();

        // Redirect back to inventory page with feedback
        header("Location: inventory.php");
        exit();
    } else {
        $_SESSION['message'] = "Invalid input or unsupported action!";
        header("Location: inventory.php");
        exit();
    }
} else {
    $_SESSION['message'] = "Invalid request method.";
    header("Location: inventory.php");
    exit();
}
?>
