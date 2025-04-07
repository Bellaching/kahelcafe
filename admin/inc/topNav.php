<?php
include './../user/authenticate.php';
include __DIR__ . '/../../connection/connection.php';

$userId = $_SESSION['user_id'] ?? 0; // Adjust based on your session variable
$username = $_SESSION['username'] ?? '';

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

// Initialize all variables for profile change
$successMessage = '';
$errorMessages = [];
$profile_picture = null;
$role = '';
$newPassword = '';
$confirmPassword = '';

// Get current user data from database including profile picture
$sql = "SELECT username, email, profile_picture, role FROM admin_list WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $userData = $result->fetch_assoc();
    $username = $userData['username'];
    $email = $userData['email'];
    $profile_picture = $userData['profile_picture'];
    $role = $userData['role'];
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) ){
    // Reset error messages for each request
    $errorMessages = [];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'updateProfile':
                // Only validate when updating profile
                $username = $_POST['username'];
                $email = $_POST['email'];
                $newPassword = $_POST['newPassword'] ?? '';
                $confirmPassword = $_POST['confirmPassword'] ?? '';

                // Validate username
                if (empty($username)) {
                    $errorMessages[] = "Username is required.";
                }

                // Validate passwords only if they're provided
                if (!empty($newPassword)) {
                    if (strlen($newPassword) < 8) {
                        $errorMessages[] = "The new password must be at least 8 characters long.";
                    } elseif ($newPassword !== $confirmPassword) {
                        $errorMessages[] = "The passwords do not match.";
                    }
                }

                if (empty($errorMessages)) {
                    try {
                        if (empty($newPassword)) {
                            $sql = "UPDATE admin_list SET username = ?, email = ? WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("ssi", $username, $email, $userId);
                        } else {
                            $sql = "UPDATE admin_list SET username = ?, email = ?, password = ? WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                            $stmt->bind_param("sssi", $username, $email, $hashedPassword, $userId);
                        }
                        
                        if ($stmt->execute()) {
                            // Return JSON response for AJAX
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
                            exit;
                        } else {
                            throw new Exception("Update failed: " . $stmt->error);
                        }
                    } catch (Exception $e) {
                        header('Content-Type: application/json');
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                        exit;
                    }
                } else {
                    header('Content-Type: application/json');
                    http_response_code(400);
                    echo json_encode(['success' => false, 'errors' => $errorMessages]);
                    exit;
                }
                break;
                
            case 'uploadProfilePicture':
                // Only validate file upload
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $fileType = $_FILES['profile_picture']['type'];
                    
                    if (in_array($fileType, $allowedTypes)) {
                        $uploadDir = 'uploads/profile_pictures/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileName = uniqid('profile_') . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                        $uploadPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                            $sql = "UPDATE admin_list SET profile_picture = ? WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("si", $uploadPath, $userId);
                            
                            if ($stmt->execute()) {
                                $profile_picture = $uploadPath;
                                $successMessage = "Profile picture updated successfully!";
                            } else {
                                $errorMessages[] = "Failed to update profile picture in database.";
                            }
                            $stmt->close();
                        } else {
                            $errorMessages[] = "Failed to upload profile picture.";
                        }
                    } else {
                        $errorMessages[] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
                    }
                } else {
                    $errorMessages[] = "Please select a valid image file.";
                }
                break;
                
            case 'deleteProfilePicture':
                if (!empty($profile_picture) && file_exists($profile_picture)) {
                    unlink($profile_picture);
                }
                
                $sql = "UPDATE admin_list SET profile_picture = NULL WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $userId);
                
                if ($stmt->execute()) {
                    $profile_picture = null;
                    $successMessage = "Profile picture removed successfully!";
                } else {
                    $errorMessages[] = "Failed to remove profile picture from database.";
                }
                $stmt->close();
                break;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    
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

        /* Profile Picture Styles */
        .profile-picture-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            cursor: pointer;
        }
        .profile-picture {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #FF902B;
        }
        .profile-picture-placeholder {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #FF902B;
            color: #777;
            font-size: 50px;
        }
        .edit-profile-picture {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: #FF902B;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
            transition: opacity 0.3s;
        }
        .modal-header {
            background-color: #FF902B;
            color: white;
        }
        .modal-content {
            border-radius: 10px;
        }
        .alert {
            margin-top: 10px;
        }
        .password-strength {
            font-size: 14px;
        }
        .password-strength.weak {
            color: red;
        }
        .password-strength.medium {
            color: orange;
        }
        .password-strength.strong {
            color: green;
        }
        .password-fields {
            display: none;
        }
        @media (max-width: 576px) {
            .profile-picture-container {
                width: 120px;
                height: 120px;
            }
            .edit-profile-picture {
                width: 35px;
                height: 35px;
                opacity: 1;
            }
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
                            <li><a class="dropdown-item" href="../views/content.php">Virtual Management</a></li>
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
                            <li><a class="dropdown-item" href="./../views/accountManagement.php">Order Management</a></li>
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
                <?php if ($successMessage): ?>
                    <div class="alert alert-success" role="alert" id="successMessage">
                        <?= htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMessages) && isset($_POST['action']) && $_POST['action'] === 'updateProfile'): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php foreach ($errorMessages as $error): ?>
                            <div><?= htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="profileForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <input type="hidden" name="action" value="updateProfile">
                    
                    <div class="text-center mb-4">
                        <div class="profile-picture-container" data-bs-toggle="modal" data-bs-target="#profilePictureModal">
                            <?php if (!empty($profile_picture)): ?>
                                <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Profile Picture" class="profile-picture">
                            <?php else: ?>
                                <div class="profile-picture-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <div class="edit-profile-picture">
                                <i class="fas fa-pencil-alt"></i>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" id="username" value="<?= htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($role); ?>" readonly>
                    </div>

                    <div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" id="changePasswordCheckbox">
    <label class="form-check-label" for="changePasswordCheckbox">Change Password</label>
</div>

<div class="password-fields" id="passwordFields">
    <div class="mb-3">
        <label for="newPassword" class="form-label">New Password</label>
        <input type="password" class="form-control" name="newPassword" id="newPassword">
        <div id="password-strength" class="password-strength"></div>
    </div>
    <div class="mb-3">
        <label for="confirmPassword" class="form-label">Confirm Password</label>
        <input type="password" class="form-control" name="confirmPassword" id="confirmPassword">
    </div>
</div>

                    <div class="mb-3">
                        <button type="submit" class="btn w-100 text-white rounded-pill" style="background-color: #FF902B;">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Profile Picture Modal -->
<div class="modal fade" id="profilePictureModal" tabindex="-1" aria-labelledby="profilePictureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profilePictureModalLabel">Change Profile Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($errorMessages) && isset($_POST['action']) && $_POST['action'] === 'uploadProfilePicture'): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php foreach ($errorMessages as $error): ?>
                            <div><?= htmlspecialchars($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="profilePictureForm">
                    <input type="hidden" name="action" value="uploadProfilePicture">
                    <div class="mb-3 text-center">
                        <?php if (!empty($profile_picture)): ?>
                            <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Current Profile Picture" class="profile-picture mb-3" style="width: 200px; height: 200px;">
                        <?php else: ?>
                            <div class="profile-picture-placeholder mb-3 mx-auto" style="width: 200px; height: 200px;">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        
                        <input type="file" class="form-control" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/gif">
                        <small class="text-muted">Max size: 2MB. Formats: JPG, PNG, GIF</small>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <?php if (!empty($profile_picture)): ?>
                            <button type="submit" name="action" value="deleteProfilePicture" class="btn btn-danger rounded-pill">
                                <i class="fas fa-trash-alt"></i> Delete Picture
                            </button>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn text-white rounded-pill ms-auto" style="background-color: #FF902B;">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
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




<!-- Notification sound -->
<audio id="notificationSound" src="./../user/notification-sound.mp3" preload="auto"></audio>

<script>

/ Toggle password fields visibility
$(document).on('change', '#changePasswordCheckbox', function() {
    if (this.checked) {
        $('#passwordFields').show();
    } else {
        $('#passwordFields').hide();
        $('#newPassword').val('');
        $('#confirmPassword').val('');
        $('#password-strength').text('');
    }
});

// Password strength detection
$(document).on('input', '#newPassword', function() {
    const password = $(this).val();
    const strengthElement = $('#password-strength');
    
    if (password.length === 0) {
        strengthElement.text('');
        return;
    }
    
    if (password.length < 8) {
        strengthElement.text('Strength: Weak').removeClass().addClass('password-strength weak');
    } else if (/[A-Za-z]/.test(password) && /\d/.test(password)) {
        strengthElement.text('Strength: Medium').removeClass().addClass('password-strength medium');
    } else {
        strengthElement.text('Strength: Strong').removeClass().addClass('password-strength strong');
    }
});

  // Add this to your $(document).ready() function
  $('#profileForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    
    $.ajax({
        url: '',
        type: 'POST',
        data: formData,
        dataType: 'json', // Expect JSON response
        success: function(response) {
            if (response.success) {
                $('#changeProfileModal').modal('hide');
                showToast(response.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                // Handle validation errors
                let errorHtml = '';
                if (response.errors) {
                    errorHtml = response.errors.map(error => `<div>${error}</div>`).join('');
                } else {
                    errorHtml = `<div>${response.message}</div>`;
                }
                
                $('#changeProfileModal .alert-danger').remove();
                $('#changeProfileModal .modal-body').prepend(
                    `<div class="alert alert-danger" role="alert">${errorHtml}</div>`
                );
            }
        },
        error: function(xhr, status, error) {
            showToast('Error updating profile: ' + error);
        }
    });
}); 
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

    // Automatically hide the success message after 5 seconds
    setTimeout(function() {
        var successMessage = document.getElementById('successMessage');
        if (successMessage) {
            successMessage.style.display = 'none';
        }
    }, 5000);

    // Toggle visibility of password fields based on checkbox
    document.getElementById('showPasswordCheckbox').addEventListener('change', function () {
        const passwordFields = document.querySelector('.password-fields');
        if (this.checked) {
            passwordFields.style.display = 'block';
        } else {
            passwordFields.style.display = 'none';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            document.getElementById('password-strength').textContent = '';
        }
    });

    // Password strength detection
    document.getElementById('newPassword').addEventListener('input', function () {
        const password = this.value;
        const strengthElement = document.getElementById('password-strength');
        let strength = '';
        
        if (password.length === 0) {
            strengthElement.textContent = '';
            return;
        }
        
        if (password.length < 8) {
            strength = 'Weak';
            strengthElement.className = 'password-strength weak';
        } else if (/[A-Za-z]/.test(password) && /\d/.test(password)) {
            strength = 'Medium';
            strengthElement.className = 'password-strength medium';
        } else {
            strength = 'Strong';
            strengthElement.className = 'password-strength strong';
        }
        strengthElement.textContent = `Strength: ${strength}`;
    });
    
    // Preview profile picture before upload
    document.getElementById('profile_picture').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const preview = document.querySelector('#profilePictureModal .profile-picture');
                if (preview) {
                    preview.src = event.target.result;
                } else {
                    const placeholder = document.querySelector('#profilePictureModal .profile-picture-placeholder');
                    if (placeholder) {
                        placeholder.innerHTML = `<img src="${event.target.result}" alt="Preview" class="profile-picture" style="width: 100%; height: 100%;">`;
                    }
                }
            };
            reader.readAsDataURL(file);
        }
    });
});

// function startNotificationCheck() {
//     // Check for new notifications every 30 seconds
//     notificationCheckInterval = setInterval(checkForNewNotifications, 30000);
//     // Initial check
//     checkForNewNotifications();
// }

// function stopNotificationCheck() {
//     clearInterval(notificationCheckInterval);
// }

// function checkForNewNotifications() {
//     $.ajax({
//         url: '?action=check_notifications&user_id=<?php echo $user_id; ?>',
//         type: 'GET',
//         dataType: 'json',
//         success: function(response) {
//             if (response.count > 0) {
//                 // Update notification count
//                 updateNotificationCount(response.count);
                
//                 // If there are new notifications, play sound and show toast
//                 if (response.count > newNotifications) {
//                     newNotifications = response.count;
//                     playNotificationSound();
//                     showToast('You have ' + response.count + ' new notification(s)');
//                 }
//             } else {
//                 // No new notifications
//                 newNotifications = 0;
//                 $('.notification-count').fadeOut();
//             }
//         },
//         error: function(xhr, status, error) {
//             console.error('Error checking notifications:', error);
//         }
//     });
// }

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