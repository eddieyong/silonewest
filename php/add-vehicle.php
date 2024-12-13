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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vehicle_number = $_POST['vehicle_number'];
    $insurance_expiry = $_POST['insurance_expiry'];
    $roadtax_expiry = $_POST['roadtax_expiry'];
    $puspakom_expiry = $_POST['puspakom_expiry'] ?: NULL;
    $description = $_POST['description'];
    $gps = $_POST['gps'] ?: NULL;
    $remarks = $_POST['remarks'] ?: NULL;

    $stmt = $mysqli->prepare("INSERT INTO vehicles (vehicle_number, insurance_expiry, roadtax_expiry, puspakom_expiry, description, gps, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $vehicle_number, $insurance_expiry, $roadtax_expiry, $puspakom_expiry, $description, $gps, $remarks);
    
    if ($stmt->execute()) {
        // Log the activity with detailed information
        $details = array(
            "Vehicle Number: $vehicle_number",
            "Description: $description",
            "Insurance Expiry: $insurance_expiry",
            "Road Tax Expiry: $roadtax_expiry",
            "PUSPAKOM Expiry: " . ($puspakom_expiry ?: 'Not Set')
        );
        if ($gps) $details[] = "GPS: $gps";
        if ($remarks) $details[] = "Remarks: $remarks";
        
        $activityLog = "Added new vehicle with details: " . implode(", ", $details);
        logActivity($mysqli, 'vehicle', $activityLog);
        
        $_SESSION['vehicle_success_msg'] = "Vehicle added successfully!";
    } else {
        $_SESSION['vehicle_error_msg'] = "Error adding vehicle: " . $mysqli->error;
    }
    
    header("Location: view-vehicles.php");
    exit();
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

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
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
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-control:focus {
    border-color: #5c1f00;
    outline: none;
}

.submit-btn {
    background: #5c1f00;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1rem;
    transition: background-color 0.3s;
}

.submit-btn:hover {
    background: #7a2900;
}

.remarks-field {
    grid-column: span 2;
}

.button-group {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.cancel-btn {
    background: #6c757d;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1rem;
    text-decoration: none;
    transition: opacity 0.3s;
}

.cancel-btn:hover {
    opacity: 0.9;
    color: white;
}

.form-text {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 4px;
}
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Add New Vehicle</h1>
        <div class="header-buttons" style="margin-left: auto;">
            <button type="submit" class="submit-btn" style="margin-left: auto;">
                <i class="fas fa-plus"></i> Add New Vehicle
            </button>
        </div>
    </div>

    <div class="form-container">
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label for="vehicle_number">Vehicle Number</label>
                    <input type="text" id="vehicle_number" name="vehicle_number" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="insurance_expiry">Insurance Expiry Date</label>
                    <input type="date" id="insurance_expiry" name="insurance_expiry" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="roadtax_expiry">Road Tax Expiry Date</label>
                    <input type="date" id="roadtax_expiry" name="roadtax_expiry" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="puspakom_expiry">PUSPAKOM Expiry Date</label>
                    <input type="date" id="puspakom_expiry" name="puspakom_expiry" class="form-control">
                    <div class="form-text">Leave empty if not applicable</div>
                </div>

                <div class="form-group">
                    <label for="gps">GPS</label>
                    <input type="text" id="gps" name="gps" class="form-control">
                </div>

                <div class="form-group remarks-field">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" class="form-control" rows="4"></textarea>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" class="submit-btn">Add Vehicle</button>
                <a href="view-vehicles.php" class="cancel-btn">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php $mysqli->close(); ?>