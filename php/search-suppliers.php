<?php
// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get search term
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Prepare query
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

// Generate HTML for table rows
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['company_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['contact_person']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
    echo "<td>" . htmlspecialchars($row['address']) . "</td>";
    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
    echo "<td>";
    echo "<div class='action-buttons'>";
    echo "<a href='edit-supplier.php?id=" . $row['id'] . "' class='edit-btn'>";
    echo "<i class='fas fa-edit'></i> Edit";
    echo "</a>";
    echo "<form method='POST' style='display: inline;' onsubmit='return confirm(\"Are you sure you want to delete this supplier?\")'>";
    echo "<input type='hidden' name='supplier_id' value='" . $row['id'] . "'>";
    echo "<button type='submit' name='delete_supplier' class='delete-btn'>";
    echo "<i class='fas fa-trash'></i> Delete";
    echo "</button>";
    echo "</form>";
    echo "</div>";
    echo "</td>";
    echo "</tr>";
}

$mysqli->close();
?> 