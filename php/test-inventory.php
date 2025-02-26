<?php
session_start();
require_once 'functions.php';

// Check if user is logged in and has Admin role
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

// Handle item deletion
if (isset($_POST['delete_item'])) {
    $item_id = $_POST['item_id'];
    
    // Get item details before deletion
    $stmt = $mysqli->prepare("SELECT inventory_item FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    
    // Delete the item
    $stmt = $mysqli->prepare("DELETE FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    
    if ($stmt->execute()) {
        // Log the activity
        logActivity($mysqli, 'inventory', "Deleted inventory item: " . $item['inventory_item']);
        $_SESSION['success_msg'] = "Item deleted successfully!";
    } else {
        $_SESSION['error_msg'] = "Error deleting item.";
    }
    
    header("Location: inventory.php");
    exit();
}

// Handle form submission for adding/updating items
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $item_number = $_POST['item_number'];
    $inventory_item = $_POST['inventory_item'];
    $bar_code = $_POST['bar_code'];
    $mfg_date = $_POST['mfg_date'];
    $exp_date = $_POST['exp_date'];
    $balance_brought_forward = $_POST['balance_brought_forward'];
    $stock_in = $_POST['stock_in'];
    $stock_out = $_POST['stock_out'];
    $balance = $_POST['balance'];
    $remarks = $_POST['remarks'];

    if (isset($_POST['id'])) {
        // Update existing item
        $id = $_POST['id'];
        $stmt = $mysqli->prepare("UPDATE inventory SET item_number=?, inventory_item=?, bar_code=?, mfg_date=?, exp_date=?, balance_brought_forward=?, stock_in=?, stock_out=?, balance=?, remarks=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
        $stmt->bind_param("sssssiiiiis", $item_number, $inventory_item, $bar_code, $mfg_date, $exp_date, $balance_brought_forward, $stock_in, $stock_out, $balance, $remarks, $id);
        
        if ($stmt->execute()) {
            // Log the activity
            logActivity($mysqli, 'inventory', "Updated inventory item: $inventory_item");
            $_SESSION['success_msg'] = "Item updated successfully!";
        } else {
            $_SESSION['error_msg'] = "Error updating item.";
        }
    } else {
        // Add new item
        $stmt = $mysqli->prepare("INSERT INTO inventory (item_number, inventory_item, bar_code, mfg_date, exp_date, balance_brought_forward, stock_in, stock_out, balance, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssiiiis", $item_number, $inventory_item, $bar_code, $mfg_date, $exp_date, $balance_brought_forward, $stock_in, $stock_out, $balance, $remarks);
        
        if ($stmt->execute()) {
            // Log the activity
            logActivity($mysqli, 'inventory', "Added new inventory item: $inventory_item");
            $_SESSION['success_msg'] = "Item added successfully!";
        } else {
            $_SESSION['error_msg'] = "Error adding item.";
        }
    }

    // Check for low stock and create alert
    if ($balance < 10) {
        logActivity($mysqli, 'stock_alert', "Low stock alert for $inventory_item (Current balance: $balance)");
    }

    header("Location: inventory.php");
    exit();
}

// Get all inventory items
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT * FROM inventory";

if (!empty($search)) {
    $search = "%$search%";
    $query .= " WHERE item_number LIKE ? OR inventory_item LIKE ? OR bar_code LIKE ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $mysqli->query($query);
}

include 'admin-header.php';
?>

<style>
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
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .page-title {
        margin: 0;
        font-size: 1.5rem;
        color: #333;
    }

    .header-buttons {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .search-bar {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .search-bar input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    .search-bar input:focus {
        border-color: #5c1f00;
        outline: none;
    }

    .search-bar button {
        padding: 10px 20px;
        background: #5c1f00;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.3s;
    }

    .search-bar button:hover {
        background: #7a2900;
    }

    .export-btn.pdf,
    .export-btn.excel,
    .add-item-btn {
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.3s;
        color: white;
    }

    .export-btn.pdf {
        background: #ff0000;
    }

    .add-item-btn {
        background: #5c1f00;
    }

    .export-btn.pdf:hover {
        background: #cc0000;
    }

    .add-item-btn:hover {
        background: #7a2900;
    }

    .inventory-table {
        width: 100%;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        overflow: hidden;
        overflow-x: auto;
    }

    .inventory-table table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1200px;
    }

    .inventory-table th,
    .inventory-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .inventory-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
        white-space: nowrap;
    }

    .inventory-table tr:hover {
        background: #f8f9fa;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
        white-space: nowrap;
    }

    .edit-btn,
    .delete-btn {
        padding: 6px 12px;
        border-radius: 4px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.9rem;
    }

    .edit-btn {
        background: #e3f2fd;
        color: #1976d2;
    }

    .delete-btn {
        background: #ffebee;
        color: #c62828;
        border: none;
        cursor: pointer;
    }

    .edit-btn:hover {
        background: #bbdefb;
    }

    .delete-btn:hover {
        background: #ffcdd2;
    }
    .stock-btn {
            display: inline-block;
            margin: 10 px;
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .stock-btn:hover {
            background-color: #0056b3;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 999;
        }

        .popup {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            width: 400px;
            text-align: center;
        }

        .popup h2 {
            margin-top: 0;
        }

        .popup form {
            display: flex;
            flex-direction: column;
        }

        .popup form input, .popup form button {
            margin: 10px 0;
            padding: 10px;
            font-size: 16px;
        }

        .close-btn {
            background: #ff4d4d;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
        }

        .close-btn:hover {
            background: #cc0000;
        }

        .export-btn.excel {
        background: green;
    }

    
    .export-btn.excel:hover {
        background: #004000;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Inventory Management</h1>
        <div class="header-buttons">
            <a href="export-pdf.php" class="export-btn pdf">
                <i class="fas fa-file-pdf"></i> Export to PDF
            </a>
            <a href="export-excel.php" class="export-btn excel">
                <i class="fas fa-file-excel"></i> Export to Excel
            </a>
            <a href="add-inventory.php" class="add-item-btn">
                <i class="fas fa-plus"></i> Add New Item
            </a>
            <a href="#" class="stock-btn" id="openStockInPopup">
        <i class="fas fa-arrow-down"></i> Stock In
          </a>
          <a href="#" class="stock-btn" id="openStockOutPopup">
        <i class="fas fa-arrow-up"></i> Stock Out
    </a>
        </div>
    </div>

    <div class="overlay" id="popupOverlay">
    <div class="popup" id="stockPopup">
        <h2 id="popupTitle">Stock In</h2>
        <form method="POST" action="update-in.php">
            <label for="barcode">Scan Barcode:</label>
            <input type="text" id="barcode" name="barcode" required autofocus>
            
            <input type="hidden" name="action" id="popupAction" value="stock_in">

            <button type="submit">Update Inventory</button>
            <button type="button" class="close-btn" id="closePopup">Close</button>
        </form>
    </div>
</div>

    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by item number, name, or barcode..." 
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="button">
            <i class="fas fa-search"></i> Search
        </button>
    </div>

    <div class="inventory-table">
        <table>
            <thead>
                <tr>
                    <th>Item Number</th>
                    <th>Item Name</th>
                    <th>Bar Code</th>
                    <th>Mfg Date</th>
                    <th>Exp Date</th>
                    <th>Balance B/F</th>
                    <th>Stock In</th>
                    <th>Stock Out</th>
                    <th>Balance</th>
                    <th>Remarks</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php while($row = $result->fetch_assoc()): 
                    // Set default values if any field is null
                    $item_number = isset($row['item_number']) ? $row['item_number'] : 'N/A';
                    $inventory_item = isset($row['inventory_item']) ? $row['inventory_item'] : 'N/A';
                    $bar_code = isset($row['bar_code']) ? $row['bar_code'] : 'N/A';
                    $mfg_date = isset($row['mfg_date']) ? date('Y-m-d', strtotime($row['mfg_date'])) : 'N/A';
                    $exp_date = isset($row['exp_date']) ? date('Y-m-d', strtotime($row['exp_date'])) : 'N/A';
                    $balance_brought_forward = isset($row['balance_brought_forward']) ? intval($row['balance_brought_forward']) : 0;
                    $stock_in = isset($row['stock_in']) ? intval($row['stock_in']) : 0;
                    $stock_out = isset($row['stock_out']) ? intval($row['stock_out']) : 0;
                    $balance = isset($row['balance']) ? intval($row['balance']) : 0;
                    $remarks = isset($row['remarks']) ? $row['remarks'] : '';
                    $created_at = isset($row['created_at']) ? date('Y-m-d H:i:s', strtotime($row['created_at'])) : 'N/A';
                    $updated_at = isset($row['updated_at']) ? date('Y-m-d H:i:s', strtotime($row['updated_at'])) : 'N/A';
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item_number); ?></td>
                    <td><?php echo htmlspecialchars($inventory_item); ?></td>
                    <td><?php echo htmlspecialchars($bar_code); ?></td>
                    <td><?php echo htmlspecialchars($mfg_date); ?></td>
                    <td><?php echo htmlspecialchars($exp_date); ?></td>
                    <td><?php echo htmlspecialchars($balance_brought_forward); ?></td>
                    <td><?php echo htmlspecialchars($stock_in); ?></td>
                    <td><?php echo htmlspecialchars($stock_out); ?></td>
                    <td><?php echo htmlspecialchars($balance); ?></td>
                    <td><?php echo htmlspecialchars($remarks); ?></td>
                    <td><?php echo htmlspecialchars($created_at); ?></td>
                    <td><?php echo htmlspecialchars($updated_at); ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="edit-inventory.php?id=<?php echo $row['id']; ?>" class="edit-btn">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                <input type="hidden" name="item_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="delete_item" class="delete-btn">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Add debounce function to limit how often the search is performed
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Function to perform the search
function performSearch() {
    const searchValue = document.getElementById('searchInput').value;
    
    // Create XMLHttpRequest object
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `search-inventory.php?search=${encodeURIComponent(searchValue)}`, true);
    
    xhr.onload = function() {
        if (this.status === 200) {
            document.getElementById('tableBody').innerHTML = this.responseText;
        }
    };
    
    xhr.send();
}

// Add event listener with debounce
document.getElementById('searchInput').addEventListener('input', debounce(performSearch, 300));


const openStockInPopup = document.getElementById('openStockInPopup');
    const openStockOutPopup = document.getElementById('openStockOutPopup');
    const popupOverlay = document.getElementById('popupOverlay');
    const stockPopup = document.getElementById('stockPopup');
    const popupTitle = document.getElementById('popupTitle');
    const popupAction = document.getElementById('popupAction');
    const closePopup = document.getElementById('closePopup');

    openStockInPopup.addEventListener('click', (e) => {
        e.preventDefault();
        popupTitle.textContent = 'Stock In';
        popupAction.value = 'stock_in';
        popupOverlay.style.display = 'flex';
    });

    openStockOutPopup.addEventListener('click', (e) => {
        e.preventDefault();
        popupTitle.textContent = 'Stock Out';
        popupAction.value = 'stock_out';
        popupOverlay.style.display = 'flex';
    });

    closePopup.addEventListener('click', () => {
        popupOverlay.style.display = 'none';
    });

    popupOverlay.addEventListener('click', (e) => {
        if (e.target === popupOverlay) {
            popupOverlay.style.display = 'none';
        }
    });

   
</script>

<?php $mysqli->close(); ?> 