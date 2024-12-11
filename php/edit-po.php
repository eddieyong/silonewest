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

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get PO number from URL
if (!isset($_GET['po'])) {
    header("Location: purchase-orders.php");
    exit();
}

$po_number = $_GET['po'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_po'])) {
    $supplier_name = $_POST['supplier_name'];
    $remarks = $_POST['remarks'];
    
    // Start transaction
    $mysqli->begin_transaction();
    
    try {
        // Update PO header
        $stmt = $mysqli->prepare("UPDATE purchase_orders SET supplier_name = ?, remarks = ? WHERE po_number = ?");
        $stmt->bind_param("sss", $supplier_name, $remarks, $po_number);
        $stmt->execute();
        
        // Delete existing items
        $stmt = $mysqli->prepare("DELETE FROM po_items WHERE po_number = ?");
        $stmt->bind_param("s", $po_number);
        $stmt->execute();
        
        // Insert updated items
        $total_amount = 0;
        foreach ($_POST['items'] as $item) {
            if (empty($item['bar_code']) || empty($item['quantity']) || empty($item['unit_price'])) {
                continue;
            }
            
            $stmt = $mysqli->prepare("INSERT INTO po_items (po_number, bar_code, quantity, unit_price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssdd", $po_number, $item['bar_code'], $item['quantity'], $item['unit_price']);
            $stmt->execute();
            
            $total_amount += ($item['quantity'] * $item['unit_price']);
        }
        
        // Log the activity
        $description = "Updated Purchase Order (PO: $po_number, Supplier: $supplier_name, Total: RM" . number_format($total_amount, 2) . ")";
        logActivity($mysqli, 'purchase_order', $description);
        
        $mysqli->commit();
        $_SESSION['success_msg'] = "Purchase Order updated successfully!";
        header("Location: purchase-orders.php");
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error_msg'] = "Error updating Purchase Order: " . $e->getMessage();
    }
}

// Get PO details
$stmt = $mysqli->prepare("SELECT * FROM purchase_orders WHERE po_number = ? AND status = 'Pending'");
$stmt->bind_param("s", $po_number);
$stmt->execute();
$po_result = $stmt->get_result();

if ($po_result->num_rows === 0) {
    $_SESSION['error_msg'] = "Purchase Order not found or cannot be edited.";
    header("Location: purchase-orders.php");
    exit();
}

$po_details = $po_result->fetch_assoc();

// Get PO items
$stmt = $mysqli->prepare("
    SELECT pi.*, i.inventory_item 
    FROM po_items pi 
    JOIN inventory i ON pi.bar_code = i.bar_code 
    WHERE pi.po_number = ?
    ORDER BY pi.id ASC
");
$stmt->bind_param("s", $po_number);
$stmt->execute();
$items_result = $stmt->get_result();
$po_items = [];
while ($item = $items_result->fetch_assoc()) {
    $po_items[] = $item;
}

// Get all suppliers for the dropdown
$suppliers_query = "SELECT company_name FROM supplier ORDER BY company_name";
$suppliers_result = $mysqli->query($suppliers_query);

// Get all inventory items for the dropdown
$items_query = "SELECT bar_code, inventory_item FROM inventory ORDER BY inventory_item";
$items_result = $mysqli->query($items_query);
$inventory_items = [];
while ($row = $items_result->fetch_assoc()) {
    $inventory_items[] = $row;
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

    .form-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        color: #333;
        font-weight: 500;
    }

    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }

    select.form-control {
        height: 38px;
    }

    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .items-table th,
    .items-table td {
        padding: 12px;
        border: 1px solid #ddd;
    }

    .items-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }

    .btn-add-item {
        background: #5c1f00;
        color: white;
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-bottom: 20px;
    }

    .btn-delete {
        background: #ffebee;
        color: #c62828;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
    }

    .btn-back {
        background: #f8f9fa;
        color: #333;
        padding: 10px 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-save {
        background: #5c1f00;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
    }

    .form-buttons {
        display: flex;
        justify-content: space-between;
        margin-top: 30px;
    }

    .grand-total-section {
        text-align: right;
        padding: 15px;
        font-size: 1.1rem;
        border-top: 1px solid #ddd;
        margin-top: 20px;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit Purchase Order</h1>
    </div>

    <div class="form-container">
        <form id="poForm" method="POST">
            <div class="form-group">
                <label>PO Number</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($po_details['po_number']); ?>" readonly>
            </div>

            <div class="form-group">
                <label for="supplier_name">Supplier *</label>
                <select name="supplier_name" id="supplier_name" class="form-control" required>
                    <?php while ($supplier = $suppliers_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($supplier['company_name']); ?>"
                            <?php echo ($supplier['company_name'] === $po_details['supplier_name']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supplier['company_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="remarks">Remarks</label>
                <textarea name="remarks" id="remarks" class="form-control" rows="3"><?php echo htmlspecialchars($po_details['remarks']); ?></textarea>
            </div>

            <h3>Items</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item *</th>
                        <th>Quantity *</th>
                        <th>Unit Price (RM) *</th>
                        <th>Total (RM)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="itemsTableBody">
                    <?php foreach ($po_items as $index => $item): ?>
                    <tr>
                        <td>
                            <select name="items[<?php echo $index; ?>][bar_code]" class="form-control" required>
                                <?php foreach ($inventory_items as $inv_item): ?>
                                    <option value="<?php echo htmlspecialchars($inv_item['bar_code']); ?>"
                                        <?php echo ($inv_item['bar_code'] === $item['bar_code']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($inv_item['inventory_item']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="items[<?php echo $index; ?>][quantity]" class="form-control" 
                                min="1" required value="<?php echo htmlspecialchars($item['quantity']); ?>"
                                onchange="calculateTotal(this.parentElement.parentElement)" 
                                onkeyup="calculateTotal(this.parentElement.parentElement)">
                        </td>
                        <td>
                            <input type="number" name="items[<?php echo $index; ?>][unit_price]" class="form-control" 
                                min="0.01" step="0.01" required value="<?php echo htmlspecialchars($item['unit_price']); ?>"
                                onchange="calculateTotal(this.parentElement.parentElement)" 
                                onkeyup="calculateTotal(this.parentElement.parentElement)">
                        </td>
                        <td><span class="row-total"><?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></span></td>
                        <td>
                            <button type="button" class="btn-delete" onclick="removeItem(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <button type="button" class="btn-add-item" onclick="addItem()">
                <i class="fas fa-plus"></i> Add Item
            </button>

            <div class="grand-total-section">
                <strong>Grand Total: RM </strong>
                <span id="grandTotal">0.00</span>
            </div>

            <div class="form-buttons">
                <a href="purchase-orders.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <button type="submit" name="update_po" class="btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
// Store inventory items for dropdown
const inventoryItems = <?php echo json_encode($inventory_items); ?>;

function addItem() {
    const tbody = document.getElementById('itemsTableBody');
    const rowCount = tbody.children.length;
    const row = document.createElement('tr');
    
    row.innerHTML = `
        <td>
            <select name="items[${rowCount}][bar_code]" class="form-control" required>
                <option value="">Select Item</option>
                ${inventoryItems.map(item => `
                    <option value="${item.bar_code}">${item.inventory_item}</option>
                `).join('')}
            </select>
        </td>
        <td>
            <input type="number" name="items[${rowCount}][quantity]" class="form-control" min="1" required 
                onchange="calculateTotal(this.parentElement.parentElement)" 
                onkeyup="calculateTotal(this.parentElement.parentElement)">
        </td>
        <td>
            <input type="number" name="items[${rowCount}][unit_price]" class="form-control" min="0.01" step="0.01" required 
                onchange="calculateTotal(this.parentElement.parentElement)" 
                onkeyup="calculateTotal(this.parentElement.parentElement)">
        </td>
        <td><span class="row-total">0.00</span></td>
        <td>
            <button type="button" class="btn-delete" onclick="removeItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
}

function removeItem(button) {
    const row = button.parentElement.parentElement;
    row.remove();
    calculateGrandTotal();
    renumberItems();
}

function renumberItems() {
    const tbody = document.getElementById('itemsTableBody');
    Array.from(tbody.children).forEach((row, index) => {
        row.querySelector('select[name*="[bar_code]"]').name = `items[${index}][bar_code]`;
        row.querySelector('input[name*="[quantity]"]').name = `items[${index}][quantity]`;
        row.querySelector('input[name*="[unit_price]"]').name = `items[${index}][unit_price]`;
    });
}

function calculateTotal(row) {
    const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
    const unitPrice = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
    const total = quantity * unitPrice;
    
    row.querySelector('.row-total').textContent = total.toFixed(2);
    calculateGrandTotal();
}

function calculateGrandTotal() {
    const totals = Array.from(document.getElementsByClassName('row-total'))
        .map(td => parseFloat(td.textContent) || 0);
    const grandTotal = totals.reduce((sum, total) => sum + total, 0);
    document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);
}

// Calculate initial grand total
document.addEventListener('DOMContentLoaded', function() {
    calculateGrandTotal();
});

// Form validation
document.getElementById('poForm').onsubmit = function(e) {
    const items = document.querySelectorAll('#itemsTableBody tr');
    if (items.length === 0) {
        e.preventDefault();
        alert('Please add at least one item to the purchase order.');
        return false;
    }

    // Check for duplicate items
    const selectedItems = new Set();
    for (let row of items) {
        const barCode = row.querySelector('select[name*="[bar_code]"]').value;
        if (selectedItems.has(barCode)) {
            e.preventDefault();
            alert('Duplicate items are not allowed. Please combine quantities instead.');
            return false;
        }
        selectedItems.add(barCode);
    }
    
    return true;
};
</script>

<?php $mysqli->close(); ?> 