<?php
// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Handle item deletion if requested
if (isset($_POST['delete_item'])) {
    $item_id = $_POST['item_id'];
    $stmt = $mysqli->prepare("DELETE FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();
}

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT id, item_number, inventory_item, bar_code, mfg_date, exp_date, balance_brought_forward, stock_in, stock_out, balance, remarks, created_at, updated_at FROM inventory";

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

if (!$result) {
    die("Query failed: " . $mysqli->error);
}

// Include the header
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
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Inventory Management</h1>
        <div class="header-buttons">
            <a href="export-pdf.php" class="export-btn pdf">
                <i class="fas fa-file-pdf"></i> Export to PDF
            </a>
            <a href="add-inventory.php" class="add-item-btn">
                <i class="fas fa-plus"></i> Add New Item
            </a>
        </div>
    </div>

    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search by item number, name, or barcode..." 
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">
            <i class="fas fa-search"></i> Search
        </button>
    </form>

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
            <tbody>
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

<?php $mysqli->close(); ?> 