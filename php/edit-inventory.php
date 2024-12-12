<?php
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

$message = '';
$messageType = '';

// Get item ID from URL
if (!isset($_GET['id'])) {
    header("Location: inventory.php");
    exit();
}

$id = $_GET['id'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_number = trim($_POST['item_number']);
    $inventory_item = trim($_POST['inventory_item']);
    $bar_code = trim($_POST['bar_code']);
    $mfg_date = trim($_POST['mfg_date']);
    $exp_date = trim($_POST['exp_date']);
    $balance_brought_forward = intval($_POST['balance_brought_forward']);
    $stock_in = intval($_POST['stock_in']);
    $stock_out = intval($_POST['stock_out']);
    $remarks = trim($_POST['remarks']);
    
    // Calculate balance
    $balance = $balance_brought_forward + $stock_in - $stock_out;

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
        remarks = ? 
        WHERE id = ?");

    $stmt->bind_param("sssssiiiisi", 
        $item_number,
        $inventory_item,
        $bar_code,
        $mfg_date,
        $exp_date,
        $balance_brought_forward,
        $stock_in,
        $stock_out,
        $balance,
        $remarks,
        $id
    );

    if ($stmt->execute()) {
        header("Location: inventory.php");
        exit();
    } else {
        $message = "Error updating inventory item: " . $mysqli->error;
        $messageType = "error";
    }
    $stmt->close();
}

// Fetch existing item data
$stmt = $mysqli->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: inventory.php");
    exit();
}

$item = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Inventory Item - SILO</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Header Styles - Exactly matching admin.php */
        .top-nav {
            background: #5c1f00;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-items {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-items a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .nav-items a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-items a.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-items .logout-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-items .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

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
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="logo">
            <a href="admin.php">
                <img src="../img/logo.png" alt="SILO Logo" style="height: 40px;">
            </a>
        </div>
        <div class="nav-items">
            <a href="admin.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="inventory.php" class="active"><i class="fas fa-box"></i> Inventory</a>
            <a href="manage-users.php"><i class="fas fa-users"></i> Users</a>
            <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="stock-summary.php"><i class="fas fa-chart-bar"></i> Stock</a>
            <a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a>
            <a href="logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?');">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Edit Inventory Item</h1>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="inventory-form">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="item_number">Item Number *</label>
                        <input type="text" id="item_number" name="item_number" required 
                               value="<?php echo htmlspecialchars($item['item_number']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="inventory_item">Inventory Item *</label>
                        <input type="text" id="inventory_item" name="inventory_item" required
                               value="<?php echo htmlspecialchars($item['inventory_item']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="bar_code">Bar Code</label>
                    <input type="text" id="bar_code" name="bar_code"
                           value="<?php echo htmlspecialchars($item['bar_code']); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mfg_date">Manufacturing Date</label>
                        <input type="date" id="mfg_date" name="mfg_date"
                               value="<?php echo $item['mfg_date']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="exp_date">Expiry Date</label>
                        <input type="date" id="exp_date" name="exp_date"
                               value="<?php echo $item['exp_date']; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="balance_brought_forward">Balance B/F *</label>
                        <input type="number" id="balance_brought_forward" name="balance_brought_forward" required min="0"
                               value="<?php echo $item['balance_brought_forward']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="stock_in">Stock In *</label>
                        <input type="number" id="stock_in" name="stock_in" required min="0"
                               value="<?php echo $item['stock_in']; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="stock_out">Stock Out *</label>
                        <input type="number" id="stock_out" name="stock_out" required min="0"
                               value="<?php echo $item['stock_out']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <textarea id="remarks" name="remarks" rows="3"><?php echo htmlspecialchars($item['remarks']); ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="inventory.php" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-submit">Update Item</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add any necessary JavaScript here
    </script>
</body>
</html>
<?php $mysqli->close(); ?> 