<?php
// Start the session
session_start();

// Check if the 'username' session variable is set before using it
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';  
?>
<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - SILO</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<style>
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
    </style>
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
            <a href="customer-profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?');">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>
   <br>
        <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
        <p>You've logged in as an <strong>customer</strong>.</p>
        <br>
        <br>
        

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-left">
                <h3>Contact Us</h3>
                <p>HQ Office Contact: <a href="tel:+60367317663">+03-6731 7663</a></p>
                <p>Email: <a href="mailto:service@silomsdnbhd.com">service@silomsdnbhd.com</a></p>
                <p><a href="https://wa.me/60367317663" class="green-button" target="_blank"> <img src="img/whatsapp.png" alt="WhatsApp" class="cwhatsapp-icon">WhatsApp: Chat with us !!</img></a></p>
            </div>
            <div class="footer-right">
                <h3>Follow Us On Our Social Media:</h3>
                <div class="social-icons">
                    <a href="https://www.facebook.com/profile.php/?id=100063545650222" class="social-icon" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/siloforyou/" class="social-icon" target="_blank"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>©2024 Silo (M) Sdn. Bhd. All Rights Reserved</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
    <script src="script.js"></script>

    <script>
        // Optional JavaScript to handle dropdown menu interaction
        $(document).ready(function() {
            $(".dropdown").hover(
                function() {
                    $(this).children(".dropdown-content").stop(true, true).slideDown(200);
                },
                function() {
                    $(this).children(".dropdown-content").stop(true, true).slideUp(200);
                }
            );
        });
    </script>
</body>
</html>
 