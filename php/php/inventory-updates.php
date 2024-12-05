<?php
include 'admin-header.php';
require_once 'functions.php';

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get inventory activities
$result = $mysqli->query("SELECT * FROM activities WHERE activity_type = 'inventory' ORDER BY created_at DESC LIMIT 50");
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

.activities-list {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
}

.activity-item {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 15px;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    background: #e3f2fd;
    color: #1976d2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.activity-content {
    flex-grow: 1;
}

.activity-description {
    margin: 0 0 5px;
    color: #333;
}

.activity-time {
    font-size: 0.85rem;
    color: #666;
}

.no-activities {
    padding: 30px;
    text-align: center;
    color: #666;
}
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Inventory Updates</h1>
    </div>

    <div class="activities-list">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="activity-content">
                        <p class="activity-description"><?php echo htmlspecialchars($row['description']); ?></p>
                        <p class="activity-time"><?php echo date('F j, Y g:i A', strtotime($row['created_at'])); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-activities">
                <p>No recent inventory updates</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
// Mark activities as read
$mysqli->query("UPDATE activities SET is_read = 1 WHERE activity_type = 'inventory' AND is_read = 0");
$mysqli->close(); 
?> 