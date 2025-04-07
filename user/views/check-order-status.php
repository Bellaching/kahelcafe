<?php
include './../../connection/connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

if (!isset($_GET['order_id'])) {
    die(json_encode(['error' => 'Order ID not provided']));
}

$orderId = (int)$_GET['order_id'];

// Fetch the current status of the order
$query = "SELECT status FROM orders WHERE order_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die(json_encode(['error' => 'Database error']));
}

$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    die(json_encode(['error' => 'Order not found']));
}

header('Content-Type: application/json');
echo json_encode(['status' => $order['status']]);

