<?php
// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get search term and month filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$month = isset($_GET['month']) ? intval($_GET['month']) : '';

// Prepare base query
$query = "SELECT id, item_number, inventory_item, bar_code, mfg_date, exp_date, balance_brought_forward, stock_in, stock_out, balance, remarks, created_at, updated_at FROM inventory WHERE 1";
$params = [];
$types = "";

// Handle search by name, item number, or barcode
if (!empty($search)) {
    $search = "%$search%";
    $query .= " AND (item_number LIKE ? OR inventory_item LIKE ? OR bar_code LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "sss";
}

// Handle filtering by month (for `mfg_date` or `exp_date`)
if (!empty($month)) {
    $query .= " AND (MONTH(mfg_date) = ? OR MONTH(exp_date) = ?)";
    $params[] = $month;
    $params[] = $month;
    $types .= "ii";
}

// Prepare and execute the query
$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Check if there are results
if ($result->num_rows > 0) {
    // Generate HTML for table rows
    while($row = $result->fetch_assoc()) {
        $item_number = isset($row['item_number']) ? $row['item_number'] : 'N/A';
        $inventory_item = isset($row['inventory_item']) ? $row['inventory_item'] : 'N/A';
        $bar_code = isset($row['bar_code']) ? $row['bar_code'] : 'N/A';
        $mfg_date = isset($row['mfg_date']) ? date('Y-m-d', strtotime($row['mfg_date'])) : 'N/A';
        $exp_date = isset($row['exp_date']) ? date('Y-m-d', strtotime($row['exp_date'])) : 'N/A';
        $balance_brought_forward = isset($row['balance_brought_forward']) ? intval($row['balance_brought_forward']) : 0;
        $stock_in = isset($row['stock_in']) ? intval($row['stock_in']) : 0;
        $stock_out = isset($row['stock_out']) ? intval($row['stock_out']) : 0;
        $balance = isset($row['balance']) ? intval($row['balance']) : 0;
        $remarks = isset($row['remarks']) ? $row['remarks'] : '';
        $created_at = isset($row['created_at']) ? date('Y-m-d H:i:s', strtotime($row['created_at'])) : 'N/A';
        $updated_at = isset($row['updated_at']) ? date('Y-m-d H:i:s', strtotime($row['updated_at'])) : 'N/A';

        echo "<tr>";
        echo "<td>" . htmlspecialchars($item_number) . "</td>";
        echo "<td>" . htmlspecialchars($inventory_item) . "</td>";
        echo "<td>" . htmlspecialchars($bar_code) . "</td>";
        echo "<td>" . htmlspecialchars($mfg_date) . "</td>";
        echo "<td>" . htmlspecialchars($exp_date) . "</td>";
        echo "<td>" . htmlspecialchars($balance_brought_forward) . "</td>";
        echo "<td>" . htmlspecialchars($stock_in) . "</td>";
        echo "<td>" . htmlspecialchars($stock_out) . "</td>";
        echo "<td>" . htmlspecialchars($balance) . "</td>";
        echo "<td>" . htmlspecialchars($remarks) . "</td>";
        echo "<td>" . htmlspecialchars($created_at) . "</td>";
        echo "<td>" . htmlspecialchars($updated_at) . "</td>";
        echo "<td>";
        echo "<div class='action-buttons'>";
        echo "<a href='edit-inventory.php?id=" . $row['id'] . "' class='edit-btn'>";
        echo "<i class='fas fa-edit'></i> Edit";
        echo "</a>";
        echo "<form method='POST' style='display: inline;' onsubmit='return confirm(\"Are you sure you want to delete this item?\")'>";
        echo "<input type='hidden' name='item_id' value='" . $row['id'] . "'>";
        echo "<button type='submit' name='delete_item' class='delete-btn'>";
        echo "<i class='fas fa-trash'></i> Delete";
        echo "</button>";
        echo "</form>";
        echo "</div>";
        echo "</td>";
        echo "</tr>";
    }
} else {
    // Display "No results found" message
    echo "<tr><td colspan='13' style='text-align: center;'>No results found.</td></tr>";
}

$mysqli->close();
?>
