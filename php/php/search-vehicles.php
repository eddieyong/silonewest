<?php
session_start();
require_once 'functions.php';

// Check if user is logged in and has Admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    exit('Unauthorized');
}

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    exit('Connection failed: ' . $mysqli->connect_error);
}

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

while($row = $result->fetch_assoc()): 
    $today = new DateTime();
    $insurance_expiry = new DateTime($row['insurance_expiry']);
    $roadtax_expiry = new DateTime($row['roadtax_expiry']);
    
    $insurance_diff = $today->diff($insurance_expiry)->days;
    $roadtax_diff = $today->diff($roadtax_expiry)->days;
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
        <td><?php echo htmlspecialchars($row['gps'] ?: '-'); ?></td>
        <td><?php echo htmlspecialchars($row['remarks'] ?: '-'); ?></td>
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
<?php endwhile;

$mysqli->close();
?> 