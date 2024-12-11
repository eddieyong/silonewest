<?php
// search-history.php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fyp";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve search and pagination parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build the query dynamically
$query = "SELECT * FROM activities WHERE 1=1";
if (!empty($search)) {
    $query .= " AND (description LIKE '%$search%' OR activity_type LIKE '%$search%')";
}
if ($month > 0) {
    $query .= " AND MONTH(created_at) = $month";
}
$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

$result = $conn->query($query);

// Fetch total number of records for pagination
$countQuery = "SELECT COUNT(*) as total FROM activities WHERE 1=1";
if (!empty($search)) {
    $countQuery .= " AND (description LIKE '%$search%' OR activity_type LIKE '%$search%')";
}
if ($month > 0) {
    $countQuery .= " AND MONTH(created_at) = $month";
}
$totalResult = $conn->query($countQuery);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// Generate table rows
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['activity_type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";

    if ($row['activity_type'] === 'Stock In' || $row['activity_type'] === 'Stock Out') {
        echo "<td>
        <a href='download_pdf.php?id=" . $row['id'] . "' class='export-btn pdf'>Download PDF</a>
        <a href='download_excel.php?id=" . $row['id'] . "' class='export-btn excel'>Download Excel</a>
      </td>";

    } else {
        echo "<td>N/A</td>";
    }
    echo "</tr>";

}

// Generate pagination links
echo "<tr><td colspan='4'>";
for ($i = 1; $i <= $totalPages; $i++) {
    $activeClass = ($i === $page) ? 'style="font-weight:bold;"' : '';
    echo "<a href='javascript:void(0)' onclick='goToPage($i)' $activeClass>$i</a> ";
}
echo "</td></tr>";

$conn->close();
?>