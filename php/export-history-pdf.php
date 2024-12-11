<?php
require 'vendor/autoload.php'; // Include the Dompdf library

use Dompdf\Dompdf;

session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fyp";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = "SELECT * FROM activities WHERE id = $id AND (activity_type = 'Stock In' OR activity_type = 'Stock Out')";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    $html = "<h1>Activity Report</h1>
            <p><strong>Activity Type:</strong> {$row['activity_type']}</p>
            <p><strong>Description:</strong> {$row['description']}</p>
            <p><strong>Date:</strong> {$row['created_at']}</p>";

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("activity_report_$id.pdf");
} else {
    echo "No report available for the requested activity.";
}

$conn->close();
?>
