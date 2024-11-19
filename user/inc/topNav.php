<?php
session_start();

// Check if the user is logged in and if they clicked the logout link
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

include './../../connection/connection.php';




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
        .navbar-nav > li > a:hover {
            background-color: #f8f9fa;
            color: black;
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
                    <a class="nav-link" href="./../../user/views/cart.php"><i class="fa-solid fa-cart-shopping"></i></a>
                </li>
                <?php if ($firstName): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= htmlspecialchars($firstName); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changeProfileModal"><i class="fa-regular fa-user me-2"></i>Change Profile</a></li>
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



<!-- Optional: Add script to toggle password fields visibility -->
<script>
    document.getElementById("changePassword").addEventListener("change", function() {
        var passwordFields = document.querySelector(".password-fields");
        passwordFields.style.display = this.checked ? "block" : "none";
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
