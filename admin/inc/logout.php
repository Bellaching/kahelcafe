<?php
// logout.php
session_start();
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session

// Redirect to the login or home page
header("Location: ./../views/login.php"); // Update path as necessary
exit();
?>
