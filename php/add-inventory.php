<?php
session_start();

// Check if the user is logged in and has the 'Admin' role
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['Admin', 'Storekeeper'])) {
    header("Location: ../admin-login.html");

    exit();
}

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

require_once 'functions.php'; // Add this line to include functions.php

$message = '';
$messageType = '';

// Generate default ascending item number
$result = $mysqli->query("SELECT MAX(CAST(item_number AS UNSIGNED)) AS max_item_number FROM inventory");
$row = $result->fetch_assoc();
$default_item_number = $row['max_item_number'] ? $row['max_item_number'] + 1 : 1; // Start from 1 if no items exist

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_number = trim($_POST['item_number']);
    $inventory_item = trim($_POST['inventory_item']);
    $bar_code = trim($_POST['bar_code']);
    $mfg_date = trim($_POST['mfg_date']);
    $exp_date = trim($_POST['exp_date']);
    $balance_brought_forward = intval($_POST['balance_brought_forward']);
    $stock_in = intval($_POST['stock_in']); // Get stock_in from form
    $stock_out = intval($_POST['stock_out']);
    $remarks = trim($_POST['remarks']);
    
    // Validate unique item number
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM inventory WHERE item_number = ?");
    $stmt->bind_param("s", $item_number);
    $stmt->execute();
    $stmt->bind_result($count_item_number);
    $stmt->fetch();
    $stmt->close();

    // Validate unique barcode
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM inventory WHERE bar_code = ?");
    $stmt->bind_param("s", $bar_code);
    $stmt->execute();
    $stmt->bind_result($count_bar_code);
    $stmt->fetch();
    $stmt->close();

    if ($count_item_number > 0) {
        $message = "The item number '$item_number' already exists. Please choose another.";
        $messageType = "error";
    } elseif (!empty($bar_code) && $count_bar_code > 0) {
        $message = "The barcode '$bar_code' already exists. Please choose another.";
        $messageType = "error";
    } else {
        // Calculate balance
        $balance = $balance_brought_forward + $stock_in - $stock_out;

        // Start transaction
        $mysqli->begin_transaction();

        try {
            // Insert new inventory item
            $stmt = $mysqli->prepare("INSERT INTO inventory (item_number, inventory_item, bar_code, mfg_date, exp_date, balance_brought_forward, stock_in, stock_out, balance, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssiiiis", 
                $item_number,
                $inventory_item,
                $bar_code,
                $mfg_date,
                $exp_date,
                $balance_brought_forward,
                $stock_in,
                $stock_out,
                $balance,
                $remarks
            );

            if ($stmt->execute()) {
                // Log the activity
                if ($stock_in > 0) {
                    $description = "Added new item '{$inventory_item}' with initial stock of {$stock_in} units";
                    logActivity($mysqli, 'stock_in', $description);
                } else {
                    $description = "Added new item '{$inventory_item}' to inventory";
                    logActivity($mysqli, 'inventory', $description);
                }
                
                $mysqli->commit();
                header("Location: inventory.php");
                exit();
            } else {
                throw new Exception("Error adding inventory item: " . $mysqli->error);
            }
        } catch (Exception $e) {
            $mysqli->rollback();
            $message = $e->getMessage();
            $messageType = "error";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Inventory Item - SILO</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Page Specific Styles */
        .container {
            padding: 20px 30px;
            background: #f8f9fa;
            min-height: calc(100vh - 72px);
        }

        .page-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .page-title {
            margin: 0;
            font-size: 1.5rem;
            color: #333;
        }

        .inventory-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #5c1f00;
            outline: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .btn-submit {
            background: #5c1f00;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background: #7a2900;
        }

        .btn-cancel {
            background: #f8f9fa;
            color: #333;
            padding: 12px 24px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }

        .btn-cancel:hover {
            background: #e9ecef;
        }

        .form-actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .message.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        .readonly-input {
            background-color: #f2f2f2;
            color: #888;
            cursor: not-allowed;
        }

        .read-only-note {
            color: #666;
            font-size: 0.9rem;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body>
    <?php include 'admin-header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Add New Inventory Item</h1>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="inventory-form">
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-row">
        <div class="form-group">
             <label for="item_number">Item Number *</label>
    <input type="text"  id="item_number"   name="item_number" value="<?php echo $default_item_number; ?>" readonly class="readonly-input" title="This field cannot be edited">
    <small class="read-only-note">This field cannot be edited.</small>
</div>


            <div class="form-group">
                <label for="inventory_item">Item Name *</label>
                <input type="text" id="inventory_item" name="inventory_item" required>
            </div>
        </div>

        <div class="form-group">
            <label for="bar_code">Bar Code</label>
            <input type="text" id="bar_code" name="bar_code">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="mfg_date">Manufacturing Date</label>
                <input type="date" id="mfg_date" name="mfg_date">
            </div>

            <div class="form-group">
                <label for="exp_date">Expiry Date</label>
                <input type="date" id="exp_date" name="exp_date">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="balance_brought_forward">Balance B/F *</label>
                <input type="number" id="balance_brought_forward" name="balance_brought_forward" required min="0" value="0"  readonly class="readonly-input" title="This field cannot be edited">
                <small class="read-only-note">This field cannot be edited.</small>
            </div>

            <div class="form-group">
                <label for="stock_in">Stock In</label>
                <input type="number" id="stock_in" name="stock_in" value="0" >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="stock_out">Stock Out</label>
                <input type="number" id="stock_out" name="stock_out" value="0"  readonly class="readonly-input" title="This field cannot be edited">
                <small class="read-only-note">This field cannot be edited.</small>
            </div>

            <div class="form-group">
                <label for="remarks">Description</label>
                <textarea id="remarks" name="remarks" rows="3"></textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="inventory.php" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn-submit">Add Item</button>
        </div>
    </form>
</div>



    <script>
    </script>
</body>
</html>
<?php $mysqli->close(); ?> 