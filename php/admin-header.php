<?php
// Only start session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and has the 'Admin' role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin-login.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SILO Admin</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Header Styles */
        .top-nav {
            background: #5c1f00;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-items {
            display: flex;
            align-items: center;
        }

        .nav-items a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .nav-items a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-items a.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-items .logout-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-items .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Dropdown styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #5c1f00;
            min-width: 220px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
            top: 100%;
            left: 0;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
            white-space: nowrap;
        }

        .dropdown-content a:first-child {
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }

        .dropdown-content a:last-child {
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
        }

        /* Container Style */
        .container {
            padding: 20px 30px;
            background: #f8f9fa;
            min-height: calc(100vh - 72px);
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="logo">
            <a href="admin.php">
                <img src="../img/logo.png" alt="SILO Logo" style="height: 40px;">
            </a>
        </div>
        <div class="nav-items">
            <a href="admin.php"><i class="fas fa-home"></i> Dashboard</a>
            <div class="dropdown">
                <a href="#"><i class="fas fa-box"></i> Inventory <i class="fas fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="inventory.php"><i class="fas fa-list"></i> View Inventory</a>
                    <a href="add-inventory.php"><i class="fas fa-plus"></i> Add Item</a>
                    <a href="purchase-orders.php"><i class="fas fa-shopping-cart"></i> Purchase Orders</a>
                    <a href="delivery-orders.php"><i class="fas fa-truck"></i> Delivery Orders</a>
                </div>
            </div>
            <div class="dropdown">
                <a href="#"><i class="fas fa-truck"></i> Suppliers <i class="fas fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="view-suppliers.php"><i class="fas fa-list"></i> View Suppliers</a>
                    <a href="add-supplier.php"><i class="fas fa-plus"></i> Add Supplier</a>
                </div>
            </div>
            <div class="dropdown">
                <a href="#"><i class="fas fa-users"></i> Users <i class="fas fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="manage-users.php"><i class="fas fa-list"></i> View Users</a>
                    <a href="add-user.php"><i class="fas fa-user-plus"></i> Add User</a>
                </div>
            </div>
            <div class="dropdown">
                <a href="#"><i class="fas fa-car"></i> Vehicles <i class="fas fa-caret-down"></i></a>
                <div class="dropdown-content">
                    <a href="view-vehicles.php"><i class="fas fa-list"></i> View Vehicles</a>
                    <a href="add-vehicle.php"><i class="fas fa-plus"></i> Add Vehicle</a>
                </div>
            </div>
            <a href="history.php"><i class="fas fa-history"></i> History</a>
            <a href="admin-profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?');">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>
</body>
</html> 