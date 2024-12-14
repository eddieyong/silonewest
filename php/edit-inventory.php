<?php
session_start();
require_once 'functions.php';

// Check if user is logged in and has appropriate role
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

$message = '';
$messageType = '';

// Get bar_code from URL
if (!isset($_GET['bar_code'])) {
    header("Location: inventory.php");
    exit();
}

$bar_code = $_GET['bar_code'];

// Fetch existing item data before processing any updates
$stmt = $mysqli->prepare("SELECT * FROM inventory WHERE bar_code = ?");
$stmt->bind_param("s", $bar_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: inventory.php");
    exit();
}

$item = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_number = trim($_POST['item_number']);
    $inventory_item = trim($_POST['inventory_item']);
    $new_bar_code = trim($_POST['bar_code']);
    $mfg_date = trim($_POST['mfg_date']);
    $exp_date = trim($_POST['exp_date']);
    $balance_brought_forward = intval($_POST['balance_brought_forward']);
    $stock_in = intval($_POST['stock_in']);
    $stock_out = intval($_POST['stock_out']);
    $balance = intval($_POST['balance']);
    $remarks = trim($_POST['remarks']);

    // Update inventory item
    $stmt = $mysqli->prepare("UPDATE inventory SET 
        item_number = ?, 
        inventory_item = ?, 
        bar_code = ?, 
        mfg_date = ?, 
        exp_date = ?, 
        balance_brought_forward = ?, 
        stock_in = ?, 
        stock_out = ?, 
        balance = ?, 
        remarks = ?,
        updated_at = CURRENT_TIMESTAMP
        WHERE bar_code = ?");

    $stmt->bind_param("sssssiiiiss", 
        $item_number,
        $inventory_item,
        $new_bar_code,
        $mfg_date,
        $exp_date,
        $balance_brought_forward,
        $stock_in,
        $stock_out,
        $balance,
        $remarks,
        $bar_code
    );

    if ($stmt->execute()) {
        // Log the activity
        $activity_description = "Updated inventory item: $inventory_item (Barcode: $bar_code)";
        $stmt = $mysqli->prepare("INSERT INTO activities (activity_type, description, created_by) VALUES ('inventory_update', ?, ?)");
        $stmt->bind_param("ss", $activity_description, $_SESSION['username']);
        $stmt->execute();

        $_SESSION['success_msg'] = "Item updated successfully!";
        header("Location: inventory.php");
        exit();
    } else {
        $error_message = "Error updating item: " . $mysqli->error;
    }
    $stmt->close();
}

include 'admin-header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit Inventory Item</h1>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="inventory-form">
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="item_number">Item Number</label>
                    <input type="text" id="item_number" name="item_number" value="<?php echo htmlspecialchars($item['item_number']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="inventory_item">Item Name</label>
                    <input type="text" id="inventory_item" name="inventory_item" value="<?php echo htmlspecialchars($item['inventory_item']); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="bar_code">Bar Code</label>
                    <input type="text" id="bar_code" name="bar_code" value="<?php echo htmlspecialchars($item['bar_code']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="mfg_date">Manufacturing Date</label>
                    <input type="date" id="mfg_date" name="mfg_date" value="<?php echo $item['mfg_date']; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="exp_date">Expiry Date</label>
                    <input type="date" id="exp_date" name="exp_date" value="<?php echo $item['exp_date']; ?>">
                </div>
                <div class="form-group">
                    <label for="balance_brought_forward">Balance Brought Forward</label>
                    <input type="number" id="balance_brought_forward" name="balance_brought_forward" value="<?php echo $item['balance_brought_forward']; ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="stock_in">Stock In</label>
                    <input type="number" id="stock_in" name="stock_in" value="<?php echo $item['stock_in']; ?>" readonly class="readonly-input" title="This field cannot be edited">
                    <small class="read-only-note">Use Stock In/Out function to modify stock levels.</small>
                </div>
                <div class="form-group">
                    <label for="stock_out">Stock Out</label>
                    <input type="number" id="stock_out" name="stock_out" value="<?php echo $item['stock_out']; ?>" readonly class="readonly-input" title="This field cannot be edited">
                    <small class="read-only-note">Use Stock In/Out function to modify stock levels.</small>
                </div>
            </div>

            <div class="form-group">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" name="remarks" rows="3"><?php echo htmlspecialchars($item['remarks']); ?></textarea>
            </div>

            <div class="form-actions">
                <a href="inventory.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">Update Item</button>
            </div>
        </form>
    </div>
</div>

<style>
    .container {
        padding: 20px 30px;
        background: #f8f9fa;
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

    .form-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
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
    }

    .btn-cancel:hover {
        background: #e9ecef;
        border-color: #ccc;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }

    .alert-error {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    .alert-success {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .readonly-input {
        background-color: #f2f2f2;
        color: #666;
        cursor: not-allowed;
    }

    .read-only-note {
        color: #666;
        font-size: 0.9rem;
        margin-top: 5px;
        display: block;
    }
</style>
</body>
</html>