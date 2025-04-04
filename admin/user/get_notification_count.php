<?php
include './../../connection/connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['count' => 0]));
}

$user_id = $_SESSION['user_id'];

$query = "SELECT COUNT(*) as count FROM notification WHERE user_id = ? AND is_read = FALSE";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'];

echo json_encode(['count' => $count]);

$stmt->close();
$conn->close();
?>