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

    // // Fetch the notification count (unread notifications)
    // $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0";
    // $stmt = $conn->prepare($sql);
    // $stmt->bind_param("i", $userId);
    // $stmt->execute();
    // $stmt->bind_result($notificationCount);
    // $stmt->fetch();
    // $stmt->close();

    // Fetch all notifications for the user
    // $sql = "SELECT id, message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
    // $stmt = $conn->prepare($sql);
    // $stmt->bind_param("i", $userId);
    // $stmt->execute();
    // $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    // $stmt->close();
} else {
    $cartCount = 0; // Default if the user is not logged in
    $notificationCount = 0; // Default if the user is not logged in
    $notifications = []; // Empty array if the user is not logged in
}

// $sql = "SELECT id, message, created_at, is_read FROM notifications WHERE user_id = ? ORDER BY created_at DESC ";
// $stmt = $conn->prepare($sql);
// $stmt->bind_param("i", $userId);
// $stmt->execute();
// $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// $stmt->close();

include '../views/change_profile.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Profile</title>
    
    <!-- CSS Links -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel/slick/slick.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel/slick/slick-theme.css"/>

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

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }

        .unread-notification {
            background-color: #FF902B;
            border-radius: 5px;
            padding: 10px;
            color: white;
        }

        .unread-notification:hover {
            background-color: #e67e22;
        }

        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        @media (max-width: 991.98px) {
            .navbar-collapse {
                display: flex;
                flex-direction: column;
            }
            
            .navbar-nav.main-links {
                width: 100%;
                justify-content: center;
                flex-direction: row;
                flex-wrap: wrap;
                margin-bottom: 10px;
            }
            
            .navbar-nav.right-icons {
                width: 100%;
                justify-content: center;
                flex-direction: row;
                gap: 15px;
            }
            
            .nav-item {
                margin: 0 5px;
            }
            
            .dropdown-menu {
                text-align: center;
            }
        }

        /* Fix for navbar toggler icon */
        .navbar-toggler {
            border: none;
            padding: 0.25rem 0.75rem;
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-default p-0 shadow-bottom">
    <div class="container-fluid">
        <a class="navbar-brand me-auto" href="#">
            <img src="./../../components/icon/kahel-cafe-logo.png" alt="MyWebsite" class="img-fluid" style="max-height: 60px;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- All navigation items grouped on the right -->
            <ul class="navbar-nav ms-auto d-flex align-items-center">
                <!-- Main navigation links -->
                <li class="nav-item">
                    <a class="nav-link" href="./../../user/views/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="./../../user/views/reservation.php">Reservation</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="./../../user/views/order-now.php">Order Now</a>
                </li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Notification Icon -->
                    <li class="nav-item position-relative mx-2" style="top: -5px;">
    <?php include "noti.php" ?>
</li>


                    <!-- Cart Icon -->
                    <li class="nav-item position-relative mx-2">
                        <a class="nav-link" href="./../../user/views/cart.php">
                          <!-- Orange Shopping Cart Icon (SVG) -->
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M6 2L3 6V20C3 20.5304 3.21071 21.0391 3.58579 21.4142C3.96086 21.7893 4.46957 22 5 22H19C19.5304 22 20.0391 21.7893 20.4142 21.4142C20.7893 21.0391 21 20.5304 21 20V6L18 2H6Z" fill="#FF902B" stroke="#FF902B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M3 6H21" stroke="#FF902B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M16 10C16 11.0609 15.5786 12.0783 14.8284 12.8284C14.0783 13.5786 13.0609 14 12 14C10.9391 14 9.92172 13.5786 9.17157 12.8284C8.42143 12.0783 8 11.0609 8 10" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
                            <?php if ($cartCount > 0): ?>
                                <span class="cart-count"><?= $cartCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- User Menu -->
                <?php if ($firstName): ?>
                    <li class="nav-item dropdown ms-2">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="me-1"><?= htmlspecialchars($firstName); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changeProfileModal"><i class="fa-regular fa-user me-2"></i>Change Profile</a></li>
                            <li><a class="dropdown-item" href="order-track.php"><i class="fa-solid fa-truck-fast me-2"></i>Track Order</a></li>
                            <li><a class="dropdown-item" href="reservation_track.php"><i class="fa-solid fa-calendar-check me-2"></i>Track Reservation</a></li>
                            <li><a class="dropdown-item" href="history.php"><i class="fa-solid fa-clock-rotate-left me-2"></i>History</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="?logout"><i class="fa-solid fa-power-off me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-2">
                        <a class="nav-link btn btn-outline px-3 py-1 text-light" style="background-color: #FF902B;" href="login.php">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
    /* Custom styles for professional right-aligned navigation */
    .navbar {
        padding: 0.5rem 1rem;
        background-color: #fff;
    }
    
    .nav-link {
        font-weight: 500;
        padding: 0.5rem 1rem;
        color: #333;
        display: flex;
        align-items: center;
    }
    
    .nav-link:hover {
        color: #000;
    }
    
    .dropdown-menu {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        min-width: 220px;
    }
    
    .dropdown-item {
        padding: 0.5rem 1.5rem;
    }
    
    .cart-count {
        position: absolute;
        top: 0;
        right: 0;
        font-size: 0.7rem;
        background-color: #dc3545;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        transform: translate(25%, -25%);
    }
    
    /* Mobile view adjustments */
    @media (max-width: 991.98px) {
        .navbar-nav {
            width: 100%;
            padding: 0.5rem 0;
            align-items: flex-end !important;
        }
        
        .nav-item {
            width: auto;
            margin: 0.25rem 0;
        }
        
        .dropdown-menu {
            position: static !important;
            transform: none !important;
            width: 100%;
            border: none;
            box-shadow: none;
            background-color: #f8f9fa;
        }
        
        .navbar-nav.ms-auto {
            gap: 0.5rem;
            justify-content: flex-end;
        }
    }
</style>
<!-- Modal for Changing Profile -->
<div class="modal fade" id="changeProfileModal" tabindex="-1" aria-labelledby="changeProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeProfileModalLabel">Change Your Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php include '../views/change_profile.php'; ?>
            </div>
        </div>
    </div>
</div>
 
<?php if (isset($_SESSION['user_id'])): ?>
<!-- Notification Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-light" id="notificationModalLabel">Notifications</h5>
                <button type="button" class="btn-close text-light" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
                <?php if (!empty($notifications)): ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item mb-3 p-3 <?= $notification['is_read'] == 0 ? 'unread-notification' : ''; ?>">
                            <a href="order-track.php?notification_id=<?= $notification['id']; ?>" style="text-decoration: none; color: inherit;">
                                <p><?= htmlspecialchars($notification['message']); ?></p>
                                <small class="text-muted"><?= $notification['created_at']; ?></small>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No new notifications.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="markAsReadBtn">Mark All as Read</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/slick-carousel/slick/slick.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize all Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Remove the manual collapse toggle handler as it might conflict with Bootstrap's built-in functionality
    // Bootstrap's built-in collapse should handle this automatically

    <?php if (isset($_SESSION['user_id'])): ?>
    function checkAndPlayNotification() {
        const notificationCount = <?= $notificationCount; ?>; 

        if (notificationCount > 0) {
            const sound = document.getElementById('notificationSound');
            sound.play();  
        }
    }

    // Check for notifications every 3 seconds
    setInterval(checkAndPlayNotification, 3000);

    // Mark all notifications as read
    $('#markAsReadBtn').on('click', function() {
        $.ajax({
            url: './../../admin/user/index.php',
            type: 'POST',
            data: { action: 'markAllAsRead' },
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    alert('All notifications marked as read.');
                    location.reload();
                } else {
                    alert('Failed to mark notifications as read.');
                }
            }
        });
    });

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
    updateCartCount();

    function addToCart(productId) {
        $.ajax({
            url: 'add_to_cart.php',
            type: 'POST',
            data: { productId: productId },
            success: function(response) {
                updateCartCount();
            }
        });
    }

    function removeFromCart(cartItemId) {
        $.ajax({
            url: 'remove_from_cart.php',
            type: 'POST',
            data: { cartItemId: cartItemId },
            success: function(response) {
                updateCartCount();
            }
        });
    }
    <?php endif; ?>
});
</script>

</body>
</html>