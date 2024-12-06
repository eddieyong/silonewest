<?php
function logActivity($mysqli, $activity_type, $description) {
    $stmt = $mysqli->prepare("INSERT INTO activities (activity_type, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $activity_type, $description);
    $stmt->execute();
    $stmt->close();
}

function getUnreadActivitiesCount($mysqli) {
    $result = $mysqli->query("SELECT COUNT(*) as count FROM activities WHERE is_read = 0");
    $row = $result->fetch_assoc();
    return $row['count'];
}

function getRecentActivities($mysqli, $limit = 10) {
    $result = $mysqli->query("SELECT * FROM activities ORDER BY created_at DESC LIMIT $limit");
    return $result;
}

function markActivitiesAsRead($mysqli) {
    $mysqli->query("UPDATE activities SET is_read = 1 WHERE is_read = 0");
}
?> 