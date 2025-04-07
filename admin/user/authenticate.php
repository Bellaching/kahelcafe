<?php
// Start the session
session_start();

// Include the database connection file
include './../../connection/connection.php';

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the required session variables are set
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    $_SESSION['message'] = "You are not allowed here.";  
    header('Location: ./../views/login.php');  
    exit();
}

// Retrieve session variables
$role = $_SESSION['role'];  
$user_id = $_SESSION['user_id'];  

// Determine which table to query based on role
$table = ($role === 'owner' || $role === 'staff') ? 'admin_list' : 'users';

// Prepare and execute the query to fetch user details
$query = "SELECT username, role FROM $table WHERE id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    // Log error if the statement preparation fails
    error_log("Error preparing statement: " . $conn->error);
    $_SESSION['message'] = "Internal server error.";  
    header('Location: ./../views/login.php');
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $fetched_role);  

// Fetch data from the database
if (!$stmt->fetch()) {
    // Handle the case where the user does not exist
    $_SESSION['message'] = "User not found.";  
    header('Location: ./../views/login.php');  
    exit();
}
$stmt->close();

// Verify if the fetched role matches the session role
if ($role !== $fetched_role) {
    $_SESSION['message'] = "Role mismatch.";  
    header('Location: ./../views/login.php'); 
    exit();
}

// Store username in session for later use
$_SESSION['username'] = $username;

// Debugging: Log session and user data (remove in production)
error_log("Session Data: " . print_r($_SESSION, true));
error_log("User Data: User ID: $user_id, Role: $role, Username: $username");
?>