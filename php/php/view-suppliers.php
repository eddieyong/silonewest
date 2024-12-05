<?php
session_start();

// Check if the user is logged in and has the 'Admin' role
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

$message = '';
$messageType = '';

// Handle supplier deletion
if (isset($_POST['delete_supplier'])) {
    $supplier_id = $_POST['supplier_id'];
    $stmt = $mysqli->prepare("DELETE FROM supplier WHERE id = ?");
    $stmt->bind_param("i", $supplier_id);
    
    if ($stmt->execute()) {
        $message = "Supplier deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting supplier: " . $mysqli->error;
        $messageType = "error";
    }
    $stmt->close();
}

// Handle search
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

if (!$result) {
    die("Query failed: " . $mysqli->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Suppliers - SILO</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            align-items: center;
        }

        .add-supplier-btn,
        .export-btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s;
            color: white;
        }

        .add-supplier-btn {
            background: #5c1f00;
        }

        .export-btn.excel {
            background: #217346;
        }

        .export-btn.pdf {
            background: #ff0000;
        }

        .add-supplier-btn:hover {
            background: #7a2900;
        }

        .export-btn.excel:hover {
            background: #1a5c38;
        }

        .export-btn.pdf:hover {
            background: #cc0000;
        }

        .suppliers-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
            overflow-x: auto;
        }

        .suppliers-table table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        .suppliers-table th,
        .suppliers-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .suppliers-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            white-space: nowrap;
        }

        .suppliers-table tr:hover {
            background: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            white-space: nowrap;
        }

        .edit-btn,
        .delete-btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }

        .edit-btn {
            background: #e3f2fd;
            color: #1976d2;
        }

        .delete-btn {
            background: #ffebee;
            color: #c62828;
            border: none;
            cursor: pointer;
        }

        .edit-btn:hover {
            background: #bbdefb;
        }

        .delete-btn:hover {
            background: #ffcdd2;
        }

        .message {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }

        .message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .message.error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-bar input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .search-bar input:focus {
            border-color: #5c1f00;
            outline: none;
        }

        .search-bar button {
            padding: 10px 20px;
            background: #5c1f00;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s;
        }

        .search-bar button:hover {
            background: #7a2900;
        }
    </style>
</head>
<body>
    <?php include 'admin-header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Manage Suppliers</h1>
            <div class="header-buttons">
                <a href="export-suppliers-excel.php" class="export-btn excel">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </a>
                <a href="export-suppliers-pdf.php" class="export-btn pdf">
                    <i class="fas fa-file-pdf"></i> Export to PDF
                </a>
                <a href="add-supplier.php" class="add-supplier-btn">
                    <i class="fas fa-plus"></i> Add New Supplier
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
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
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
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
</body>
</html> 