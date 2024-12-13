<?php
session_start();

// Check if user is logged in and has Admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin-login.html");
    exit();
}

// Database connection
$mysqli = new mysqli("localhost", "root", "", "fyp");

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $title = $mysqli->real_escape_string($_POST['title']);
            $message = $mysqli->real_escape_string($_POST['message']);
            $type = $mysqli->real_escape_string($_POST['type']);
            $start_date = $mysqli->real_escape_string($_POST['start_date']);
            $end_date = !empty($_POST['end_date']) ? "'".$mysqli->real_escape_string($_POST['end_date'])."'" : "NULL";
            
            $query = "INSERT INTO notifications (title, message, type, start_date, end_date, created_by) 
                     VALUES ('$title', '$message', '$type', '$start_date', $end_date, '{$_SESSION['username']}')";
            
            if ($mysqli->query($query)) {
                $success_message = "Notification created successfully!";
            } else {
                $error_message = "Error creating notification: " . $mysqli->error;
            }
        } elseif ($_POST['action'] === 'archive' && isset($_POST['notification_id'])) {
            $id = (int)$_POST['notification_id'];
            if ($mysqli->query("UPDATE notifications SET status = 'archived' WHERE id = $id")) {
                $success_message = "Notification archived successfully!";
            } else {
                $error_message = "Error archiving notification: " . $mysqli->error;
            }
        } elseif ($_POST['action'] === 'restore' && isset($_POST['notification_id'])) {
            $id = (int)$_POST['notification_id'];
            if ($mysqli->query("UPDATE notifications SET status = 'active' WHERE id = $id")) {
                $success_message = "Notification restored successfully!";
            } else {
                $error_message = "Error restoring notification: " . $mysqli->error;
            }
        } elseif ($_POST['action'] === 'delete' && isset($_POST['notification_id'])) {
            $id = (int)$_POST['notification_id'];
            if ($mysqli->query("DELETE FROM notifications WHERE id = $id")) {
                $success_message = "Notification deleted successfully!";
            } else {
                $error_message = "Error deleting notification: " . $mysqli->error;
            }
        }
    }
}

// Get current tab
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'active';

// Get notifications based on status
$notifications = $mysqli->query("
    SELECT * FROM notifications 
    WHERE status = '".($current_tab === 'archived' ? 'archived' : 'active')."' 
    ORDER BY created_at DESC
");

include 'admin-header.php';
?>

<style>
    .page-title {
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        font-family: 'Poppins', sans-serif;
    }

    .action-button {
        background: #5c1f00;
        color: white;
        border: none;
        padding: 0.6rem 1.2rem;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
        text-decoration: none;
    }

    .action-button:hover {
        background: #7a2900;
        transform: translateY(-2px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .notification-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid #eee;
    }

    .notification-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }

    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.8rem;
        border-bottom: 1px solid #eee;
    }

    .notification-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #333;
        font-family: 'Poppins', sans-serif;
    }

    .notification-type {
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        color: white;
        font-weight: 500;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        font-family: 'Poppins', sans-serif;
    }

    .notification-type.closure {
        background: #dc3545;
    }

    .notification-type.announcement {
        background: #0d6efd;
    }

    .notification-type.alert {
        background: #ffc107;
        color: #000;
    }

    .notification-meta {
        font-size: 0.9rem;
        color: #666;
        margin-bottom: 1rem;
        display: flex;
        gap: 1.5rem;
        flex-wrap: wrap;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .notification-message {
        color: #444;
        margin-bottom: 1.2rem;
        line-height: 1.6;
        font-size: 0.95rem;
    }

    .notification-actions {
        display: flex;
        gap: 0.8rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }

    .btn {
        padding: 0.5rem 1rem;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        transition: all 0.3s ease;
        color: white;
        text-decoration: none;
    }

    .btn:hover {
        transform: translateY(-2px);
    }

    .btn-archive {
        background: #6c757d;
    }

    .btn-archive:hover {
        background: #5a6268;
    }

    .btn-delete {
        background: #dc3545;
    }

    .btn-delete:hover {
        background: #c82333;
    }

    .btn-restore {
        background: #28a745;
    }

    .btn-restore:hover {
        background: #218838;
    }

    .tabs {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        border-bottom: 2px solid #eee;
        padding-bottom: 0.5rem;
    }

    .tab {
        padding: 0.8rem 1.5rem;
        color: #666;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-family: 'Poppins', sans-serif;
    }

    .tab:hover {
        background: rgba(92, 31, 0, 0.1);
        color: #5c1f00;
    }

    .tab.active {
        background: #5c1f00;
        color: white;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        backdrop-filter: blur(4px);
    }

    .modal.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        width: 90%;
        max-width: 600px;
        padding: 2rem;
        position: relative;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }

    .modal-header {
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eee;
    }

    .modal-title {
        font-size: 1.4rem;
        color: #333;
        margin: 0;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
    }

    .modal-close {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        background: none;
        border: none;
        font-size: 1.8rem;
        cursor: pointer;
        color: #666;
        transition: color 0.3s ease;
    }

    .modal-close:hover {
        color: #333;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        color: #333;
        font-weight: 500;
        font-family: 'Poppins', sans-serif;
    }

    .form-control {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.95rem;
        transition: border-color 0.3s ease;
        font-family: inherit;
    }

    .form-control:focus {
        outline: none;
        border-color: #5c1f00;
        box-shadow: 0 0 0 3px rgba(92, 31, 0, 0.1);
    }

    .form-select {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.95rem;
        transition: border-color 0.3s ease;
        font-family: inherit;
        background-color: white;
        cursor: pointer;
    }

    .form-select:focus {
        outline: none;
        border-color: #5c1f00;
        box-shadow: 0 0 0 3px rgba(92, 31, 0, 0.1);
    }

    .alert {
        padding: 1rem 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .form-text {
        font-size: 0.85rem;
        color: #666;
        margin-top: 0.4rem;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .notification-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .notification-meta {
            flex-direction: column;
            gap: 0.5rem;
        }

        .notification-actions {
            flex-wrap: wrap;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h1 class="page-title">
            <i class="fas fa-bell"></i> Manage Notifications
        </h1>
        <button class="action-button" onclick="openCreateModal()">
            <i class="fas fa-plus"></i> Create Notification
        </button>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="tabs">
        <a href="?tab=active" class="tab <?php echo $current_tab === 'active' ? 'active' : ''; ?>">
            <i class="fas fa-bell"></i> Active Notifications
        </a>
        <a href="?tab=archived" class="tab <?php echo $current_tab === 'archived' ? 'active' : ''; ?>">
            <i class="fas fa-archive"></i> Archived Notifications
        </a>
    </div>

    <?php if ($notifications && $notifications->num_rows > 0): ?>
        <?php while ($notification = $notifications->fetch_assoc()): ?>
            <div class="notification-card">
                <div class="notification-header">
                    <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                    <span class="notification-type <?php echo $notification['type']; ?>">
                        <?php echo ucfirst(htmlspecialchars($notification['type'])); ?>
                    </span>
                </div>
                <div class="notification-meta">
                    <span class="meta-item">
                        <i class="fas fa-user"></i>
                        Created by: <?php echo htmlspecialchars($notification['created_by']); ?>
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        Start: <?php echo date('Y-m-d H:i', strtotime($notification['start_date'])); ?>
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-clock"></i>
                        End: <?php echo $notification['end_date'] ? date('Y-m-d H:i', strtotime($notification['end_date'])) : 'Indefinite'; ?>
                    </span>
                </div>
                <div class="notification-message">
                    <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                </div>
                <div class="notification-actions">
                    <?php if ($current_tab === 'active'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="archive">
                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                            <button type="submit" class="btn btn-archive" onclick="return confirm('Are you sure you want to archive this notification?')">
                                <i class="fas fa-archive"></i> Archive
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                            <button type="submit" class="btn btn-restore" onclick="return confirm('Are you sure you want to restore this notification?')">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                        </form>
                    <?php endif; ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                        <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to permanently delete this notification? This action cannot be undone.')">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="notification-card">
            <div class="notification-message" style="text-align: center; color: #666;">
                <i class="fas fa-info-circle"></i> No <?php echo $current_tab; ?> notifications found.
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Create Notification Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Create New Notification</h2>
            <button type="button" class="modal-close" onclick="closeCreateModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label class="form-label" for="title">Title</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="type">Type</label>
                <select class="form-select" id="type" name="type" required>
                    <option value="closure">Closure</option>
                    <option value="announcement">Announcement</option>
                    <option value="alert">Alert</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="message">Message</label>
                <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="start_date">Start Date</label>
                <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="end_date">End Date (Optional)</label>
                <input type="datetime-local" class="form-control" id="end_date" name="end_date">
                <div class="form-text">Leave empty for indefinite notifications</div>
            </div>

            <div style="text-align: right; margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="action-button" style="background: #6c757d;" onclick="closeCreateModal()">Cancel</button>
                <button type="submit" class="action-button">Create Notification</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createModal').classList.add('show');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.remove('show');
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('show');
    }
}
</script>

<?php $mysqli->close(); ?> 