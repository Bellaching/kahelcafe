<?php
include './../user/authenticate.php';
include './../../connection/connection.php';

// Handle AJAX actions for notifications
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'check_notifications':
            if (!isset($_GET['user_id'])) {
                echo json_encode(['count' => 0]);
                exit;
            }
            $user_id = $_GET['user_id'];
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notification WHERE user_id = ? AND is_read = FALSE");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['count'];
            $stmt->close();
            echo json_encode(['count' => $count]);
            exit;
            
        case 'get_notifications':
            if (!isset($_GET['user_id'])) {
                echo json_encode([]);
                exit;
            }
            $user_id = $_GET['user_id'];
            $stmt = $conn->prepare("
                SELECT n.id, n.message, n.is_read, n.created_at, r.transaction_code 
                FROM notification n
                LEFT JOIN reservation r ON n.reservation_id = r.id
                WHERE n.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT 50
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $notifications = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            echo json_encode($notifications);
            exit;
    }
}

// After updating the reservation status, add this notification logic
function triggerReservationNotification($conn, $reservation_id, $new_status) {
    // Define which statuses should trigger notifications
    $notifiable_statuses = ['for confirmation', 'paid', 'cancel'];
    
    // Debug: Log the status being checked
    error_log("Checking notification for status: $new_status");
    
    if (in_array(strtolower($new_status), array_map('strtolower', $notifiable_statuses))) {
        // Debug: Log that status is notifiable
        error_log("Status $new_status is notifiable");
        
        // Get reservation details
        $stmt = $conn->prepare("
            SELECT r.id, r.user_id, r.transaction_code, r.clientFullName, 
                   r.reservation_date, r.reservation_time, r.res_status,
                   u.id as client_id
            FROM reservation r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.id = ?
        ");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservation = $result->fetch_assoc();
        $stmt->close();
        
        if ($reservation) {
            // Debug: Log reservation found
            error_log("Reservation found: " . print_r($reservation, true));
            
            // Create messages for different statuses
            $status_messages = [
                'for confirmation' => "Your reservation #{$reservation['transaction_code']} is pending confirmation",
                'paid' => "Your reservation #{$reservation['transaction_code']} has been confirmed (Paid)",
                'cancel' => "Your reservation #{$reservation['transaction_code']} has been canceled"
            ];
            
            $message = $status_messages[strtolower($new_status)] ?? "Your reservation status has been updated to {$new_status}";
            
            // Notification for client
            if ($reservation['client_id']) {
                error_log("Creating client notification for user {$reservation['client_id']}");
                createNotification($conn, $reservation['client_id'], $reservation_id, $message);
            }
            
            // Notification for admin (owner/staff)
            $admin_message = "Reservation #{$reservation['transaction_code']} by {$reservation['clientFullName']} is now {$new_status}";
            
            // Get all admin users (owner and staff)
            $admin_stmt = $conn->prepare("SELECT id FROM users WHERE role IN ('owner', 'staff')");
            $admin_stmt->execute();
            $admin_result = $admin_stmt->get_result();
            
            while ($admin = $admin_result->fetch_assoc()) {
                error_log("Creating admin notification for user {$admin['id']}");
                createNotification($conn, $admin['id'], $reservation_id, $admin_message);
            }
            
            $admin_stmt->close();
        } else {
            error_log("No reservation found with ID: $reservation_id");
        }
    } else {
        error_log("Status $new_status is not notifiable");
    }
}


if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'mark_notifications_read':
            if (!isset($_POST['user_id'])) {
                echo json_encode(['success' => false]);
                exit;
            }
            $user_id = $_POST['user_id'];
            $stmt = $conn->prepare("UPDATE notification SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
            $stmt->bind_param("i", $user_id);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success]);
            exit;
            
        case 'mark_notification_read':
            if (!isset($_POST['notification_id'])) {
                echo json_encode(['success' => false]);
                exit;
            }
            $notification_id = $_POST['notification_id'];
            $stmt = $conn->prepare("UPDATE notification SET is_read = TRUE WHERE id = ?");
            $stmt->bind_param("i", $notification_id);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success]);
            exit;
            
        case 'delete_notification':
            if (!isset($_POST['notification_id'])) {
                echo json_encode(['success' => false]);
                exit;
            }
            $notification_id = $_POST['notification_id'];
            $stmt = $conn->prepare("DELETE FROM notification WHERE id = ?");
            $stmt->bind_param("i", $notification_id);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success]);
            exit;
            
        case 'clear_notifications':
            if (!isset($_POST['user_id'])) {
                echo json_encode(['success' => false]);
                exit;
            }
            $user_id = $_POST['user_id'];
            $stmt = $conn->prepare("DELETE FROM notification WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $success = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $success]);
            exit;
    }
}

// Function to create a notification
function createNotification($conn, $user_id, $reservation_id, $message) {
    error_log("Creating notification for user $user_id: $message");
    $stmt = $conn->prepare("INSERT INTO notification (user_id, reservation_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $reservation_id, $message);
    $stmt->execute();
    $stmt->close();
}

// Get unread notification count
$notification_count = 0;
if (isset($user_id)) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notification WHERE user_id = ? AND is_read = FALSE");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notification_count = $result->fetch_assoc()['count'];
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }
        
        .navbar-default {
            background-color: white;
            width: 100%;
            margin: 0;
            padding: 0;
            border: none !important;
        }
        
        .navbar-container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0 15px;
        }
        
        .navbar-nav > li > a {
            color: black;
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            white-space: nowrap;
            border: none !important;
        }
        
        .navbar-nav > li > a:hover,
        .dropdown-menu > li > a:hover {
            background-color: #f8f9fa;
            color: black;
        }
        
        .shadow-bottom {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
        }
        
        .notification-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 3px 6px;
            font-size: 10px;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            position: absolute;
        }
        
        .dropdown-item {
            padding: 8px 16px;
            color: #333;
            border: none !important;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .nav-link,
        .dropdown-toggle,
        .navbar-toggler {
            border: none !important;
            outline: none !important;
        }
        
        /* Notification specific styles */
        .notification-item {
            transition: all 0.3s ease;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        
        .notification-item.unread {
            background-color: #f0f8ff;
            border-left: 4px solid #0d6efd;
        }
        
        .notification-time {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .notification-container {
            max-height: 60vh;
            overflow-y: auto;
            width: 100%;
        }
        
        .notification-modal {
            width: 400px;
            max-width: 90%;
        }
        
        @media (min-width: 992px) {
            .navbar-expand-lg .navbar-nav {
                flex-direction: row;
                width: auto;
            }
            
            .navbar-expand-lg .navbar-nav .nav-link {
                padding: 15px 20px;
            }
            
            .navbar-expand-lg .dropdown-menu {
                position: absolute;
                border: none;
            }
            
            .navbar-collapse {
                display: flex !important;
                flex-basis: auto;
                justify-content: flex-end;
            }
        }
        
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background-color: white;
                padding: 15px;
                margin-top: 10px;
                border-radius: 5px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                width: 100%;
            }
            
            .navbar-toggler {
                border: none !important;
                padding: 10px;
            }
            
            .navbar-nav {
                width: 100%;
            }
            
            .nav-item {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .dropdown-menu {
                position: static !important;
                float: none;
                width: 100%;
                margin-top: 0;
                border: none !important;
                box-shadow: none;
            }
            
            .dropdown-toggle::after {
                float: right;
                margin-top: 8px;
            }
        }
        
        .notification-count {
            position: absolute;
            top: -5px;
            right: -10px;
            background-color: #FF902B;
            color: white;
            border-radius: 50%;
            padding: 3px 8px;
            font-weight: bold;
            font-size: 12px;
            animation: bounce 0.5s ease-in-out;
        }

        @keyframes bounce {
            0% { transform: scale(0.5); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1100;
        }
        
        .notification-dot {
            height: 8px;
            width: 8px;
            background-color: #0d6efd;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
    </style>
    <title>Navigation</title>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-default shadow-bottom">
    <div class="container-fluid navbar-container">
        <a class="navbar-brand" href="#">
            <img src="./../../components/icon/kahel-cafe-logo.png" alt="MyWebsite" class="img-fluid" style="max-height: 60px;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fas fa-bars text-dark"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <!-- Owner Side -->
                <?php if ($role === 'owner'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-black" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Admin side</a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="./../views/accountManagement.php">Account Management</a></li>
                            <li><a class="dropdown-item" href="./../views/client.php">Client Management</a></li>
                            <li><a class="dropdown-item" href="./../views/index.php">Order Management</a></li>
                            <li><a class="dropdown-item" href="./../views/reservation.php">Reservation Management</a></li>
                            <li><a class="dropdown-item" href="./../views/report.php">Performance Report</a></li>
                            <li><a class="dropdown-item" href="../views/menuManagement.php">Menu Management</a></li>
                        </ul>
                    </li>
                    <li class="nav-item position-relative me-3">
                        <a class="nav-link text-black" href="#" data-bs-toggle="modal" data-bs-target="#notificationModal" onclick="loadNotifications()">
                            <i class="fa-solid fa-bell"></i>
                            <?php if ($notification_count > 0): ?>
                                <span class="notification-count"><?php echo $notification_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-black" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?php echo htmlspecialchars($username); ?>(<?php echo htmlspecialchars($role); ?>)</a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changeProfileModal"><i class="fa-regular fa-user me-2"></i>Change Profile</a></li>
                            <li><a class="dropdown-item" href="./../views/login.php"><i class="fa-solid fa-power-off me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Staff Side -->
                <?php if ($role === 'staff'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-black" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Admin Side</a>
                        <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                            <li><a class="dropdown-item" href="#">Order Management</a></li>
                            <li><a class="dropdown-item" href="./../views/reservation.php">Reservation Management</a></li>
                            <li><a class="dropdown-item" href="../views/menuManagement.php">Menu Management</a></li>
                        </ul>
                    </li>
                    <li class="nav-item position-relative me-3">
                        <a class="nav-link text-black" href="#" data-bs-toggle="modal" data-bs-target="#notificationModal" onclick="loadNotifications()">
                            <i class="fa-solid fa-bell"></i>
                            <?php if ($notification_count > 0): ?>
                                <span class="notification-count"><?php echo $notification_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-black" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?php echo htmlspecialchars($username); ?>(<?php echo htmlspecialchars($role); ?>)</a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changeProfileModal"><i class="fa-regular fa-user me-2"></i>Change Profile</a></li>
                            <li><a class="dropdown-item" href="./../views/login.php"><i class="fa-solid fa-power-off me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Notification Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="notificationModalLabel">
                    <i class="fas fa-bell me-2"></i>Notifications
                    <span id="liveNotificationCount" class="badge bg-danger ms-2"><?php echo $notification_count; ?></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="notification-container">
                    <div id="notificationList" class="list-group list-group-flush">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading notifications...</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" onclick="clearNotifications()">
                    <i class="fas fa-trash-alt me-1"></i>Clear All
                </button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="markNotificationsAsRead()">
                    <i class="fas fa-check me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Change Profile Modal -->
<div class="modal fade" id="changeProfileModal" tabindex="-1" aria-labelledby="changeProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeProfileModalLabel">Change Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php include './../views/change_profile.php'; ?>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast-container">
    <div id="errorToast" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Notification sound -->
<audio id="notificationSound" src="./../user/notification-sound.mp3" preload="auto"></audio>

<script>
// Global variable to track new notifications
let newNotifications = 0;
let notificationCheckInterval;

$(document).ready(function() {
    // Start checking for new notifications
    startNotificationCheck();
    
    // Play sound when modal is shown (for demo purposes)
    $('#notificationModal').on('shown.bs.modal', function() {
        if (newNotifications > 0) {
            playNotificationSound();
        }
    });
    
    // Mark notifications as read when modal is closed
    $('#notificationModal').on('hidden.bs.modal', function() {
        markNotificationsAsRead();
    });
});

function startNotificationCheck() {
    // Check for new notifications every 30 seconds
    notificationCheckInterval = setInterval(checkForNewNotifications, 30000);
    // Initial check
    checkForNewNotifications();
}

function stopNotificationCheck() {
    clearInterval(notificationCheckInterval);
}

function checkForNewNotifications() {
    $.ajax({
        url: '?action=check_notifications&user_id=<?php echo $user_id; ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.count > 0) {
                // Update notification count
                updateNotificationCount(response.count);
                
                // If there are new notifications, play sound and show toast
                if (response.count > newNotifications) {
                    newNotifications = response.count;
                    playNotificationSound();
                    showToast('You have ' + response.count + ' new notification(s)');
                }
            } else {
                // No new notifications
                newNotifications = 0;
                $('.notification-count').fadeOut();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error checking notifications:', error);
        }
    });
}

function updateNotificationCount(count) {
    const badge = $('.notification-count');
    const liveBadge = $('#liveNotificationCount');
    
    if (count > 0) {
        badge.text(count).fadeIn();
        liveBadge.text(count).fadeIn();
    } else {
        badge.fadeOut();
        liveBadge.fadeOut();
    }
}



function loadNotifications() {
    $.ajax({
        url: '?action=get_notifications&user_id=<?php echo $user_id; ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            const notificationList = $('#notificationList');
            notificationList.empty();
            
            if (response.length === 0) {
                notificationList.html(`
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No notifications yet</p>
                    </div>
                `);
                return;
            }
            
            response.forEach(function(notification) {
                const timeAgo = getTimeAgo(notification.created_at);
                const unreadClass = notification.is_read ? '' : 'unread';
                const dot = notification.is_read ? '' : '<span class="notification-dot"></span>';
                
                notificationList.append(`
                    <div class="list-group-item notification-item ${unreadClass}" data-id="${notification.id}">
                        <div class="d-flex justify-content-between">
                            <div>
                                ${dot}
                                <strong>${notification.message}</strong>
                            </div>
                            <button class="btn btn-sm btn-outline-danger delete-notification" data-id="${notification.id}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="notification-time">${timeAgo}</div>
                    </div>
                `);
            });
            
            // Add click handler for delete buttons
            $('.delete-notification').click(function(e) {
                e.stopPropagation();
                const notificationId = $(this).data('id');
                deleteNotification(notificationId);
            });
            
            // Add click handler for notification items
            $('.notification-item').click(function() {
                const notificationId = $(this).data('id');
                markNotificationAsRead(notificationId);
                // You could also redirect to the relevant reservation here
            });
        },
        error: function(xhr, status, error) {
            $('#notificationList').html(`
                <div class="text-center py-5 text-danger">
                    <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                    <p>Failed to load notifications</p>
                </div>
            `);
            console.error('Error loading notifications:', error);
        }
    });
}

function markNotificationsAsRead() {
    $.ajax({
        url: '?action=mark_notifications_read',
        type: 'POST',
        data: { 
            user_id: <?php echo $user_id; ?>,
            action: 'mark_notifications_read'
        },
        success: function() {
            // Update UI to show notifications as read
            $('.notification-item').removeClass('unread');
            $('.notification-dot').remove();
            updateNotificationCount(0);
            newNotifications = 0;
        },
        error: function(xhr, status, error) {
            console.error('Error marking notifications as read:', error);
        }
    });
}

function markNotificationAsRead(notificationId) {
    $.ajax({
        url: '?action=mark_notification_read',
        type: 'POST',
        data: { 
            notification_id: notificationId,
            action: 'mark_notification_read'
        },
        success: function() {
            // Update UI for this specific notification
            $(`.notification-item[data-id="${notificationId}"]`).removeClass('unread');
            $(`.notification-item[data-id="${notificationId}"] .notification-dot`).remove();
            
            // Update count
            const currentCount = parseInt($('.notification-count').text());
            if (currentCount > 0) {
                updateNotificationCount(currentCount - 1);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error marking notification as read:', error);
        }
    });
}

function deleteNotification(notificationId) {
    if (!confirm('Are you sure you want to delete this notification?')) return;
    
    $.ajax({
        url: '?action=delete_notification',
        type: 'POST',
        data: { 
            notification_id: notificationId,
            action: 'delete_notification'
        },
        success: function() {
            // Remove the notification from the UI
            $(`.notification-item[data-id="${notificationId}"]`).remove();
            
            // Update count
            const currentCount = parseInt($('.notification-count').text());
            if (currentCount > 0) {
                updateNotificationCount(currentCount - 1);
            }
            
            // If no notifications left, show empty state
            if ($('.notification-item').length === 0) {
                $('#notificationList').html(`
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No notifications yet</p>
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error deleting notification:', error);
            showToast('Failed to delete notification');
        }
    });
}

function clearNotifications() {
    if (!confirm('Are you sure you want to clear all notifications?')) return;
    
    $.ajax({
        url: '?action=clear_notifications',
        type: 'POST',
        data: { 
            user_id: <?php echo $user_id; ?>,
            action: 'clear_notifications'
        },
        success: function() {
            // Clear the notification list
            $('#notificationList').html(`
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No notifications yet</p>
                </div>
            `);
            
            // Update count
            updateNotificationCount(0);
            newNotifications = 0;
        },
        error: function(xhr, status, error) {
            console.error('Error clearing notifications:', error);
            showToast('Failed to clear notifications');
        }
    });
}

function playNotificationSound() {
    const sound = document.getElementById('notificationSound');
    sound.currentTime = 0; // Rewind to start
    sound.play().catch(e => console.log('Audio play failed:', e));
}

function showToast(message) {
    const toast = new bootstrap.Toast(document.getElementById('errorToast'));
    $('#toastMessage').text(message);
    toast.show();
}

function getTimeAgo(dateTime) {
    const now = new Date();
    const then = new Date(dateTime);
    const seconds = Math.floor((now - then) / 1000);
    
    if (seconds < 60) return 'just now';
    
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return minutes + ' minute' + (minutes === 1 ? '' : 's') + ' ago';
    
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return hours + ' hour' + (hours === 1 ? '' : 's') + ' ago';
    
    const days = Math.floor(hours / 24);
    return days + ' day' + (days === 1 ? '' : 's') + ' ago';
}
</script>
</body>
</html>