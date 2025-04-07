<?php
session_start();

// Initialize variables
$firstName = ''; // Default value if not logged in
$lastName = '';
$email = '';
$contactNumber = '';

// Check if the user is logged in and if they clicked the logout link
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

include './../../connection/connection.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $sql = "SELECT firstname, lastname, email, contact_number FROM client WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($firstName, $lastName, $email, $contactNumber);
    $stmt->fetch();
    $stmt->close();

    $sql = "SELECT COUNT(*) FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($cartCount);
    $stmt->fetch();
    $stmt->close();

    // Fetch the notification count (unread notifications)
    $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($notificationCount);
    $stmt->fetch();
    $stmt->close();

    // Fetch all notifications for the user
    $sql = "SELECT id, message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $cartCount = 0; // Default if the user is not logged in
    $notificationCount = 0; // Default if the user is not logged in
    $notifications = []; // Empty array if the user is not logged in
}



$sql = "SELECT id, message, created_at, is_read FROM notifications WHERE user_id = ? ORDER BY created_at DESC ";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../views/change_profile.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel/slick/slick.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel/slick/slick-theme.css"/>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/slick-carousel/slick/slick.min.js"></script>

    <style>
        .navbar-default {
            background-color: white;
            border-color: #e7e7e7;
        }
        .navbar-nav > li > a {
            color: black;
            text-decoration: none;
            padding: 10px 15px;
        }
     
        .navbar-nav > li > a.active {
            color: #FF902B !important;
        }
        .shadow-bottom {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
        }
        .dropdown-menu {
            min-width: 200px;
        }
        
        /* Cart and Notification count styling */
        .cart-count, .notification-count {
            position: absolute;
            top: -5px;
            right: -10px;
            background-color: #FF902B;
            color: white;
            border-radius: 50%;
            padding: 3px 8px; /* Adjusted padding */
            font-weight: bold;
            font-size: 12px; /* Reduced font size */
            animation: bounce 0.5s ease-in-out;
        }

        /* Animation for cart and notification count */
        @keyframes bounce {
            0% {
                transform: scale(0.5);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }

        /* Toast notification styling */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }

         /* Style for unread notifications */
    .unread-notification {
        background-color: #FF902B; /* Orange background */
        border-radius: 5px; /* Rounded corners */
        padding: 10px; /* Add some padding */
        color: white;
    }

    /* Hover effect for unread notifications */
    .unread-notification:hover {
        background-color: #e67e22; /* Darker orange on hover */
    }

    /* Custom scrollbar styling */
.modal-body::-webkit-scrollbar {
    width: 8px; /* Width of the scrollbar */
}

.modal-body::-webkit-scrollbar-track {
    background: #f1f1f1; /* Color of the track */
}

.modal-body::-webkit-scrollbar-thumb {
    background: #888; /* Color of the scrollbar */
    border-radius: 4px; /* Rounded corners */
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: #555; /* Color of the scrollbar on hover */
}

/* Center nav links in mobile view */
@media (max-width: 991.98px) {
    .navbar-collapse {
        display: flex;
        flex-direction: column;
    }
    
    /* Center the main nav links */
    .navbar-nav.main-links {
        width: 100%;
        justify-content: center;
        flex-direction: row;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }
    
    /* Center the right-aligned icons */
    .navbar-nav.right-icons {
        width: 100%;
        justify-content: center;
        flex-direction: row;
        gap: 15px;
    }
    
    .nav-item {
        margin: 0 5px;
    }
    
    /* Center dropdown menu */
    .dropdown-menu {
        text-align: center;
    }

    /* Notification Modal Styling */
.modal-content {
    border: none;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
}

.unread-notification {
    background-color: rgba(255, 144, 43, 0.1); /* Light orange background */
    border-left: 4px solid #FF902B;
}

.read-notification {
    background-color: white;
    border-left: 4px solid #e9ecef;
}

.notification-item:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}

.modal-header {
    padding: 15px 20px;
}

.modal-body {
    padding: 0;
}

.modal-footer {
    padding: 12px 20px;
}

/* Custom scrollbar for modal */
.modal-body::-webkit-scrollbar {
    width: 6px;
}

.modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.modal-body::-webkit-scrollbar-thumb {
    background: #FF902B;
    border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: #e67e22;
}


#markAsReadBtn {
    background-color: #FF902B;
    border-color: #FF902B;
}

#markAsReadBtn:hover {
    background-color: #e67e22;
    border-color: #e67e22;
}

#clearNotificationsBtn:hover {
    background-color: #dc3545;
    color: white;
}

.mark-as-read {
    background: none;
    border: none;
    padding: 0;
    margin-left: 10px;
}

.mark-as-read:hover {
    opacity: 0.8;
}
}
    </style>
    <title>Change Profile</title>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-default p- shadow-bottom">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <img src="./../../components/icon/kahel-cafe-logo.png" alt="MyWebsite" class="img-fluid" style="max-height: 60px;">
        </a>
        <button class="navbar-toggler text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
    <div class="d-flex flex-column flex-lg-row justify-content-lg-end align-items-center w-100">
        
        <!-- Main navigation links -->
        <ul class="navbar-nav main-links mb-2 mb-lg-0 me-lg-3 text-center text-lg-end">
            <li class="nav-item">
                <a class="nav-link" href="./../../user/views/index.php">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="./../../user/views/reservation.php">Reservation</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="./../../user/views/order-now.php">Order-now</a>
            </li>
        </ul>

        <!-- Right-aligned icons -->
        <ul class="navbar-nav right-icons">
            <!-- Notification Icon -->
            <li class="nav-item position-relative">
                <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#notificationModal">
                    <i class="fa-solid fa-bell" id="noti"></i>
                    <?php if ($notificationCount > 0): ?>
                        <span class="notification-count"><?= $notificationCount; ?></span>
                    <?php endif; ?>
                    <audio id="notificationSound" src="./../../admin/user/notification-sound.mp3"></audio>
                </a>
            </li>

            <!-- Cart Icon -->
            <li class="nav-item position-relative">
                <a class="nav-link" href="./../../user/views/cart.php">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-count"><?= $cartCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <!-- User Menu -->
            <?php if ($firstName): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= htmlspecialchars($firstName); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changeProfileModal"><i class="fa-regular fa-user me-2"></i>Change Profile</a></li>
                        <li><a class="dropdown-item" href="order-track.php"><i class="fa-solid fa-truck-fast me-2"></i>Track Order</a></li>
                        <li><a class="dropdown-item" href="reservation_track.php"><i class="fa-solid fa-calendar-check me-2"></i>Track Reservation</a></li>
                        <li><a class="dropdown-item" href="history.php"><i class="fa-solid fa-clock-rotate-left me-2"></i>History</a></li>
                        <li><a class="dropdown-item" href="?logout"><i class="fa-solid fa-power-off me-2"></i>Logout</a></li>
                    </ul>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="login.php">Login</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

</nav> 

<!-- Modal for Changing Profile -->
<div class="modal fade" id="changeProfileModal" tabindex="-1" aria-labelledby="changeProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeProfileModalLabel">Change Your Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <!-- Profile Form (Can be included from change_profile.php) -->
                <?php include '../views/change_profile.php'; ?>
            </div>
        </div>
    </div>
</div>
 
<!-- Notification Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header" style="background-color: #FF902B; color: white;">
                <h5 class="modal-title" id="notificationModalLabel">
                    <i class="fas fa-bell me-2"></i>Notifications
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="max-height: 400px; overflow-y: auto;">
                <?php if (!empty($notifications)): ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item p-3 border-bottom <?= $notification['is_read'] == 0 ? 'unread-notification' : 'read-notification'; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <p class="mb-1 <?= $notification['is_read'] == 0 ? 'fw-bold' : ''; ?>">
                                        <?= htmlspecialchars($notification['message']); ?>
                                    </p>
                                    <small class="text-muted">
                                        <i class="far fa-clock me-1"></i><?= $notification['created_at']; ?>
                                    </small>
                                </div>
                                <?php if ($notification['is_read'] == 0): ?>
                                    <button class="btn btn-sm mark-as-read" data-id="<?= $notification['id']; ?>" title="Mark as read">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="far fa-bell-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No new notifications.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer d-flex justify-content-between" style="background-color: #f8f9fa;">
                <button type="button" class="btn btn-outline-danger" id="clearNotificationsBtn">
                    <i class="fas fa-trash-alt me-1"></i>Clear All
                </button>
                <button type="button" class="btn btn-primary" id="markAsReadBtn">
                    <i class="fas fa-check-circle me-1"></i>Mark All as Read
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>




function checkAndPlayNotification() {
   
    const notificationCount = <?= $notificationCount; ?>; 

    if (notificationCount > 0) {
        const sound = document.getElementById('notificationSound');
        sound.play();  
    }
}




setInterval(checkAndPlayNotification, 3000);


$(document).ready(function() {
    // Mark all notifications as read
    $('#markAsReadBtn').on('click', function() {
        $.ajax({
            url: './../../admin/user/mark_notifications.php',
            type: 'POST',
            data: { action: 'markAllAsRead' },
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    // Update UI without reloading
                    $('.unread-notification').removeClass('unread-notification').addClass('read-notification');
                    $('.unread-notification p').removeClass('fw-bold');
                    $('.notification-count').hide();
                    $('.mark-as-read').remove();
                    
                    // Show toast notification
                    showToast('All notifications marked as read', 'success');
                } else {
                    showToast('Failed to mark notifications as read', 'error');
                }
            }
        });
    });

    // Clear all notifications
    $('#clearNotificationsBtn').on('click', function() {
        if (confirm('Are you sure you want to clear all notifications?')) {
            $.ajax({
                url: './../../admin/user/clear_notifications.php',
                type: 'POST',
                data: { action: 'clearAllNotifications' },
                success: function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        // Update UI without reloading
                        $('.modal-body').html(`
                            <div class="text-center py-4">
                                <i class="far fa-bell-slash fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No new notifications.</p>
                            </div>
                        `);
                        $('.notification-count').hide();
                        
                        // Show toast notification
                        showToast('All notifications cleared', 'success');
                    } else {
                        showToast('Failed to clear notifications', 'error');
                    }
                }
            });
        }
    });

    // Mark single notification as read
    $(document).on('click', '.mark-as-read', function(e) {
        e.stopPropagation();
        const notificationId = $(this).data('id');
        const notificationItem = $(this).closest('.notification-item');
        
        $.ajax({
            url: './../../admin/user/mark_notifications.php',
            type: 'POST',
            data: { action: 'markAsRead', id: notificationId },
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    // Update UI
                    notificationItem.removeClass('unread-notification').addClass('read-notification');
                    notificationItem.find('p').removeClass('fw-bold');
                    $(this).remove();
                    
                    // Update notification count
                    const currentCount = parseInt($('.notification-count').text());
                    if (currentCount > 1) {
                        $('.notification-count').text(currentCount - 1);
                    } else {
                        $('.notification-count').hide();
                    }
                }
            }
        });
    });

    // Toast notification function
    function showToast(message, type) {
        const toast = $(`
            <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `);
        
        $('.toast-container').append(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
});
$(document).ready(function() {
    function updateCartCount() {
        $.ajax({
            url: '',
            type: 'GET',
            success: function(response) {
                const data = JSON.parse(response);
                if (data.cartCount > 0) {
                    $('.cart-count').text(data.cartCount).show();
                } else {
                    $('.cart-count').hide();
                }
            }
        });
    }

    // Update cart count every 10 seconds
    setInterval(updateCartCount, 10000);

    // Initial call to update cart count
    updateCartCount();
});

function addToCart(productId) {
    $.ajax({
        url: 'add_to_cart.php',
        type: 'POST',
        data: { productId: productId },
        success: function(response) {
            // Handle success response
            updateCartCount(); // Update cart count immediately
        }
    });
}

function removeFromCart(cartItemId) {
    $.ajax({
        url: 'remove_from_cart.php',
        type: 'POST',
        data: { cartItemId: cartItemId },
        success: function(response) {
            // Handle success response
            updateCartCount(); // Update cart count immediately
        }
    });
}
</script>

</body>
</html>