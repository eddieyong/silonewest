<?php
session_start();
require_once 'permissions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['username']) || !hasStockPermission($_SESSION['role'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'You do not have permission to perform this action']);
    exit();
}

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_GET['action'] ?? '';
    
    if (!empty($action) && isset($_POST['items']) && is_array($_POST['items'])) {
        foreach ($_POST['items'] as $item) {
            if (empty($item['bar_code']) || empty($item['amount'])) {
                continue;
            }

            $bar_code = $item['bar_code'];
            $amount = intval($item['amount']);

            // Get current item details
            $stmt = $mysqli->prepare("SELECT * FROM inventory WHERE bar_code = ?");
            $stmt->bind_param("s", $bar_code);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $_SESSION['error_msg'] = "Item with barcode $bar_code not found.";
                continue;
            }

            $current_item = $result->fetch_assoc();
            
            // Calculate new values
            if ($action === 'stock_in') {
                $new_stock_in = $current_item['stock_in'] + $amount;
                $new_balance = $current_item['balance'] + $amount;
                
                // Update the inventory
                $stmt = $mysqli->prepare("UPDATE inventory SET stock_in = ?, balance = ?, updated_at = CURRENT_TIMESTAMP WHERE bar_code = ?");
                $stmt->bind_param("iis", $new_stock_in, $new_balance, $bar_code);
                
                if ($stmt->execute()) {
                    // Log the activity
                    $activity_type = 'stock_in';
                    $item_name = $current_item['inventory_item'];
                    $activity_description = "Stock In: Added $amount units of $item_name (Barcode: $bar_code)";
                    
                    $stmt = $mysqli->prepare("INSERT INTO activities (activity_type, description, created_by) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $activity_type, $activity_description, $_SESSION['username']);
                    $stmt->execute();

                    // Check for low stock and create alert if necessary
                    if ($new_balance < 10) {
                        $alert_description = "Low stock alert: $item_name has only $new_balance units remaining";
                        $alert_type = 'stock_alert';
                        $stmt = $mysqli->prepare("INSERT INTO activities (activity_type, description, created_by) VALUES (?, ?, ?)");
                        $stmt->bind_param("sss", $alert_type, $alert_description, $_SESSION['username']);
                        $stmt->execute();
                    }

                    $_SESSION['success_msg'] = "Stock in successful!";
                } else {
                    $_SESSION['error_msg'] = "Error updating stock in.";
                }
            } 
            elseif ($action === 'stock_out') {
                // Check if there's enough stock
                if ($current_item['balance'] < $amount) {
                    $_SESSION['error_msg'] = "Not enough stock for item $bar_code. Current balance: {$current_item['balance']}";
                    continue;
                }

                $new_stock_out = $current_item['stock_out'] + $amount;
                $new_balance = $current_item['balance'] - $amount;
                
                // Update the inventory
                $stmt = $mysqli->prepare("UPDATE inventory SET stock_out = ?, balance = ?, updated_at = CURRENT_TIMESTAMP WHERE bar_code = ?");
                $stmt->bind_param("iis", $new_stock_out, $new_balance, $bar_code);
                
                if ($stmt->execute()) {
                    // Log the activity
                    $activity_type = 'stock_out';
                    $item_name = $current_item['inventory_item'];
                    $activity_description = "Stock Out: Removed $amount units of $item_name (Barcode: $bar_code)";
                    
                    $stmt = $mysqli->prepare("INSERT INTO activities (activity_type, description, created_by) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $activity_type, $activity_description, $_SESSION['username']);
                    $stmt->execute();

                    // Check for low stock and create alert if necessary
                    if ($new_balance < 10) {
                        $alert_description = "Low stock alert: $item_name has only $new_balance units remaining";
                        $alert_type = 'stock_alert';
                        $stmt = $mysqli->prepare("INSERT INTO activities (activity_type, description, created_by) VALUES (?, ?, ?)");
                        $stmt->bind_param("sss", $alert_type, $alert_description, $_SESSION['username']);
                        $stmt->execute();
                    }

                    $_SESSION['success_msg'] = "Stock out successful!";
                } else {
                    $_SESSION['error_msg'] = "Error updating stock out.";
                }
            }
        }
    }
}

$mysqli->close();
header("Location: inventory.php");
exit();
?> 