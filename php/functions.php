<?php
// Set default timezone to Asia/Kuala_Lumpur for Malaysia time
date_default_timezone_set('Asia/Kuala_Lumpur');

function logActivity($mysqli, $activity_type, $description) {
    // Get the current user if logged in
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'System';
    
    // Add user info to the description
    $full_description = "By $username: $description";
    
    // Get current timestamp in the correct format with proper timezone
    $timestamp = date('Y-m-d H:i:s');
    
    $stmt = $mysqli->prepare("INSERT INTO activities (activity_type, description, created_by, created_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $activity_type, $full_description, $username, $timestamp);
    $stmt->execute();
    $stmt->close();
}

function getUnreadActivitiesCount($mysqli) {
    $result = $mysqli->query("SELECT COUNT(*) as count FROM activities WHERE is_read = 0");
    $row = $result->fetch_assoc();
    return $row['count'];
}

function getRecentActivities($mysqli, $limit = 10) {
    // Convert timestamps to local time in the query
    $result = $mysqli->query("SELECT *, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at FROM activities ORDER BY created_at DESC LIMIT $limit");
    return $result;
}

function markActivitiesAsRead($mysqli) {
    $mysqli->query("UPDATE activities SET is_read = 1 WHERE is_read = 0");
}

function formatActivityDescription($description) {
    // Format timestamps using regular expression
    $pattern = '/\b\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\b/';
    $description = preg_replace_callback($pattern, function($matches) {
        $timestamp = strtotime($matches[0]);
        return date('M d, Y h:i A', $timestamp);
    }, $description);
    
    // Highlight numbers but exclude dates
    $description = preg_replace('/\b(\d+)(?!\d{2}:\d{2})\b/', '<strong>$1</strong>', $description);
    
    return $description;
}
?> 