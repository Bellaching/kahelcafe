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
                    <i class="fa-solid fa-bell"></i>
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
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
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
                    location.reload(); // Reload the page to reflect changes
                } else {
                    alert('Failed to mark notifications as read.');
                }
            }
        });
    });
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