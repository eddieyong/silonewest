<?php
session_start();
require_once 'functions.php';

// Check if user is logged in and has Admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin-login.html");
    exit();
}

$mysqli = new mysqli("localhost", "root", "", "fyp");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Handle DO submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_do'])) {
    $do_number = 'DO-' . date('Ymd') . '-' . sprintf('%04d', rand(0, 9999));
    $po_number = $_POST['po_number'];
    $delivery_date = $_POST['delivery_date'];
    $recipient_company = $_POST['recipient_company'];
    $delivery_address = $_POST['delivery_address'];
    $contact_person = $_POST['contact_person'];
    $contact_number = $_POST['contact_number'];
    $vehicle_number = $_POST['vehicle_number'];
    $driver_name = $_POST['driver_name'];
    $driver_contact = $_POST['driver_contact'];
    $remarks = $_POST['remarks'];
    
    // Start transaction
    $mysqli->begin_transaction();
    
    try {
        // Insert DO header
        $stmt = $mysqli->prepare("INSERT INTO delivery_orders (do_number, po_number, delivery_date, recipient_company, delivery_address, contact_person, contact_number, vehicle_number, driver_name, driver_contact, remarks, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
        $stmt->bind_param("ssssssssssss", $do_number, $po_number, $delivery_date, $recipient_company, $delivery_address, $contact_person, $contact_number, $vehicle_number, $driver_name, $driver_contact, $remarks, $_SESSION['username']);
        $stmt->execute();
        
        // Get items from PO and insert into DO items
        $stmt = $mysqli->prepare("SELECT bar_code, quantity FROM po_items WHERE po_number = ?");
        $stmt->bind_param("s", $po_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($item = $result->fetch_assoc()) {
            $stmt = $mysqli->prepare("INSERT INTO do_items (do_number, bar_code, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $do_number, $item['bar_code'], $item['quantity']);
            $stmt->execute();
        }
        
        // Log the activity
        $description = "Created new Delivery Order (DO: $do_number, PO: $po_number)";
        logActivity($mysqli, 'delivery_order', $description);
        
        $mysqli->commit();
        $_SESSION['success_msg'] = "Delivery Order created successfully!";
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error_msg'] = "Error creating Delivery Order: " . $e->getMessage();
    }
    
    header("Location: delivery-orders.php");
    exit();
}

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $do_number = $_POST['do_number'];
    $new_status = $_POST['status'];
    
    // Start transaction
    $mysqli->begin_transaction();
    
    try {
        // Get current status for logging
        $stmt = $mysqli->prepare("SELECT status FROM delivery_orders WHERE do_number = ?");
        $stmt->bind_param("s", $do_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_status = $result->fetch_assoc()['status'];
        
        // Update status
        $stmt = $mysqli->prepare("UPDATE delivery_orders SET status = ? WHERE do_number = ?");
        $stmt->bind_param("ss", $new_status, $do_number);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to update status.");
        }
        
        // Log the activity
        $description = "Updated Delivery Order status (DO: $do_number) from $current_status to $new_status";
        logActivity($mysqli, 'delivery_order', $description);
        
        $mysqli->commit();
        $_SESSION['success_msg'] = "Status updated successfully!";
        http_response_code(200);
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error_msg'] = "Error updating status: " . $e->getMessage();
        http_response_code(500);
    }
    
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header("Location: delivery-orders.php");
        exit();
    }
}

// Handle DO deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_do'])) {
    $do_number = $_POST['do_number'];
    
    // Start transaction
    $mysqli->begin_transaction();
    
    try {
        // Delete DO items first (foreign key constraint)
        $stmt = $mysqli->prepare("DELETE FROM do_items WHERE do_number = ?");
        $stmt->bind_param("s", $do_number);
        $stmt->execute();
        
        // Delete DO header
        $stmt = $mysqli->prepare("DELETE FROM delivery_orders WHERE do_number = ?");
        $stmt->bind_param("s", $do_number);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to delete Delivery Order.");
        }
        
        // Log the activity
        $description = "Deleted Delivery Order (DO: $do_number)";
        logActivity($mysqli, 'delivery_order', $description);
        
        $mysqli->commit();
        $_SESSION['success_msg'] = "Delivery Order deleted successfully!";
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error_msg'] = "Error deleting Delivery Order: " . $e->getMessage();
    }
    
    header("Location: delivery-orders.php");
    exit();
}

// Get all vehicles for dropdown
$vehicles_query = "SELECT vehicle_number FROM vehicles ORDER BY vehicle_number";
$vehicles_result = $mysqli->query($vehicles_query);

// Get all PO numbers for dropdown
$po_query = "SELECT po_number FROM purchase_orders WHERE status = 'Completed' ORDER BY created_at DESC";
$po_result = $mysqli->query($po_query);

// Get all delivery orders with supplier name
$query = "SELECT do.*, po.supplier_name 
          FROM delivery_orders do 
          LEFT JOIN purchase_orders po ON do.po_number = po.po_number 
          ORDER BY do.created_at DESC";
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

    .action-buttons {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .btn-view {
        background: #e3f2fd;
        color: #1976d2;
        padding: 6px 12px;
        border-radius: 4px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.9rem;
    }

    .btn-view:hover {
        background: #c8e6ff;
        color: #0056b3;
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
        background: #5c1f00;
        color: white;
        border-radius: 10px 10px 0 0;
    }

    .modal-title {
        margin: 0;
        font-size: 1.25rem;
    }

    .close-btn {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.3s;
    }

    .close-btn:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .modal-body {
        padding: 20px;
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

    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }

    .modal-footer {
        padding: 20px;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn-cancel {
        background: #6c757d;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-cancel:hover {
        background: #5a6268;
    }

    .btn-create {
        background: #5c1f00;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-create:hover {
        background: #7a2900;
    }

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
    }

    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .items-table th,
    .items-table td {
        padding: 12px;
        border: 1px solid #ddd;
        text-align: left;
    }

    .items-table th {
        background: #f8f9fa;
        font-weight: 600;
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
        text-decoration: none;
    }

    .btn-delete:hover {
        background: #ffcdd2;
        color: #b71c1c;
    }

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
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Delivery Orders</h1>
        <button class="btn-primary" onclick="openCreateDOModal()">
            <i class="fas fa-plus"></i> Create New DO
        </button>
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
                    <th>DO Number</th>
                    <th>PO Number</th>
                    <th>Delivery Date</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Recipient Company</th>
                    <th>Contact Person</th>
                    <th>Contact Number</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['do_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['po_number']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['delivery_date'])); ?></td>
                        <td>
                            <?php
                            $status = trim($row['status']);
                            $badge_class = '';
                            switch($status) {
                                case 'Pending':
                                    $badge_class = 'badge bg-warning';
                                    break;
                                case 'Completed':
                                    $badge_class = 'badge bg-success';
                                    break;
                                case 'Cancelled':
                                    $badge_class = 'badge bg-danger';
                                    break;
                            }
                            echo "<span class='$badge_class'>" . htmlspecialchars($status) . "</span>";
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['created_by']); ?></td>
                        <td><?php echo htmlspecialchars($row['recipient_company']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact_person']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="view-do.php?do=<?php echo $row['do_number']; ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if ($row['status'] === 'Pending'): ?>
                                    <select class="form-select status-select" onchange="updateStatus(this, '<?php echo $row['do_number']; ?>')">
                                        <option value="">Change Status</option>
                                        <option value="Completed">Completed</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                <?php endif; ?>
                                <button onclick="deleteDO('<?php echo $row['do_number']; ?>')" class="btn-delete">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create DO Modal -->
<div id="createDOModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Create Delivery Order</h2>
            <button type="button" onclick="closeCreateDOModal()" class="close-btn">&times;</button>
        </div>
        <form id="doForm" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="po_number">Purchase Order Number *</label>
                    <select name="po_number" id="po_number" class="form-control" required>
                        <option value="">Select PO Number</option>
                        <?php while ($po = $po_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($po['po_number']); ?>">
                                <?php echo htmlspecialchars($po['po_number']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="delivery_date">Delivery Date *</label>
                    <input type="date" name="delivery_date" id="delivery_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="recipient_company">Recipient Company *</label>
                    <input type="text" name="recipient_company" id="recipient_company" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="delivery_address">Delivery Address *</label>
                    <textarea name="delivery_address" id="delivery_address" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="contact_person">Contact Person *</label>
                    <input type="text" name="contact_person" id="contact_person" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="contact_number">Contact Number *</label>
                    <input type="text" name="contact_number" id="contact_number" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="vehicle_number">Vehicle Number</label>
                    <select name="vehicle_number" id="vehicle_number" class="form-control">
                        <option value="">Select Vehicle Number</option>
                        <?php while ($vehicle = $vehicles_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($vehicle['vehicle_number']); ?>">
                                <?php echo htmlspecialchars($vehicle['vehicle_number']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="driver_name">Driver Name</label>
                    <input type="text" name="driver_name" id="driver_name" class="form-control">
                </div>
                <div class="form-group">
                    <label for="driver_contact">Driver Contact</label>
                    <input type="text" name="driver_contact" id="driver_contact" class="form-control">
                </div>
                <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-control" rows="3" placeholder="Enter any additional notes or remarks"></textarea>
                </div>
                <div id="items_container">
                    <h3>Items</h3>
                    <div id="po_items">
                        <!-- Items will be loaded here when PO is selected -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeCreateDOModal()">Cancel</button>
                <button type="submit" name="create_do" class="btn-create">Create DO</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function openCreateDOModal() {
    document.getElementById('createDOModal').style.display = 'block';
}

function closeCreateDOModal() {
    document.getElementById('createDOModal').style.display = 'none';
    document.getElementById('doForm').reset();
    document.getElementById('po_items').innerHTML = '';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('createDOModal');
    if (event.target == modal) {
        closeCreateDOModal();
    }
}

$(document).ready(function() {
    // When PO number is selected, fetch its items
    $('#po_number').change(function() {
        const poNumber = $(this).val();
        if (poNumber) {
            $.ajax({
                url: 'get-po-items.php',
                type: 'GET',
                data: { po_number: poNumber },
                success: function(response) {
                    $('#po_items').html(response);
                },
                error: function(xhr, status, error) {
                    $('#po_items').html('<p class="error-message">Error loading items: ' + error + '</p>');
                }
            });
        } else {
            $('#po_items').html('');
        }
    });
});

function updateStatus(selectElement, doNumber) {
    const newStatus = selectElement.value;
    if (!newStatus) return;

    if (confirm('Are you sure you want to update the status to ' + newStatus + '?')) {
        const formData = new FormData();
        formData.append('update_status', '1');
        formData.append('do_number', doNumber);
        formData.append('status', newStatus);

        fetch('delivery-orders.php', {
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

function deleteDO(doNumber) {
    if (confirm('Are you sure you want to delete this Delivery Order? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delivery-orders.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_do';
        input.value = '1';
        form.appendChild(input);
        
        const doInput = document.createElement('input');
        doInput.type = 'hidden';
        doInput.name = 'do_number';
        doInput.value = doNumber;
        form.appendChild(doInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php $mysqli->close(); ?>