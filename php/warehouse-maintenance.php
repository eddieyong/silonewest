<?php
session_start();

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

// Handle maintenance record deletion
if (isset($_POST['delete_record'])) {
    $id = (int)$_POST['record_id'];
    if ($mysqli->query("DELETE FROM warehouse_maintenance WHERE id = $id")) {
        $_SESSION['maintenance_success_msg'] = "Record deleted successfully!";
    } else {
        $_SESSION['maintenance_error_msg'] = "Error deleting record: " . $mysqli->error;
    }
    header("Location: warehouse-maintenance.php");
    exit();
}

// Get all maintenance records
$result = $mysqli->query("SELECT * FROM warehouse_maintenance ORDER BY next_service_date ASC");

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

.add-btn {
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

.add-btn:hover {
    opacity: 0.9;
    color: white;
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

.maintenance-table {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
    overflow-x: auto;
}

.maintenance-table table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
}

.maintenance-table th,
.maintenance-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.maintenance-table th {
    background: #f8f9fa;
    font-weight: 500;
    color: #333;
}

.maintenance-table tr:last-child td {
    border-bottom: none;
}

.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-expired {
    background: #f8d7da;
    color: #721c24;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
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

.type-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 500;
}

.type-fire_extinguisher {
    background: #ffebee;
    color: #c62828;
}

.type-pest_control {
    background: #e8f5e9;
    color: #2e7d32;
}

.type-insurance {
    background: #e3f2fd;
    color: #1565c0;
}

.expiry-warning {
    color: #dc3545;
    font-weight: 500;
}

.expiry-soon {
    color: #ffc107;
    font-weight: 500;
}
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Warehouse Maintenance</h1>
        <div class="header-buttons">
            <a href="add-maintenance.php" class="add-btn">
                <i class="fas fa-plus"></i> Add New Record
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['maintenance_success_msg'])): ?>
        <div class="message success">
            <?php 
                echo $_SESSION['maintenance_success_msg'];
                unset($_SESSION['maintenance_success_msg']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['maintenance_error_msg'])): ?>
        <div class="message error">
            <?php 
                echo $_SESSION['maintenance_error_msg'];
                unset($_SESSION['maintenance_error_msg']);
            ?>
        </div>
    <?php endif; ?>

    <div class="maintenance-table">
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Last Service Date</th>
                    <th>Next Service Date</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($result && $result->num_rows > 0):
                    while($row = $result->fetch_assoc()): 
                        $today = new DateTime();
                        $next_service = new DateTime($row['next_service_date']);
                        $days_remaining = $today->diff($next_service)->days;
                        $is_expired = $today > $next_service;
                ?>
                    <tr>
                        <td>
                            <span class="type-badge type-<?php echo htmlspecialchars($row['type']); ?>">
                                <?php echo ucwords(str_replace('_', ' ', $row['type'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($row['last_service_date'])); ?></td>
                        <td class="<?php echo $is_expired ? 'expiry-warning' : ($days_remaining <= 30 ? 'expiry-soon' : ''); ?>">
                            <?php echo date('d/m/Y', strtotime($row['next_service_date'])); ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['provider']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $row['status']; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($row['remarks']) ?: '<span style="color: #333;">-</span>'; ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit-maintenance.php?id=<?php echo $row['id']; ?>" class="edit-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this record?')">
                                    <input type="hidden" name="record_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="delete_record" class="delete-btn">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php 
                    endwhile;
                else:
                ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #666;">
                            <i class="fas fa-info-circle"></i> No maintenance records found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $mysqli->close(); ?> 