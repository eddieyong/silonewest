<?php
session_start();
require_once 'permissions.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../admin-login.html");
    exit();
}

// Check if user has permission to access inventory
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['Admin', 'Storekeeper', 'Coordinator'])) {
    header("Location: ../admin-login.html");
    exit();
}

// Set view-only mode for Coordinator
$isViewOnly = ($_SESSION['role'] === 'Coordinator');

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Only allow these operations for Admin and Storekeeper
if (!$isViewOnly) {
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
}

// Get all inventory items
$query = "SELECT * FROM inventory WHERE 1";

// Add search functionality
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $mysqli->real_escape_string($_GET['search']);
    $query .= " AND (inventory_item LIKE '%$search%' OR bar_code LIKE '%$search%' OR category LIKE '%$search%')";
}

$query .= " ORDER BY inventory_item ASC";
$result = $mysqli->query($query);

include 'admin-header.php';
?>

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

<div class="container">
    <div class="page-header">
        <h1>Inventory Management</h1>
        <div class="header-actions">
            <a href="export-pdf.php" class="btn btn-red">
                <i class="fas fa-file-pdf"></i> Export to PDF
            </a>
            <a href="export-excel.php" class="btn btn-green">
                <i class="fas fa-file-excel"></i> Export to Excel
            </a>
            <?php if (!$isViewOnly): ?>
                <a href="add-inventory.php" class="btn btn-brown">
                    <i class="fas fa-plus"></i> Add New Item
                </a>
                <a href="#" class="btn btn-brown" id="openStockInPopup">
                    <i class="fas fa-arrow-down"></i> Stock In
                </a>
                <a href="#" class="btn btn-brown" id="openStockOutPopup">
                    <i class="fas fa-arrow-up"></i> Stock Out
                </a>
                <a href="purchase-orders.php" class="btn btn-success">
                    <i class="fas fa-shopping-cart"></i> Purchase Orders
                </a>
                <a href="delivery-orders.php" class="btn btn-info">
                    <i class="fas fa-truck"></i> Delivery Orders
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="search-section">
        <input type="text" id="searchInput" placeholder="Search by name, item number, or barcode..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <select id="monthSelect">
            <option value="">December</option>
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
        <button type="button" class="btn btn-brown" onclick="performSearch()">
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
                    <?php if (!$isViewOnly): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['item_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['inventory_item']); ?></td>
                        <td><?php echo htmlspecialchars($row['bar_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['mfg_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['exp_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['balance_brought_forward']); ?></td>
                        <td><?php echo htmlspecialchars($row['stock_in']); ?></td>
                        <td><?php echo htmlspecialchars($row['stock_out']); ?></td>
                        <td><?php echo htmlspecialchars($row['balance']); ?></td>
                        <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($row['updated_at']); ?></td>
                        <?php if (!$isViewOnly): ?>
                            <td class="actions">
                                <a href="edit-inventory.php?bar_code=<?php echo urlencode($row['bar_code']); ?>" class="edit-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                    <input type="hidden" name="bar_code" value="<?php echo htmlspecialchars($row['bar_code']); ?>">
                                    <button type="submit" name="delete_item" class="delete-btn">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Stock In Modal -->
<div id="stockInModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Stock In</h2>
        <form id="stockInForm" action="update-stock.php?action=stock_in" method="POST">
            <div id="stockInItems">
                <div class="stock-item">
                    <input type="text" name="items[0][bar_code]" placeholder="Bar Code" required>
                    <input type="number" name="items[0][amount]" placeholder="Amount" required min="1">
                </div>
            </div>
            <button type="button" class="btn btn-brown add-item">Add Another Item</button>
            <button type="submit" class="btn btn-brown">Submit Stock In</button>
        </form>
    </div>
</div>

<!-- Stock Out Modal -->
<div id="stockOutModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Stock Out</h2>
        <form id="stockOutForm" action="update-stock.php?action=stock_out" method="POST">
            <div id="stockOutItems">
                <div class="stock-item">
                    <input type="text" name="items[0][bar_code]" placeholder="Bar Code" required>
                    <input type="number" name="items[0][amount]" placeholder="Amount" required min="1">
                </div>
            </div>
            <button type="button" class="btn btn-brown add-item">Add Another Item</button>
            <button type="submit" class="btn btn-brown">Submit Stock Out</button>
        </form>
    </div>
</div>

<style>
    .container {
        padding: 30px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .header-actions {
        display: flex;
        gap: 15px;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
        cursor: pointer;
        border: none;
        color: white;
        transition: background-color 0.3s;
    }

    .btn-brown {
        background-color: #5c1f00;
    }

    .btn-brown:hover {
        background-color: #4a1900;
        color: white;
        text-decoration: none;
    }

    .btn-red {
        background-color: #ff0000;
    }

    .btn-red:hover {
        background-color: #cc0000;
        color: white;
        text-decoration: none;
    }

    .btn-green {
        background-color: #008000;
    }

    .btn-green:hover {
        background-color: #006600;
        color: white;
        text-decoration: none;
    }

    .btn-success {
        background-color: #28a745;
    }

    .btn-success:hover {
        background-color: #218838;
        color: white;
        text-decoration: none;
    }

    .btn-info {
        background-color: #17a2b8;
    }

    .btn-info:hover {
        background-color: #138496;
        color: white;
        text-decoration: none;
    }

    .search-section {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
        align-items: center;
    }

    .search-section input {
        flex: 1;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }

    .search-section select {
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
        min-width: 150px;
    }

    .search-section button {
        padding: 10px 20px;
    }

    .inventory-table {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        font-size: 15px;
    }

    th, td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    th {
        background: #f8f9fa;
        font-weight: 600;
    }

    .actions {
        display: flex;
        gap: 10px;
    }

    .edit-btn, .delete-btn {
        padding: 8px 12px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
    }

    .edit-btn {
        background: #e3f2fd;
        color: #1976d2;
        border: none;
    }

    .delete-btn {
        background: #ffebee;
        color: #c62828;
        border: none;
    }

    h1 {
        font-size: 24px;
        margin: 0;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 500px;
        border-radius: 5px;
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover {
        color: black;
    }

    .stock-item {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
    }

    .stock-item input {
        flex: 1;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .modal form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .modal h2 {
        margin-top: 0;
        color: #5c1f00;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }

    .alert-success {
        color: #0f5132;
        background-color: #d1e7dd;
        border-color: #badbcc;
    }

    .alert-danger {
        color: #842029;
        background-color: #f8d7da;
        border-color: #f5c2c7;
    }
</style>

<script>
function performSearch() {
    const searchValue = document.getElementById('searchInput').value;
    const monthValue = document.getElementById('monthSelect').value;
    window.location.href = `inventory.php?search=${encodeURIComponent(searchValue)}&month=${monthValue}`;
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const monthSelect = document.getElementById('monthSelect');
    
    searchInput.addEventListener('input', debounce(performSearch, 500));
    monthSelect.addEventListener('change', performSearch);
});

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get modal elements
    const stockInModal = document.getElementById('stockInModal');
    const stockOutModal = document.getElementById('stockOutModal');
    
    // Get buttons that open the modals
    const stockInBtn = document.getElementById('openStockInPopup');
    const stockOutBtn = document.getElementById('openStockOutPopup');
    
    // Get close buttons
    const closeButtons = document.getElementsByClassName('close');
    
    // Open Stock In Modal
    stockInBtn.onclick = function() {
        stockInModal.style.display = 'block';
    }
    
    // Open Stock Out Modal
    stockOutBtn.onclick = function() {
        stockOutModal.style.display = 'block';
    }
    
    // Close modals when clicking (x)
    Array.from(closeButtons).forEach(button => {
        button.onclick = function() {
            stockInModal.style.display = 'none';
            stockOutModal.style.display = 'none';
        }
    });
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target == stockInModal) {
            stockInModal.style.display = 'none';
        }
        if (event.target == stockOutModal) {
            stockOutModal.style.display = 'none';
        }
    }

    // Add item functionality
    document.querySelectorAll('.add-item').forEach(button => {
        button.onclick = function() {
            const itemsContainer = this.closest('form').querySelector('.stock-item').parentElement;
            const newIndex = itemsContainer.children.length;
            const newItem = document.createElement('div');
            newItem.className = 'stock-item';
            newItem.innerHTML = `
                <input type="text" name="items[${newIndex}][bar_code]" placeholder="Bar Code" required>
                <input type="number" name="items[${newIndex}][amount]" placeholder="Amount" required min="1">
                <button type="button" class="btn btn-red remove-item" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            itemsContainer.appendChild(newItem);
        }
    });

    // Form submission handling
    const forms = document.querySelectorAll('#stockInForm, #stockOutForm');
    forms.forEach(form => {
        form.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                // Close the modal
                stockInModal.style.display = 'none';
                stockOutModal.style.display = 'none';
                // Reload the page to show updated inventory and success message
                window.location.href = 'inventory.php';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request.');
            });
        }
    });
});
</script>

<?php $mysqli->close(); ?>
</body>
</html>