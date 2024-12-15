<?php
// Function to log activities in the database
function logActivity($mysqli, $activity_type, $description) {
    try {
        session_start();
        $created_by = isset($_SESSION['username']) ? $_SESSION['username'] : 'System';
        
        $stmt = $mysqli->prepare("INSERT INTO activities (activity_type, description, created_by) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $activity_type, $description, $created_by);
            $stmt->execute();
        }
    } catch (Exception $e) {
        // Silently fail - logging should not break the main functionality
        error_log("Error logging activity: " . $e->getMessage());
    }
}
?> 