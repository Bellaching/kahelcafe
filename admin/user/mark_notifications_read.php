<?php
include './../../connection/connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

$user_id = $_SESSION['user_id'];

$query = "UPDATE notification SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();

echo "Notifications marked as read";

$stmt->close();
$conn->close();
?>