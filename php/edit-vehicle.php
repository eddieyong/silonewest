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

$id = isset($_GET['id']) ? $_GET['id'] : 0;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vehicle_number = $_POST['vehicle_number'];
    $insurance_expiry = $_POST['insurance_expiry'];
    $roadtax_expiry = $_POST['roadtax_expiry'];
    $description = $_POST['description'];
    $gps = $_POST['gps'] ?: NULL;
    $remarks = $_POST['remarks'] ?: NULL;

    $stmt = $mysqli->prepare("UPDATE vehicles SET vehicle_number=?, insurance_expiry=?, roadtax_expiry=?, description=?, gps=?, remarks=? WHERE id=?");
    $stmt->bind_param("ssssssi", $vehicle_number, $insurance_expiry, $roadtax_expiry, $description, $gps, $remarks, $id);
    
    if ($stmt->execute()) {
        logActivity($mysqli, 'system', "Updated vehicle: $vehicle_number");
        $_SESSION['success_msg'] = "Vehicle updated successfully!";
    } else {
        $_SESSION['error_msg'] = "Error updating vehicle: " . $mysqli->error;
    }
    
    header("Location: view-vehicles.php");
    exit();
}

// Get vehicle data
$stmt = $mysqli->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$vehicle = $result->fetch_assoc();

if (!$vehicle) {
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
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Edit Vehicle</h1>
    </div>

    <div class="form-container">
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label for="vehicle_number">Vehicle Number</label>
                    <input type="text" id="vehicle_number" name="vehicle_number" class="form-control" 
                           value="<?php echo htmlspecialchars($vehicle['vehicle_number']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" class="form-control" 
                           value="<?php echo htmlspecialchars($vehicle['description']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="insurance_expiry">Insurance Expiry Date</label>
                    <input type="date" id="insurance_expiry" name="insurance_expiry" class="form-control" 
                           value="<?php echo $vehicle['insurance_expiry']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="roadtax_expiry">Road Tax Expiry Date</label>
                    <input type="date" id="roadtax_expiry" name="roadtax_expiry" class="form-control" 
                           value="<?php echo $vehicle['roadtax_expiry']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="gps">GPS</label>
                    <input type="text" id="gps" name="gps" class="form-control" 
                           value="<?php echo htmlspecialchars($vehicle['gps']); ?>">
                </div>

                <div class="form-group remarks-field">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" class="form-control" rows="4"><?php echo htmlspecialchars($vehicle['remarks']); ?></textarea>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" class="submit-btn">Update Vehicle</button>
                <a href="view-vehicles.php" class="cancel-btn">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php $mysqli->close(); ?> 