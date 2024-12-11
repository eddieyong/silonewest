<?php
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

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"activity_report_$id.xls\"");

    echo "Activity Type\tDescription\tDate\n";
    echo "{$row['activity_type']}\t{$row['description']}\t{$row['created_at']}\n";
} else {
    echo "No report available for the requested activity.";
}

$conn->close();
?>
