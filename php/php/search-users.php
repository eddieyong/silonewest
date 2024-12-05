<?php
// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get search term
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

// Generate HTML for table rows
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td>" . htmlspecialchars($row['contact']) . "</td>";
    echo "<td>" . htmlspecialchars($row['role']) . "</td>";
    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
    echo "<td>";
    echo "<div class='action-buttons'>";
    echo "<a href='edit-user.php?username=" . urlencode($row['username']) . "&table=" . urlencode($row['source_table']) . "' class='edit-btn'>";
    echo "<i class='fas fa-edit'></i> Edit";
    echo "</a>";
    echo "<form method='POST' style='display: inline;' onsubmit='return confirm(\"Are you sure you want to delete this user?\")'>";
    echo "<input type='hidden' name='username' value='" . htmlspecialchars($row['username']) . "'>";
    echo "<input type='hidden' name='table' value='" . htmlspecialchars($row['source_table']) . "'>";
    echo "<button type='submit' name='delete_user' class='delete-btn'>";
    echo "<i class='fas fa-trash'></i> Delete";
    echo "</button>";
    echo "</form>";
    echo "</div>";
    echo "</td>";
    echo "</tr>";
}

$mysqli->close();
?> 