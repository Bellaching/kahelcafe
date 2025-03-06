<?php
include './../../connection/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = (int)$_POST['order_id'];

    // Update the order status to "completed"
    $updateQuery = "UPDATE Orders SET status = 'completed' WHERE order_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $stmt->close();

    echo "Order completed successfully!";
} else {
    echo "Invalid request.";
}
?>