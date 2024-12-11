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

// Handle supplier deletion
if (isset($_POST['delete_supplier'])) {
    $supplier_id = $_POST['supplier_id'];
    
    // Get supplier details before deletion
    $stmt = $mysqli->prepare("SELECT * FROM supplier WHERE id = ?");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $supplier = $result->fetch_assoc();
    
    // Delete the supplier
    $stmt = $mysqli->prepare("DELETE FROM supplier WHERE id = ?");
    $stmt->bind_param("i", $supplier_id);
    
    if ($stmt->execute()) {
        // Log the activity with more details
        $description = "Deleted supplier: {$supplier['company_name']} (Contact: {$supplier['contact_person']}, Email: {$supplier['email']})";
        logActivity($mysqli, 'supplier', $description);
        $_SESSION['success_msg'] = "Supplier deleted successfully!";
    } else {
        $_SESSION['error_msg'] = "Error deleting supplier.";
    }
    
    header("Location: view-suppliers.php");
    exit();
}

// Get all suppliers
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT * FROM supplier";

if (!empty($search)) {
    $search = "%$search%";
    $query .= " WHERE company_name LIKE ? OR contact_person LIKE ? OR email LIKE ? OR phone LIKE ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ssss", $search, $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $mysqli->query($query . " ORDER BY company_name");
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

    .export-btn:hover {
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

    .suppliers-table {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .suppliers-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .suppliers-table th,
    .suppliers-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .suppliers-table th {
        background: #f8f9fa;
        font-weight: 500;
        color: #333;
    }

    .suppliers-table tr:last-child td {
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

    .add-supplier-btn {
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

    .add-supplier-btn:hover {
        opacity: 0.9;
        color: white;
    }
    .export-btn.excel {
        background: green;
    }

    
    .export-btn.excel:hover {
        background: #004000;
    }

    /* Add new styles for supplier activity icons */
    .activity-icon.supplier {
        background: #e8eaf6;
        color: #3f51b5;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Manage Suppliers</h1>
        <div class="header-buttons">
            <a href="export-suppliers-pdf.php" class="export-btn pdf">
                <i class="fas fa-file-pdf"></i> Export to PDF
            </a>
            <a href="export-suppliers-excel.php" class="export-btn excel">
                <i class="fas fa-file-excel"></i> Export to Excel
            </a>
            <a href="add-supplier.php" class="add-supplier-btn">
                <i class="fas fa-plus"></i> Add New Supplier
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="message success">
            <?php 
            echo htmlspecialchars($_SESSION['success_msg']); 
            unset($_SESSION['success_msg']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="message error">
            <?php 
            echo htmlspecialchars($_SESSION['error_msg']); 
            unset($_SESSION['error_msg']);
            ?>
        </div>
    <?php endif; ?>

    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by company name, contact person, email, or phone..." 
                value="<?php echo htmlspecialchars($search); ?>">
        <button type="button">
            <i class="fas fa-search"></i> Search
        </button>
    </div>

    <div class="suppliers-table">
        <table>
            <thead>
                <tr>
                    <th>Company Name</th>
                    <th>Contact Person</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact_person']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['address']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit-supplier.php?id=<?php echo $row['id']; ?>" class="edit-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this supplier?')">
                                    <input type="hidden" name="supplier_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="delete_supplier" class="delete-btn">
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
    xhr.open('GET', `search-suppliers.php?search=${encodeURIComponent(searchValue)}`, true);
    
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