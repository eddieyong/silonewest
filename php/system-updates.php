<?php
include 'admin-header.php';
require_once 'functions.php';

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get system updates
$result = $mysqli->query("SELECT * FROM activities WHERE activity_type = 'system' ORDER BY created_at DESC LIMIT 50");
?>

<style>
.container {
    padding: 20px 30px;
    background: #f8f9fa;
    min-height: calc(100vh - 72px);
}

.page-header {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    margin-bottom: 30px;
}

.page-title {
    margin: 0;
    font-size: 1.5rem;
    color: #333;
}

.updates-list {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
}

.update-item {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 15px;
}

.update-item:last-child {
    border-bottom: none;
}

.update-icon {
    width: 40px;
    height: 40px;
    background: #e8f5e9;
    color: #2e7d32;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.update-content {
    flex-grow: 1;
}

.update-description {
    margin: 0 0 5px;
    color: #333;
}

.update-time {
    font-size: 0.85rem;
    color: #666;
}

.no-updates {
    padding: 30px;
    text-align: center;
    color: #666;
}
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">System Updates</h1>
    </div>

    <div class="updates-list">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="update-item">
                    <div class="update-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="update-content">
                        <p class="update-description"><?php echo htmlspecialchars($row['description']); ?></p>
                        <p class="update-time"><?php echo date('F j, Y g:i A', strtotime($row['created_at'])); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-updates">
                <p>No system updates</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
// Mark activities as read
$mysqli->query("UPDATE activities SET is_read = 1 WHERE activity_type = 'system' AND is_read = 0");
$mysqli->close(); 
?> 