<?php
// Start the session
session_start();

// Check if the user is logged in and has the 'Customer' role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Customer') {
    header("Location: ../login.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SILO User Dashboard</title>
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
            gap: 2rem;
            align-items: center;
        }

        .nav-items a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
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
            <a href="user-dashboard.php">
                <img src="../img/logo.png" alt="SILO Logo" style="height: 40px;">
            </a>
        </div>
        <div class="nav-items">
            <a href="user-dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="place-order.php"><i class="fas fa-shopping-cart"></i> Place Order</a>
            <a href="my-orders.php"><i class="fas fa-list"></i> My Orders</a>
            <a href="track-order.php"><i class="fas fa-truck"></i> Track Order</a>
            <a href="user-profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?');">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>
</body>
</html> 