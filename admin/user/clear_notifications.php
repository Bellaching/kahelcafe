<?php
include './../../connection/connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

$user_id = $_SESSION['user_id'];

$query = "DELETE FROM notification WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();

echo "Notifications cleared";

$stmt->close();
$conn->close();
?>