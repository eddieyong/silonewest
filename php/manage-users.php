<?php
// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Handle user deletion if requested
if (isset($_POST['delete_user'])) {
    $username = $_POST['username'];
    $table = $_POST['table'];
    $stmt = $mysqli->prepare("DELETE FROM $table WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->close();
}

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query both admin and customer tables
$query = "SELECT username, email, contact, role, created_at, 'admin' as source_table FROM admin";
if (!empty($search)) {
    $query .= " WHERE username LIKE ? OR email LIKE ? OR contact LIKE ? OR role LIKE ?";
}
$query .= " UNION ALL ";
$query .= "SELECT username, email, contact, role, created_at, 'customer' as source_table FROM customer";
if (!empty($search)) {
    $query .= " WHERE username LIKE ? OR email LIKE ? OR contact LIKE ? OR role LIKE ?";
}

if (!empty($search)) {
    $search = "%$search%";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ssssssss", $search, $search, $search, $search, $search, $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $mysqli->query($query);
}

if (!$result) {
    die("Query failed: " . $mysqli->error);
}

// Include the header
include 'admin-header.php';
?>

<style>
    /* Page Specific Styles */
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

    .add-user-btn {
        background: #5c1f00;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.3s;
    }

    .add-user-btn:hover {
        background: #7a2900;
    }

    .users-table {
        width: 100%;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .users-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .users-table th,
    .users-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .users-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }

    .users-table tr:hover {
        background: #f8f9fa;
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

    .status-active {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.9rem;
    }

    .status-inactive {
        background: #ffebee;
        color: #c62828;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.9rem;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Manage Users</h1>
        <a href="add-user.php" class="add-user-btn">
            <i class="fas fa-plus"></i> Add New User
        </a>
    </div>

    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by username, email, contact, or role..." 
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="button">
            <i class="fas fa-search"></i> Search
        </button>
    </div>

    <div class="users-table" id="searchResults">
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['contact']); ?></td>
                    <td><?php echo htmlspecialchars($row['role']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="edit-user.php?username=<?php echo urlencode($row['username']); ?>&table=<?php echo urlencode($row['source_table']); ?>" class="edit-btn">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($row['username']); ?>">
                                <input type="hidden" name="table" value="<?php echo htmlspecialchars($row['source_table']); ?>">
                                <button type="submit" name="delete_user" class="delete-btn">
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
    xhr.open('GET', `search-users.php?search=${encodeURIComponent(searchValue)}`, true);
    
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
 