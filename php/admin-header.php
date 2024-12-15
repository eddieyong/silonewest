<?php
// Only start session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'permissions.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['Admin', 'Storekeeper', 'Coordinator', 'Driver'])) {
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

        /* Mobile menu button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
        }

        /* Container Style */
        .container {
            padding: 20px 30px;
            background: #f8f9fa;
            min-height: calc(100vh - 72px);
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .top-nav {
                padding: 1rem;
            }

            .mobile-menu-btn {
                display: block;
            }

            .nav-items {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #5c1f00;
                flex-direction: column;
                align-items: stretch;
                padding: 1rem;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }

            .nav-items.active {
                display: flex;
            }

            .nav-items a {
                padding: 0.8rem 1rem;
                text-align: left;
                border-radius: 0;
                width: 100%;
            }

            .dropdown {
                width: 100%;
            }

            .dropdown-content {
                position: static;
                box-shadow: none;
                width: 100%;
                margin-top: 0;
                padding-left: 1rem;
            }

            .dropdown-content a {
                padding-left: 2rem;
            }

            .dropdown:hover .dropdown-content {
                display: none;
            }

            .dropdown.active .dropdown-content {
                display: block;
            }

            .nav-items .logout-btn {
                margin-top: 1rem;
                justify-content: center;
            }
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
        <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="nav-items">
            <!-- Common Dashboard Link -->
            <a href="admin.php"><i class="fas fa-home"></i> Dashboard</a>

            <!-- Admin Navigation -->
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <div class="dropdown">
                    <a href="#" onclick="toggleDropdown(this)"><i class="fas fa-shopping-cart"></i> Orders <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="view-orders.php"><i class="fas fa-list"></i> View Orders</a>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="#" onclick="toggleDropdown(this)"><i class="fas fa-box"></i> Inventory <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="inventory.php"><i class="fas fa-list"></i> View Inventory</a>
                        <a href="add-inventory.php"><i class="fas fa-plus"></i> Add Item</a>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="#" onclick="toggleDropdown(this)"><i class="fas fa-truck"></i> Suppliers <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="view-suppliers.php"><i class="fas fa-list"></i> View Suppliers</a>
                        <a href="add-supplier.php"><i class="fas fa-plus"></i> Add Supplier</a>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="#" onclick="toggleDropdown(this)"><i class="fas fa-users"></i> Users <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="manage-users.php"><i class="fas fa-list"></i> View Users</a>
                        <a href="add-user.php"><i class="fas fa-user-plus"></i> Add User</a>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="#" onclick="toggleDropdown(this)"><i class="fas fa-car"></i> Vehicles <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="view-vehicles.php"><i class="fas fa-list"></i> View Vehicles</a>
                        <a href="add-vehicle.php"><i class="fas fa-plus"></i> Add Vehicle</a>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="#" onclick="toggleDropdown(this)"><i class="fas fa-warehouse"></i> Warehouse <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="warehouse-maintenance.php"><i class="fas fa-tools"></i> Maintenance</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Storekeeper Navigation -->
            <?php if ($_SESSION['role'] === 'Storekeeper'): ?>
                <div class="dropdown">
                    <a href="#" onclick="toggleDropdown(this)"><i class="fas fa-box"></i> Inventory <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="inventory.php"><i class="fas fa-list"></i> View Inventory</a>
                        <a href="add-inventory.php"><i class="fas fa-plus"></i> Add Item</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Coordinator Navigation -->
            <?php if ($_SESSION['role'] === 'Coordinator'): ?>
                <a href="inventory.php"><i class="fas fa-box"></i> View Inventory</a>
                
                <div class="dropdown">
                    <a href="#" onclick="toggleDropdown(this)"><i class="fas fa-truck"></i> Deliveries <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="purchase-orders.php"><i class="fas fa-file-invoice"></i> Purchase Orders</a>
                        <a href="delivery-orders.php"><i class="fas fa-shipping-fast"></i> Delivery Orders</a>
                    </div>
                </div>

                <a href="manage-notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                <a href="history.php"><i class="fas fa-history"></i> History</a>
            <?php endif; ?>

            <!-- Driver Navigation -->
            <?php if ($_SESSION['role'] === 'Driver'): ?>
                <div class="dropdown">
                    <a href="#" onclick="toggleDropdown(this)"><i class="fas fa-truck"></i> Deliveries <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="purchase-orders.php"><i class="fas fa-file-invoice"></i> Purchase Orders</a>
                        <a href="delivery-orders.php"><i class="fas fa-shipping-fast"></i> Delivery Orders</a>
                    </div>
                </div>

                <a href="view-vehicles.php"><i class="fas fa-car"></i> View Vehicles</a>
                <a href="manage-notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                <a href="history.php"><i class="fas fa-history"></i> History</a>
            <?php endif; ?>

            <!-- Common Navigation Items for Admin and Storekeeper -->
            <?php if ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Storekeeper'): ?>
                <a href="manage-notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                <a href="history.php"><i class="fas fa-history"></i> History</a>
            <?php endif; ?>

            <!-- Common Profile and Logout Links -->
            <a href="admin-profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?');">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <script>
        function toggleMobileMenu() {
            const navItems = document.querySelector('.nav-items');
            navItems.classList.toggle('active');
        }

        function toggleDropdown(element) {
            if (window.innerWidth <= 768) {
                event.preventDefault();
                const dropdown = element.parentElement;
                const dropdowns = document.querySelectorAll('.dropdown');
                
                // Close other dropdowns
                dropdowns.forEach(d => {
                    if (d !== dropdown) {
                        d.classList.remove('active');
                    }
                });
                
                // Toggle current dropdown
                dropdown.classList.toggle('active');
            }
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const navItems = document.querySelector('.nav-items');
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            
            if (!event.target.closest('.nav-items') && 
                !event.target.closest('.mobile-menu-btn') && 
                window.innerWidth <= 768) {
                navItems.classList.remove('active');
                document.querySelectorAll('.dropdown').forEach(d => {
                    d.classList.remove('active');
                });
            }
        });

        // Close mobile menu when window is resized above mobile breakpoint
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.querySelector('.nav-items').classList.remove('active');
                document.querySelectorAll('.dropdown').forEach(d => {
                    d.classList.remove('active');
                });
            }
        });
    </script>
</body>
</html> 