<?php
include './../user/authenticate.php'; 

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
            overflow-x: hidden; /* Prevent horizontal scroll */
        }
        .navbar-default {
            background-color: white;
            border-color: #e7e7e7;
        }
        .navbar-nav > li > a {
            color: black;
            text-decoration: none;
            padding: 10px 15px; /* Padding for nav links */
        }
        .navbar-nav > li > a:hover,
        .dropdown-menu > li > a:hover {
            background-color: #f8f9fa; /* Change this to your desired hover color */
            color: black; /* Maintain text color on hover */
        }
        .dropdown-menu > li > a {
            padding: 10px 15px; /* Padding for dropdown items */
        }
        .shadow-bottom {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08); /* More pronounced shadow */
        }
        .navbar-nav {
            white-space: nowrap; /* Prevent line breaks in nav items */
        }
        .dropdown-menu {
            min-width: 200px; /* Set a minimum width for dropdowns if needed */
        }
        .navbar-collapse {
            margin-left: -15px; /* Adjust this value to move the navbar to the left */
        }
    </style>
    <title>Navigation</title>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-default p-1 shadow-bottom">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <img src="./../../components/icon/kahel-cafe-logo.png" alt="MyWebsite" class="img-fluid" style="max-height: 60px;">
        </a>
        <button class="navbar-toggler text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon text-dark"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end text-dark" id="navbarNav">
            <ul class="navbar-nav flex-row">
                <!-- Owner Side -->
               
                <?php if ($role === 'owner'): ?>
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link dropdown-toggle text-black" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Admin side</a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item text-black" href="./../views/accountManagement.php">Account Management</a></li>
                            <li><a class="dropdown-item text-black" href="./../views/client.php">Client Management</a></li>
                            <li><a class="dropdown-item text-black" href="./../views/index.php">Order Management</a></li>
                            <li><a class="dropdown-item text-black" href="./../views/reservation.php">Reservation Management</a></li>
                            <li><a class="dropdown-item text-black" href="./../views/report.php">Performance Report</a></li>
                            <li><a class="dropdown-item text-black" href="../views/menuManagement.php">Menu Management</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link dropdown-toggle text-black" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-bell"></i>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="notificationDropdown"></ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-black" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?php echo htmlspecialchars($username); ?>(<?php echo htmlspecialchars($role); ?>)</a>
                        <ul class="dropdown-menu" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item text-black" href="#">Change Profile</a></li>
                          
                            <li><a class="dropdown-item text-black" href="./../views/login.php">Logout</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Staff Side -->
                <?php if ($role === 'staff'): ?>
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link dropdown-toggle text-black" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Admin Side</a>
                        <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                            <li><a class="dropdown-item text-black" href="#">Order Management</a></li>
                            <li><a class="dropdown-item text-black" href="./../views/reservation.php">Reservation Management</a></li>
                            <li><a class="dropdown-item text-black" href="../views/menuManagement.php">Menu Management</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link dropdown-toggle text-black" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-bell"></i>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="notificationDropdown"></ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-black" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?php echo htmlspecialchars($username); ?>(<?php echo htmlspecialchars($role); ?>)</a>
                        <ul class="dropdown-menu" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item text-black" href="#">Change Profile</a></li>
                           
                            <li><a class="dropdown-item text-black" href="./../views/login.php">Logout</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

               

            </ul>
        </div>
    </div>
</nav>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
