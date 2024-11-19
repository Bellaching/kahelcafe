<?php
session_start();

include './../../connection/connection.php';

// Check if the user is logged in and if 'role' and 'user_id' are set in the session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    $_SESSION['message'] = "You are not allowed here.";  // Set the session message
    header('Location: ./../views/login.php');  // Redirect to login page
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$query = "SELECT username, role FROM admin_list WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $role);  
$stmt->fetch();
$stmt->close();

?>
