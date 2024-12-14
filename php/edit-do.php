<?php
session_start();
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../admin-login.html");
    exit();
}

// Check if user has permission to access Delivery Orders
if (!in_array($_SESSION['role'], ['Admin', 'Storekeeper'])) {
    header("Location: admin.php");
    exit();
}

// Check if DO number is provided
if (!isset($_GET['do'])) {
    header("Location: delivery-orders.php");
    exit();
}

$do_number = $_GET['do'];

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get DO details
$query = "SELECT do.*, po.supplier_name 
          FROM delivery_orders do 
          LEFT JOIN purchase_orders po ON do.po_number = po.po_number 
          WHERE do.do_number = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $do_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: delivery-orders.php");
    exit();
}

$do_details = $result->fetch_assoc();

// Check if DO is editable (only Pending status)
if ($do_details['status'] !== 'Pending') {
    $_SESSION['error_msg'] = "Only pending delivery orders can be edited.";
    header("Location: delivery-orders.php");
    exit();
}

// Get all vehicles for dropdown
$vehicles_query = "SELECT vehicle_number FROM vehicles ORDER BY vehicle_number";
$vehicles_result = $mysqli->query($vehicles_query);

// Get all drivers for dropdown
$drivers_query = "SELECT username, contact FROM admin WHERE role = 'Driver' ORDER BY username";
$drivers_result = $mysqli->query($drivers_query);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_do'])) {
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
        // Update DO
        $stmt = $mysqli->prepare("UPDATE delivery_orders SET 
            delivery_date = ?,
            recipient_company = ?,
            delivery_address = ?,
            contact_person = ?,
            contact_number = ?,
            vehicle_number = ?,
            driver_name = ?,
            driver_contact = ?,
            remarks = ?
            WHERE do_number = ?");
        
        $stmt->bind_param("ssssssssss", 
            $delivery_date,
            $recipient_company,
            $delivery_address,
            $contact_person,
            $contact_number,
            $vehicle_number,
            $driver_name,
            $driver_contact,
            $remarks,
            $do_number
        );
        
        $stmt->execute();
        
        if ($stmt->affected_rows === 0 && $stmt->errno !== 0) {
            throw new Exception("Failed to update Delivery Order.");
        }
        
        // Log the activity
        $description = "Updated Delivery Order (DO: $do_number)";
        logActivity($mysqli, 'delivery_order', $description);
        
        $mysqli->commit();
        $_SESSION['success_msg'] = "Delivery Order updated successfully!";
        header("Location: delivery-orders.php");
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error_msg'] = "Error updating Delivery Order: " . $e->getMessage();
    }
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
    
    .form-control[readonly] {
        background-color: #f8f9fa;
    }
    
    textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }
    
    .btn-back {
        background: #6c757d;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-back:hover {
        background: #5a6268;
        color: white;
        text-decoration: none;
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
    
    .btn-save:hover {
        background: #7a2900;
    }
    
    .form-buttons {
        display: flex;
        justify-content: space-between;
        margin-top: 30px;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit Delivery Order</h1>
    </div>

    <div class="form-container">
        <form method="POST">
            <div class="form-group">
                <label>DO Number</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($do_details['do_number']); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>PO Number</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($do_details['po_number']); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label for="delivery_date">Delivery Date *</label>
                <input type="date" name="delivery_date" id="delivery_date" class="form-control" value="<?php echo date('Y-m-d', strtotime($do_details['delivery_date'])); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="recipient_company">Recipient Company *</label>
                <input type="text" name="recipient_company" id="recipient_company" class="form-control" value="<?php echo htmlspecialchars($do_details['recipient_company']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="delivery_address">Delivery Address *</label>
                <textarea name="delivery_address" id="delivery_address" class="form-control" required><?php echo htmlspecialchars($do_details['delivery_address']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="contact_person">Contact Person *</label>
                <input type="text" name="contact_person" id="contact_person" class="form-control" value="<?php echo htmlspecialchars($do_details['contact_person']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="contact_number">Contact Number *</label>
                <input type="text" name="contact_number" id="contact_number" class="form-control" value="<?php echo htmlspecialchars($do_details['contact_number']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="vehicle_number">Vehicle Number</label>
                <select name="vehicle_number" id="vehicle_number" class="form-control">
                    <option value="">Select Vehicle Number</option>
                    <?php 
                    $vehicles_result->data_seek(0);
                    while ($vehicle = $vehicles_result->fetch_assoc()): 
                        $selected = ($vehicle['vehicle_number'] === $do_details['vehicle_number']) ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($vehicle['vehicle_number']); ?>" <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($vehicle['vehicle_number']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="driver_name">Driver Name</label>
                <select name="driver_name" id="driver_name" class="form-control" onchange="updateDriverContact(this.value)">
                    <option value="">Select Driver</option>
                    <?php 
                    $drivers_result->data_seek(0);
                    while ($driver = $drivers_result->fetch_assoc()): 
                        $selected = ($driver['username'] === $do_details['driver_name']) ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($driver['username']); ?>" 
                                data-contact="<?php echo htmlspecialchars($driver['contact']); ?>"
                                <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($driver['username']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="driver_contact">Driver Contact</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" name="driver_contact" id="driver_contact" class="form-control" value="<?php echo htmlspecialchars($do_details['driver_contact']); ?>" readonly>
                    <button type="button" class="btn-edit" onclick="toggleDriverContactEdit()" style="padding: 8px 15px; background: #e3f2fd; color: #1976d2; border: none; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="remarks">Remarks</label>
                <textarea name="remarks" id="remarks" class="form-control" rows="3"><?php echo htmlspecialchars($do_details['remarks']); ?></textarea>
            </div>
            
            <div class="form-buttons">
                <a href="delivery-orders.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <button type="submit" name="update_do" class="btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateDriverContact(driverName) {
    const driverSelect = document.getElementById('driver_name');
    const selectedOption = driverSelect.options[driverSelect.selectedIndex];
    const driverContact = selectedOption.getAttribute('data-contact');
    document.getElementById('driver_contact').value = driverContact || '';
}

function toggleDriverContactEdit() {
    const driverContactInput = document.getElementById('driver_contact');
    driverContactInput.readOnly = !driverContactInput.readOnly;
    if (!driverContactInput.readOnly) {
        driverContactInput.focus();
    }
}
</script>

<?php $mysqli->close(); ?> 