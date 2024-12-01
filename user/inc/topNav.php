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
    header("Location: login.php");
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

    // Fetch the cart count specific to the logged-in user
    $sql = "SELECT COUNT(*) FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($cartCount);
    $stmt->fetch();
    $stmt->close();
} else {
    $cartCount = 0; // Default if the user is not logged in
}

include '../views/change_profile.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        
        /* Cart count styling */
        .cart-count {
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

        /* Animation for cart count */
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
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="./../../user/views/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="./../../user/views/reservation.php">Reservation</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="./../../user/views/order-now.php">Order-now</a>
                </li>
                <li class="nav-item position-relative">
                    <a class="nav-link" href="./../../user/views/cart.php">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-count"><?= $cartCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if ($firstName): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= htmlspecialchars($firstName); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changeProfileModal"><i class="fa-regular fa-user me-2"></i>Change Profile</a></li>
                            <li><a class="dropdown-item" href="order-track.php" data-bs-toggle="" data-bs-target="#"><i class="fa-regular fa-user me-2"></i>Track Order</a></li>
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

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
