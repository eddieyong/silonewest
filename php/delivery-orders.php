<?php
session_start();
require_once 'functions.php';

// Check if user is logged in and has Admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin-login.html");
    exit();
}

$mysqli = new mysqli("localhost", "root", "", "fyp");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

include 'admin-header.php';
?>

<style>
    .container {
        padding: 20px 30px;
        background: #f8f9fa;
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

    .btn-primary {
        background: #5c1f00;
        border: none;
        padding: 10px 20px;
        color: white;
        border-radius: 5px;
        cursor: pointer;
    }

    .btn-primary:hover {
        background: #7a2900;
    }

    .table-responsive {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        padding: 20px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .table th {
        background: #f8f9fa;
        font-weight: 600;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Delivery Orders</h1>
        <button class="btn btn-primary" data-toggle="modal" data-target="#createDOModal">
            <i class="fas fa-plus"></i> Create New DO
        </button>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>DO Number</th>
                    <th>PO Number</th>
                    <th>Delivery Date</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Add PHP code to fetch and display DOs -->
            </tbody>
        </table>
    </div>
</div>

<!-- Create DO Modal -->
<div class="modal fade" id="createDOModal" tabindex="-1" role="dialog" aria-labelledby="createDOModalLabel" aria-hidden="true">
    <!-- Add modal content for DO creation form -->
</div>

<?php $mysqli->close(); ?> 