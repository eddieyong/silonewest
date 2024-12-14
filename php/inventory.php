<?php
session_start();
require_once 'permissions.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../admin-login.html");
    exit();
}

// Check if user has permission to access inventory
if (!hasPermission($_SESSION['role'], 'inventory')) {
    header("Location: dashboard.php");
    $_SESSION['error_msg'] = "You don't have permission to access the inventory management system.";
    exit();
}

// Check if user has view-only access
$isViewOnly = isViewOnly($_SESSION['role'], 'inventory');

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Handle item deletion
if (isset($_POST['delete_item'])) {
    $bar_code = $_POST['bar_code'];
    
    // Get item details before deletion
    $stmt = $mysqli->prepare("SELECT inventory_item FROM inventory WHERE bar_code = ?");
    $stmt->bind_param("s", $bar_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    
    // Delete the item
    $stmt = $mysqli->prepare("DELETE FROM inventory WHERE bar_code = ?");
    $stmt->bind_param("s", $bar_code);
    
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
    // Check if item exists
    $check_stmt = $mysqli->prepare("SELECT bar_code FROM inventory WHERE bar_code = ?");
    $check_stmt->bind_param("s", $bar_code);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Update existing item
        $stmt = $mysqli->prepare("UPDATE inventory SET item_number=?, inventory_item=?, mfg_date=?, exp_date=?, balance_brought_forward=?, stock_in=?, stock_out=?, balance=?, remarks=?, updated_at=CURRENT_TIMESTAMP WHERE bar_code=?");
        $stmt->bind_param("ssssiiiiss", $item_number, $inventory_item, $mfg_date, $exp_date, $balance_brought_forward, $stock_in, $stock_out, $balance, $remarks, $bar_code);
        
        if ($stmt->execute()) {
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

// Get all inventory items with extended search options
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$month = isset($_GET['month']) ? trim($_GET['month']) : '';

$query = "SELECT * FROM inventory WHERE 1"; // Start with a base query

$params = [];
$types = "";

// Handle search by item name, number, or barcode
if (!empty($search)) {
    $search = "%$search%";
    $query .= " AND (item_number LIKE ? OR inventory_item LIKE ? OR bar_code LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "sss";
}

// Handle search by month (for created_at)
if (!empty($month)) {
    $query .= " AND MONTH(created_at) = ?";
    $params[] = $month;
    $types .= "s";
}

$query .= " ORDER BY created_at DESC";  // Order by creation date, newest first

$stmt = $mysqli->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

include 'admin-header.php';
?>
<style>
    .container {
        padding: 20px 30px;
        background: #f8f9fa;
    }

    .page-header {
        background: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-bottom: 20px;
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
        align-items: center;
    }

    .search-bar input,
    .search-bar select {
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .search-bar input {
        flex: 1;
        min-width: 200px;
    }

    .search-bar select {
        min-width: 150px;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background: #fff url("data:image/svg+xml,<svg height='10px' width='10px' viewBox='0 0 16 16' fill='%23000000' xmlns='http://www.w3.org/2000/svg'><path d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/></svg>") no-repeat;
        background-position: calc(100% - 12px) center;
        padding-right: 35px;
        cursor: pointer;
    }

    .search-bar input:focus,
    .search-bar select:focus {
        outline: none;
        border-color: #5c1f00;
        box-shadow: 0 0 0 2px rgba(92, 31, 0, 0.1);
    }

    .search-bar select:hover {
        border-color: #5c1f00;
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
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        width: 500px;
        position: relative;
    }

    .popup h2 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #333;
        font-size: 1.5rem;
        text-align: center;
    }

    .popup form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 10px;
    }

    .row label {
        display: block;
        margin-bottom: 5px;
        color: #555;
        font-weight: 500;
    }

    .row input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .row input:focus {
        border-color: #5c1f00;
        outline: none;
        box-shadow: 0 0 0 2px rgba(92, 31, 0, 0.1);
    }

    .stock-btn {
        background-color: #5c1f00 !important;
        transition: background-color 0.3s;
    }

    .stock-btn:hover {
        background-color: #7a2900 !important;
    }

    .popup button {
        background: #5c1f00;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 4px;
        cursor: pointer;
        margin: 10px 0;
        transition: background-color 0.3s;
    }

    .popup button:hover {
        background: #7a2900;
    }

    .popup button[type="submit"] {
        background: #28a745;
    }

    .popup button[type="submit"]:hover {
        background: #218838;
    }

    .close-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #666;
        padding: 5px;
    }

    .close-btn:hover {
        color: #333;
    }

    .export-btn.excel {
        background: green;
    }

    
    .export-btn.excel:hover {
        background: #004000;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-size: 14px;
    }

    .alert-success {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .alert-danger {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    .po-btn,
    .do-btn {
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.3s;
        color: white;
    }

    .po-btn {
        background: #28a745;
    }

    .do-btn {
        background: #17a2b8;
    }

    .po-btn:hover {
        background: #218838;
    }

    .do-btn:hover {
        background: #138496;
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
            <?php if ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Storekeeper'): ?>
            <a href="purchase-orders.php" class="po-btn">
                <i class="fas fa-file-invoice"></i> Purchase Orders
            </a>
            <a href="delivery-orders.php" class="do-btn">
                <i class="fas fa-truck"></i> Delivery Orders
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['success_msg'];
                unset($_SESSION['success_msg']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger">
            <?php 
                echo $_SESSION['error_msg'];
                unset($_SESSION['error_msg']);
            ?>
        </div>
    <?php endif; ?>
    
    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by name, item number, or barcode..." value="<?php echo htmlspecialchars($search); ?>">
        <select id="monthSelect">
            <option value="">-- Select Month --</option>
            <option value="1">January</option>
            <option value="2">February</option>
            <option value="3">March</option>
            <option value="4">April</option>
            <option value="5">May</option>
            <option value="6">June</option>
            <option value="7">July</option>
            <option value="8">August</option>
            <option value="9">September</option>
            <option value="10">October</option>
            <option value="11">November</option>
            <option value="12">December</option>
        </select>
        <button type="button" onclick="performSearch()">
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
                            <a href="edit-inventory.php?bar_code=<?php echo urlencode($row['bar_code']); ?>" class="edit-btn">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                <input type="hidden" name="bar_code" value="<?php echo htmlspecialchars($row['bar_code']); ?>">
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

    <!-- Stock In Popup -->
    <div id="stockInPopup" class="overlay">
        <div class="popup">
            <h2>Stock In</h2>
            <button type="button" class="close-btn" onclick="closePopup('stockInPopup')">&times;</button>
            <form id="stockInForm" method="POST" action="update-stock.php?action=stock_in">
                <div id="stockInRowsContainer">
                    <div class="row">
                        <div>
                            <label for="barcode">Bar Code:</label>
                            <input type="text" name="items[0][bar_code]" placeholder="Scan Bar Code" required>
                        </div>
                        <div>
                            <label for="amount">Amount:</label>
                            <input type="number" name="items[0][amount]" min="1" required>
                        </div>
                    </div>
                </div>
                <button type="button" id="addStockInRow">Add Another Item</button>
                <button type="submit">Submit Stock In</button>
            </form>
        </div>
    </div>

    <!-- Stock Out Popup -->
    <div id="stockOutPopup" class="overlay">
        <div class="popup">
            <h2>Stock Out</h2>
            <button type="button" class="close-btn" onclick="closePopup('stockOutPopup')">&times;</button>
            <form id="stockOutForm" method="POST" action="update-stock.php?action=stock_out">
                <div id="stockOutRowsContainer">
                    <div class="row">
                        <div>
                            <label for="barcode">Bar Code:</label>
                            <input type="text" name="items[0][bar_code]" placeholder="Scan Bar Code" required>
                        </div>
                        <div>
                            <label for="amount">Amount:</label>
                            <input type="number" name="items[0][amount]" min="1" required>
                        </div>
                    </div>
                </div>
                <button type="button" id="addStockOutRow">Add Another Item</button>
                <button type="submit">Submit Stock Out</button>
            </form>
        </div>
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
    const searchValue = document.getElementById('searchInput')?.value || '';
    const monthValue = document.getElementById('monthSelect')?.value || '';
    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `inventory.php?search=${encodeURIComponent(searchValue)}&month=${encodeURIComponent(monthValue)}`, true);
    xhr.onreadystatechange = function() {
        if (this.readyState !== 4) return;
        if (this.status === 200) {
            // Parse the response as HTML
            const parser = new DOMParser();
            const newDoc = parser.parseFromString(this.responseText, 'text/html');
            
            // Update only the table content instead of the entire page
            const oldTable = document.querySelector('.inventory-table');
            const newTable = newDoc.querySelector('.inventory-table');
            if (oldTable && newTable) {
                oldTable.innerHTML = newTable.innerHTML;
            }
            
            // Restore the selected values
            const searchInput = document.getElementById('searchInput');
            const monthSelect = document.getElementById('monthSelect');
            if (searchInput) searchInput.value = searchValue;
            if (monthSelect) monthSelect.value = monthValue;
        }
    };
    xhr.send();
}

// Function to attach event listeners
function attachEventListeners() {
    // Search input event listener
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(performSearch, 300));
    }

    // Month select event listener
    const monthSelect = document.getElementById('monthSelect');
    if (monthSelect) {
        monthSelect.addEventListener('change', performSearch);
    }
}

// Initial attachment of event listeners
document.addEventListener('DOMContentLoaded', function() {
    attachEventListeners();
    
    // Set the current month in the select if no month is selected
    const monthSelect = document.getElementById('monthSelect');
    if (monthSelect && !monthSelect.value) {
        const currentMonth = new Date().getMonth() + 1; // getMonth() returns 0-11
        monthSelect.value = currentMonth;
    }
});

// Stock In/Out Popup Functions
function openPopup(popupId) {
    const popup = document.getElementById(popupId + 'Popup');
    if (popup) {
        popup.style.display = 'flex';
    }
}

function closePopup(popupId) {
    const popup = document.getElementById(popupId);
    if (popup) {
        popup.style.display = 'none';
    }
}

function addRow(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const rowIndex = container.children.length;
    const rowHTML = `
        <div class="row">
            <div>
                <label for="barcode">Bar Code:</label>
                <input type="text" name="items[${rowIndex}][bar_code]" placeholder="Scan Bar Code" required>
            </div>
            <div>
                <label for="amount">Amount:</label>
                <input type="number" name="items[${rowIndex}][amount]" min="1" required>
            </div>
        </div>
    `;
    container.insertAdjacentHTML("beforeend", rowHTML);
}

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Event Listeners for Stock In/Out buttons
    const stockInBtn = document.getElementById('openStockInPopup');
    const stockOutBtn = document.getElementById('openStockOutPopup');
    
    if (stockInBtn) {
        stockInBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openPopup('stockIn');
        });
    }
    
    if (stockOutBtn) {
        stockOutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openPopup('stockOut');
        });
    }

    // Event Listeners for Add Row buttons
    const addStockInRowBtn = document.getElementById('addStockInRow');
    const addStockOutRowBtn = document.getElementById('addStockOutRow');
    
    if (addStockInRowBtn) {
        addStockInRowBtn.addEventListener('click', function() {
            addRow('stockInRowsContainer');
        });
    }
    
    if (addStockOutRowBtn) {
        addStockOutRowBtn.addEventListener('click', function() {
            addRow('stockOutRowsContainer');
        });
    }

    // Event Listeners for Close buttons
    const closeButtons = document.querySelectorAll('.close-btn');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const popupId = this.closest('.overlay').id;
            closePopup(popupId);
        });
    });
});
</script>

<?php $mysqli->close(); ?>
</body>
</html>