<?php
include './../../connection/connection.php';

// Ensure we have an order_id
if (!isset($_GET['order_id'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
    exit();
}

$orderId = $_GET['order_id'];

// Fetch current status
$query = "SELECT status FROM Orders WHERE order_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'not_found']);
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

header('Content-Type: application/json');
echo json_encode(['status' => $order['status']]);
?>