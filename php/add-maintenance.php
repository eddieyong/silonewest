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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['type'];
    $last_service_date = $_POST['last_service_date'];
    $next_service_date = $_POST['next_service_date'];
    $provider = $_POST['provider'];
    $status = $_POST['status'];
    $remarks = $_POST['remarks'] ?: NULL;

    $stmt = $mysqli->prepare("INSERT INTO warehouse_maintenance (type, last_service_date, next_service_date, provider, status, remarks) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $type, $last_service_date, $next_service_date, $provider, $status, $remarks);
    
    if ($stmt->execute()) {
        $_SESSION['maintenance_success_msg'] = "Maintenance record added successfully!";
        header("Location: warehouse-maintenance.php");
        exit();
    } else {
        $error_message = "Error adding record: " . $mysqli->error;
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

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    border: 1px solid #f5c6cb;
}
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Add Maintenance Record</h1>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="error-message">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label for="type">Type</label>
                    <select id="type" name="type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="fire_extinguisher">Fire Extinguisher</option>
                        <option value="pest_control">Pest Control</option>
                        <option value="insurance">Insurance</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="provider">Service Provider</label>
                    <input type="text" id="provider" name="provider" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="last_service_date">Last Service Date</label>
                    <input type="date" id="last_service_date" name="last_service_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="next_service_date">Next Service Date</label>
                    <input type="date" id="next_service_date" name="next_service_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="">Select Status</option>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>

                <div class="form-group remarks-field">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" class="form-control" rows="4"></textarea>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" class="submit-btn">Add Record</button>
                <a href="warehouse-maintenance.php" class="cancel-btn">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
// Add validation for service dates
document.getElementById('next_service_date').addEventListener('change', function() {
    var lastService = new Date(document.getElementById('last_service_date').value);
    var nextService = new Date(this.value);
    
    if (nextService <= lastService) {
        alert('Next service date must be after the last service date');
        this.value = '';
    }
});

document.getElementById('last_service_date').addEventListener('change', function() {
    var nextServiceInput = document.getElementById('next_service_date');
    if (nextServiceInput.value) {
        var lastService = new Date(this.value);
        var nextService = new Date(nextServiceInput.value);
        
        if (nextService <= lastService) {
            alert('Next service date must be after the last service date');
            nextServiceInput.value = '';
        }
    }
});
</script>

<?php $mysqli->close(); ?> 