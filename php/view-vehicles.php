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

// Handle vehicle deletion
if (isset($_POST['delete_vehicle'])) {
    $vehicle_id = $_POST['vehicle_id'];
    
    // Get vehicle details before deletion
    $stmt = $mysqli->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $vehicle = $result->fetch_assoc();
    
    // Delete the vehicle
    $stmt = $mysqli->prepare("DELETE FROM vehicles WHERE id = ?");
    $stmt->bind_param("i", $vehicle_id);
    
    if ($stmt->execute()) {
        // Log the activity with detailed information
        $details = array(
            "Vehicle Number: {$vehicle['vehicle_number']}",
            "Description: {$vehicle['description']}",
            "Insurance Expiry: {$vehicle['insurance_expiry']}",
            "Road Tax Expiry: {$vehicle['roadtax_expiry']}"
        );
        if ($vehicle['gps']) $details[] = "GPS: {$vehicle['gps']}";
        if ($vehicle['remarks']) $details[] = "Remarks: {$vehicle['remarks']}";
        
        $activityLog = "Deleted vehicle with details: " . implode(", ", $details);
        logActivity($mysqli, 'vehicle', $activityLog);
        
        $_SESSION['vehicle_success_msg'] = "Vehicle deleted successfully!";
    } else {
        $_SESSION['vehicle_error_msg'] = "Error deleting vehicle.";
    }
    
    header("Location: view-vehicles.php");
    exit();
}

// Get all vehicles
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT * FROM vehicles";

if (!empty($search)) {
    $search = "%$search%";
    $query .= " WHERE vehicle_number LIKE ? OR description LIKE ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $mysqli->query($query . " ORDER BY vehicle_number");
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
}

.add-vehicle-btn {
    padding: 8px 16px;
    border-radius: 5px;
    text-decoration: none;
    color: white;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: opacity 0.3s;
    background: #5c1f00;
}

.add-vehicle-btn:hover {
    opacity: 0.9;
    color: white;
}

.export-btn {
    padding: 8px 16px;
    border-radius: 5px;
    text-decoration: none;
    color: white;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: opacity 0.3s;
}

.export-btn pdf:hover {
    opacity: 0.9;
    color: white;
}

.pdf {
    background: #ff0000;
}

.message {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.search-bar {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
}

.search-bar input {
    flex: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
}

.search-bar button {
    padding: 10px 20px;
    background: #0066cc;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.search-bar button:hover {
    background: #0052a3;
}

.vehicles-table {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
    overflow-x: auto;
}

.vehicles-table table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
}

.vehicles-table th,
.vehicles-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.vehicles-table th {
    background: #f8f9fa;
    font-weight: 500;
    color: #333;
}

.vehicles-table tr:last-child td {
    border-bottom: none;
}

.action-buttons {
    display: flex;
    gap: 10px;
}

.edit-btn,
.delete-btn {
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    color: white;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: opacity 0.3s;
}

.edit-btn {
    background: #0066cc;
}

.delete-btn {
    background: #dc3545;
    border: none;
    cursor: pointer;
}

.edit-btn:hover,
.delete-btn:hover {
    opacity: 0.9;
    color: white;
}

.expiry-warning {
    color: #dc3545;
    font-weight: 500;
}

.expiry-soon {
    color: #ffc107;
    font-weight: 500;
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
        <h1 class="page-title">Manage Vehicles</h1>
        <div class="header-buttons">
            <a href="export-vehicles-pdf.php" class="export-btn pdf">
                <i class="fas fa-file-pdf"></i> Export to PDF
            </a>
            <a href="export-vehicles-excel.php" class="export-btn excel">
                <i class="fas fa-file-excel"></i> Export to Excel
            </a>
            <a href="add-vehicle.php" class="add-vehicle-btn" style="margin-left: 10px;">
                <i class="fas fa-plus"></i> Add New Vehicle
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['vehicle_success_msg'])): ?>
        <div class="message success">
            <?php 
                echo $_SESSION['vehicle_success_msg'];
                unset($_SESSION['vehicle_success_msg']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['vehicle_error_msg'])): ?>
        <div class="message error">
            <?php 
                echo $_SESSION['vehicle_error_msg'];
                unset($_SESSION['vehicle_error_msg']);
            ?>
        </div>
    <?php endif; ?>

    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by vehicle number or description..." 
                value="<?php echo htmlspecialchars($search); ?>">
        <button type="button">
            <i class="fas fa-search"></i> Search
        </button>
    </div>

    <div class="vehicles-table">
        <table>
            <thead>
                <tr>
                    <th>Vehicle Number</th>
                    <th>Description</th>
                    <th>Insurance Expiry</th>
                    <th>Road Tax Expiry</th>
                    <th>PUSPAKOM Expiry</th>
                    <th>GPS</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php 
                while($row = $result->fetch_assoc()): 
                    $today = new DateTime();
                    $insurance_expiry = new DateTime($row['insurance_expiry']);
                    $roadtax_expiry = new DateTime($row['roadtax_expiry']);
                    $puspakom_expiry = new DateTime($row['puspakom_expiry']);
                    
                    $insurance_diff = $today->diff($insurance_expiry)->days;
                    $roadtax_diff = $today->diff($roadtax_expiry)->days;
                    $puspakom_diff = $today->diff($puspakom_expiry)->days;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['vehicle_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td class="<?php echo ($insurance_diff < 30) ? ($insurance_diff < 7 ? 'expiry-warning' : 'expiry-soon') : ''; ?>">
                            <?php echo date('d/m/Y', strtotime($row['insurance_expiry'])); ?>
                        </td>
                        <td class="<?php echo ($roadtax_diff < 30) ? ($roadtax_diff < 7 ? 'expiry-warning' : 'expiry-soon') : ''; ?>">
                            <?php echo date('d/m/Y', strtotime($row['roadtax_expiry'])); ?>
                        </td>
                        <td class="<?php echo ($puspakom_diff < 30) ? ($puspakom_diff < 7 ? 'expiry-warning' : 'expiry-soon') : ''; ?>">
                            <?php echo $row['puspakom_expiry'] ? date('d/m/Y', strtotime($row['puspakom_expiry'])) : '<span style="color: #333;">-</span>'; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['gps']) ?: '<span style="color: #333;">-</span>'; ?></td>
                        <td><?php echo htmlspecialchars($row['remarks']) ?: '<span style="color: #333;">-</span>'; ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit-vehicle.php?id=<?php echo $row['id']; ?>" class="edit-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this vehicle?')">
                                    <input type="hidden" name="vehicle_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="delete_vehicle" class="delete-btn">
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
    xhr.open('GET', `search-vehicles.php?search=${encodeURIComponent(searchValue)}`, true);
    
    xhr.onload = function() {
        if (this.status === 200) {
            document.getElementById('tableBody').innerHTML = this.responseText;
        }
    };
    
    xhr.send();
}

// Add event listener with debounce
document.getElementById('searchInput').addEventListener('input', debounce(performSearch, 300));
</script>

<?php $mysqli->close(); ?> 