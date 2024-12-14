<?php
session_start();
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: inventory.php");
    $_SESSION['error_msg'] = "You need to login first.";
    exit();
}

// Check if user has permission to access Purchase Orders
if (!in_array($_SESSION['role'], ['Admin', 'Storekeeper', 'Coordinator', 'Driver'])) {
    header("Location: admin.php");
    exit();
}

// Set view-only mode for Coordinator and Driver
$isViewOnly = ($_SESSION['role'] === 'Coordinator' || $_SESSION['role'] === 'Driver');

$mysqli = new mysqli("localhost", "root", "", "fyp");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Handle PO submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_po'])) {
    $po_number = 'PO-' . date('Ymd') . '-' . sprintf('%04d', rand(0, 9999));
    $supplier_name = $_POST['supplier_name'];
    $delivery_address = $_POST['delivery_address'];
    $remarks = $_POST['remarks'];
    
    // Start transaction
    $mysqli->begin_transaction();
    
    try {
        // Insert PO header with delivery address
        $stmt = $mysqli->prepare("INSERT INTO purchase_orders (po_number, supplier_name, delivery_address, order_date, status, remarks, created_by) VALUES (?, ?, ?, CURRENT_DATE, 'Pending', ?, ?)");
        $stmt->bind_param("sssss", $po_number, $supplier_name, $delivery_address, $remarks, $_SESSION['username']);
        $stmt->execute();
        
        // Insert PO items
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
        $description = "Created new Purchase Order (PO: $po_number, Supplier: $supplier_name, Total: RM" . number_format($total_amount, 2) . ")";
        logActivity($mysqli, 'purchase_order', $description);
        
        $mysqli->commit();
        $_SESSION['success_msg'] = "Purchase Order created successfully!";
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error_msg'] = "Error creating Purchase Order: " . $e->getMessage();
    }
    
    header("Location: purchase-orders.php");
    exit();
}

// Handle PO deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_po'])) {
    $po_number = $_POST['po_number'];
    
    // Start transaction
    $mysqli->begin_transaction();
    
    try {
        // Get PO details before deletion for logging
        $stmt = $mysqli->prepare("SELECT supplier_name FROM purchase_orders WHERE po_number = ?");
        $stmt->bind_param("s", $po_number);
        $stmt->execute();
        $po_result = $stmt->get_result();
        
        if ($po_result->num_rows === 0) {
            throw new Exception("Purchase Order not found.");
        }
        
        $po_details = $po_result->fetch_assoc();
        
        // Delete PO items first (foreign key constraint)
        $stmt = $mysqli->prepare("DELETE FROM po_items WHERE po_number = ?");
        $stmt->bind_param("s", $po_number);
        $stmt->execute();
        
        // Delete PO header without status check
        $stmt = $mysqli->prepare("DELETE FROM purchase_orders WHERE po_number = ?");
        $stmt->bind_param("s", $po_number);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to delete Purchase Order.");
        }
        
        // Log the activity
        $description = "Deleted Purchase Order (PO: $po_number, Supplier: {$po_details['supplier_name']})";
        logActivity($mysqli, 'purchase_order', $description);
        
        $mysqli->commit();
        $_SESSION['success_msg'] = "Purchase Order deleted successfully!";
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error_msg'] = "Error deleting Purchase Order: " . $e->getMessage();
    }
    
    header("Location: purchase-orders.php");
    exit();
}

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $po_number = $_POST['po_number'];
    $new_status = $_POST['status'];
    
    // Start transaction
    $mysqli->begin_transaction();
    
    try {
        // Get current status for logging
        $stmt = $mysqli->prepare("SELECT status FROM purchase_orders WHERE po_number = ?");
        $stmt->bind_param("s", $po_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_status = $result->fetch_assoc()['status'];
        
        // Update status
        $stmt = $mysqli->prepare("UPDATE purchase_orders SET status = ? WHERE po_number = ?");
        $stmt->bind_param("ss", $new_status, $po_number);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to update status.");
        }
        
        // Log the activity
        $description = "Updated Purchase Order status (PO: $po_number) from $current_status to $new_status";
        logActivity($mysqli, 'purchase_order', $description);
        
        $mysqli->commit();
        $_SESSION['success_msg'] = "Status updated successfully!";
        http_response_code(200);
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error_msg'] = "Error updating status: " . $e->getMessage();
        http_response_code(500);
    }
    
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header("Location: purchase-orders.php");
        exit();
    }
}

// Get all suppliers for the dropdown
$suppliers_query = "SELECT company_name FROM supplier ORDER BY company_name";
$suppliers_result = $mysqli->query($suppliers_query);

// Get all inventory items for the dropdown
$items_query = "SELECT bar_code, inventory_item FROM inventory ORDER BY inventory_item";
$items_result = $mysqli->query($items_query);
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}

// Get all purchase orders
$query = "SELECT po.*, 
          COALESCE(SUM(poi.quantity * poi.unit_price), 0) as total_amount 
          FROM purchase_orders po 
          LEFT JOIN po_items poi ON po.po_number = poi.po_number 
          GROUP BY po.po_number 
          ORDER BY po.order_date DESC";
$result = $mysqli->query($query);

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

    .btn-primary {
        background: #5c1f00;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        transition: background-color 0.3s;
    }

    .btn-primary:hover {
        background: #7a2900;
        color: white;
    }

    .table-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }

    .table tbody tr:hover {
        background: #f8f9fa;
    }

    .table tbody tr:last-child td {
        border-bottom: none;
    }

    .table td .status-badge {
        padding: 5px 10px !important;
        border-radius: 15px !important;
        font-size: 0.875rem !important;
        font-weight: 500 !important;
        display: inline-block !important;
    }

    .table td .status-badge.status-pending {
        background-color: #fff3e0 !important;
        color: #f57c00 !important;
    }

    .table td .status-badge.status-completed {
        background-color: #e8f5e9 !important;
        color: #2e7d32 !important;
    }

    .table td .status-badge.status-cancelled {
        background-color: #ffebee !important;
        color: #c62828 !important;
    }

    .table td .status-badge.status-received {
        background-color: #e3f2fd !important;
        color: #1976d2 !important;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
    }

    .btn-view,
    .btn-edit,
    .btn-delete {
        padding: 6px 12px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .btn-view {
        background: #e3f2fd;
        color: #1976d2;
    }

    .btn-edit {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 6px 12px;
        border-radius: 4px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .btn-edit:hover {
        background: #c8e6c9;
        color: #1b5e20;
    }

    .btn-delete {
        background: #ffebee;
        color: #c62828;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .btn-delete:hover {
        background: #ffcdd2;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .modal-content {
        background: white;
        width: 90%;
        max-width: 800px;
        margin: 50px auto;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        background: white;
        z-index: 1;
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 24px;
        color: #666;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.3s;
    }

    .close-btn:hover {
        background: #f8f9fa;
        color: #333;
    }

    .modal-title {
        margin: 0;
        font-size: 1.25rem;
        color: #333;
    }

    .modal-body {
        padding: 20px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 15px;
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
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background: #fff url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 5'%3E%3Cpath fill='%23333' d='M2 0L0 2h4zm0 5L0 3h4z'/%3E%3C/svg%3E") no-repeat right .75rem center;
        background-size: 8px 10px;
        padding-right: 30px;
    }

    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .items-table th,
    .items-table td {
        padding: 10px;
        border: 1px solid #ddd;
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

    .modal-footer {
        padding: 20px;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        position: sticky;
        bottom: 0;
        background: white;
        z-index: 1;
    }

    .btn-cancel {
        background: #f8f9fa;
        color: #333;
        padding: 10px 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-cancel:hover {
        background: #e9ecef;
        border-color: #ccc;
    }

    .btn-create {
        background: #5c1f00;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        transition: background-color 0.3s;
    }

    .btn-create:hover {
        background: #7a2900;
    }

    .grand-total-section {
        text-align: right;
        padding: 15px;
        font-size: 1.1rem;
        border-top: 1px solid #ddd;
        margin-top: 20px;
    }

    .grand-total-section strong {
        margin-right: 10px;
    }

    .btn-status {
        background: #e8f5e9;
        color: #2e7d32;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .status-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .status-modal-content {
        background: white;
        width: 90%;
        max-width: 400px;
        margin: 100px auto;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .status-modal-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .status-modal-body {
        padding: 20px;
    }

    .status-modal-footer {
        padding: 20px;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .status-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        cursor: pointer;
        border-radius: 5px;
        transition: background-color 0.3s;
    }

    .status-option:hover {
        background: #f8f9fa;
    }

    .status-option input[type="radio"] {
        margin: 0;
    }

    .status-completed {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .status-cancelled {
        background: #ffebee;
        color: #c62828;
    }

    .status-received {
        background: #e3f2fd;
        color: #1976d2;
    }

    /* Updated Status Badge Styles */
    .badge {
        padding: 6px 12px !important;
        border-radius: 20px !important;
        font-size: 0.85rem !important;
        font-weight: 500 !important;
        display: inline-block !important;
        text-align: center;
        min-width: 90px;
    }

    .bg-warning {
        background-color: #fff3cd !important;
        color: #856404 !important;
        border: 1px solid #ffeeba !important;
    }

    .bg-success {
        background-color: #d4edda !important;
        color: #155724 !important;
        border: 1px solid #c3e6cb !important;
    }

    .bg-danger {
        background-color: #f8d7da !important;
        color: #721c24 !important;
        border: 1px solid #f5c6cb !important;
    }

    .bg-info {
        background-color: #d1ecf1 !important;
        color: #0c5460 !important;
        border: 1px solid #bee5eb !important;
    }

    /* Status Dropdown Styles */
    .form-select.status-select {
        padding: 6px 30px 6px 12px !important;
        border: 1px solid #ced4da !important;
        border-radius: 4px !important;
        font-size: 0.9rem !important;
        color: #495057 !important;
        background-color: #fff !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
        background-repeat: no-repeat !important;
        background-position: right 0.75rem center !important;
        background-size: 16px 12px !important;
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        cursor: pointer !important;
        min-width: 140px !important;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
    }

    .form-select.status-select:hover {
        border-color: #80bdff !important;
    }

    .form-select.status-select:focus {
        border-color: #80bdff !important;
        outline: 0 !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
    }

    .form-select.status-select option {
        padding: 8px 12px !important;
        font-size: 0.9rem !important;
    }

    /* Action Buttons Container */
    .action-buttons {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    /* Make View and Delete buttons consistent with dropdown */
    .btn-view,
    .btn-delete {
        padding: 6px 12px !important;
        border-radius: 4px !important;
        font-size: 0.9rem !important;
        text-decoration: none !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 5px !important;
        min-width: 80px !important;
        justify-content: center !important;
        transition: all 0.2s ease-in-out !important;
    }

    .btn-view:hover {
        background: #c8e6ff !important;
        color: #0056b3 !important;
    }

    .btn-delete:hover {
        background: #ffcdd2 !important;
        color: #b71c1c !important;
    }

    /* Add these styles in the style section */
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

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Purchase Orders</h1>
        <?php if (!$isViewOnly && $_SESSION['role'] !== 'Driver'): ?>
            <button class="btn-primary" onclick="openCreatePOModal()">
                <i class="fas fa-plus"></i> Create New PO
            </button>
        <?php endif; ?>
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

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Supplier</th>
                    <th>Delivery Address</th>
                    <th>Order Date</th>
                    <th>Status</th>
                    <th>Total Amount</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['po_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['delivery_address']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['order_date'])); ?></td>
                        <td>
                            <?php
                            $status = trim($row['status']); // Trim any whitespace
                            $badge_class = '';
                            $badge_text = htmlspecialchars($status);
                            
                            switch($status) {
                                case 'Pending':
                                    $badge_class = 'badge bg-warning text-dark';
                                    break;
                                case 'Completed':
                                    $badge_class = 'badge bg-success';
                                    break;
                                case 'Cancelled':
                                    $badge_class = 'badge bg-danger';
                                    break;
                                case 'Received':
                                    $badge_class = 'badge bg-info';
                                    break;
                                default:
                                    $badge_class = 'badge bg-secondary';
                            }
                            
                            echo "<span class='$badge_class'>$badge_text</span>";
                            ?>
                        </td>
                        <td>RM <?php echo number_format($row['total_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['created_by']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="view-po.php?po=<?php echo $row['po_number']; ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if (!$isViewOnly && $_SESSION['role'] !== 'Driver' && ($row['status'] === 'Pending' || $row['status'] === 'Received')): ?>
                                    <a href="edit-po.php?po=<?php echo $row['po_number']; ?>" class="btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <select class="form-select status-select" onchange="updateStatus(this, '<?php echo $row['po_number']; ?>')">
                                        <option value="">Change Status</option>
                                        <option value="Completed">Completed</option>
                                        <option value="Cancelled">Cancelled</option>
                                        <option value="Received">Received</option>
                                    </select>
                                    <button onclick="deletePO('<?php echo $row['po_number']; ?>')" class="btn-delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create PO Modal -->
<div id="createPOModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Create Purchase Order</h2>
            <button type="button" onclick="closeCreatePOModal()" class="close-btn">&times;</button>
        </div>
        <form id="poForm" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="supplier_name">Supplier *</label>
                    <select name="supplier_name" id="supplier_name" class="form-control" required>
                        <option value="">Select Supplier</option>
                        <?php while ($supplier = $suppliers_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($supplier['company_name']); ?>">
                                <?php echo htmlspecialchars($supplier['company_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="delivery_address">Delivery Address *</label>
                    <textarea name="delivery_address" id="delivery_address" class="form-control" rows="3" required placeholder="Enter complete delivery address"></textarea>
                </div>
                <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-control" rows="3" placeholder="Enter any additional notes or remarks"></textarea>
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
                        <tr>
                            <td>
                                <select name="items[0][bar_code]" class="form-control" required>
                                    <option value="">Select Item</option>
                                    <?php foreach ($items as $item): ?>
                                        <option value="<?php echo htmlspecialchars($item['bar_code']); ?>">
                                            <?php echo htmlspecialchars($item['inventory_item']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" name="items[0][quantity]" class="form-control" min="1" required onchange="calculateTotal(this.parentElement.parentElement)" onkeyup="calculateTotal(this.parentElement.parentElement)"></td>
                            <td><input type="number" name="items[0][unit_price]" class="form-control" min="0.01" step="0.01" required onchange="calculateTotal(this.parentElement.parentElement)" onkeyup="calculateTotal(this.parentElement.parentElement)"></td>
                            <td><span class="row-total">0.00</span></td>
                            <td><button type="button" class="btn-delete" onclick="removeItem(this)"><i class="fas fa-trash"></i></button></td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="btn-add-item" onclick="addItem()">
                    <i class="fas fa-plus"></i> Add Item
                </button>
                <div class="grand-total-section">
                    <strong>Grand Total: RM </strong>
                    <span id="grandTotal">0.00</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeCreatePOModal()">Cancel</button>
                <button type="submit" name="create_po" class="btn-create">Create PO</button>
            </div>
        </form>
    </div>
</div>

<script>
// Store inventory items for dropdown
const inventoryItems = <?php echo json_encode($items); ?>;

function openCreatePOModal() {
    document.getElementById('createPOModal').style.display = 'block';
    // Add one empty row by default
    if (document.getElementById('itemsTableBody').children.length === 0) {
        addItem();
    }
}

function closeCreatePOModal() {
    document.getElementById('createPOModal').style.display = 'none';
    document.getElementById('poForm').reset();
    document.getElementById('itemsTableBody').innerHTML = '';
}

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
            <input type="number" name="items[${rowCount}][quantity]" class="form-control" min="1" required onchange="calculateTotal(this.parentElement.parentElement)" onkeyup="calculateTotal(this.parentElement.parentElement)">
        </td>
        <td>
            <input type="number" name="items[${rowCount}][unit_price]" class="form-control" min="0.01" step="0.01" required onchange="calculateTotal(this.parentElement.parentElement)" onkeyup="calculateTotal(this.parentElement.parentElement)">
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

// Add event listeners to initial inputs
document.addEventListener('DOMContentLoaded', function() {
    const initialRow = document.querySelector('#itemsTableBody tr');
    if (initialRow) {
        const quantityInput = initialRow.querySelector('input[name*="[quantity]"]');
        const unitPriceInput = initialRow.querySelector('input[name*="[unit_price]"]');
        
        if (quantityInput && unitPriceInput) {
            quantityInput.addEventListener('input', () => calculateTotal(initialRow));
            unitPriceInput.addEventListener('input', () => calculateTotal(initialRow));
        }
    }
});

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target == document.getElementById('createPOModal')) {
        closeCreatePOModal();
    }
}

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

function deletePO(poNumber) {
    if (confirm('Are you sure you want to delete this Purchase Order? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'purchase-orders.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_po';
        input.value = '1';
        form.appendChild(input);
        
        const poInput = document.createElement('input');
        poInput.type = 'hidden';
        poInput.name = 'po_number';
        poInput.value = poNumber;
        form.appendChild(poInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function updateStatus(selectElement, poNumber) {
    const newStatus = selectElement.value;
    if (!newStatus) return;

    if (confirm('Are you sure you want to update the status to ' + newStatus + '?')) {
        const formData = new FormData();
        formData.append('update_status', '1');
        formData.append('po_number', poNumber);
        formData.append('status', newStatus);

        fetch('purchase-orders.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(() => {
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating status. Please try again.');
        });
    } else {
        selectElement.value = ''; // Reset to default if cancelled
    }
}
</script>

<?php $mysqli->close(); ?> 