<?php
session_start();
include './../../connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];

if ($_POST['action'] === 'markAllAsRead') {
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $result = $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => $result]);
} elseif ($_POST['action'] === 'markAsRead' && isset($_POST['id'])) {
    $notificationId = $_POST['id'];
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notificationId, $userId);
    $result = $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => $result]);
}
?>