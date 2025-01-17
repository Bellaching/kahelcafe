<?php
session_start();
include './../../connection/connection.php';

if (!isset($_GET['transaction_id'])) {
    echo "Invalid request.";
    exit();
}

$transactionId = $_GET['transaction_id'];

// Fetch order details from the Orders table
$stmt = $conn->prepare("SELECT * FROM Orders WHERE transaction_id = ?");
$stmt->bind_param("s", $transactionId);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    echo "Order not found.";
    exit();
}

// Fetch order items from the Order_Items table
$stmt = $conn->prepare("SELECT * FROM Order_Items WHERE order_id = ?");
$stmt->bind_param("i", $order['order_id']);
$stmt->execute();
$orderItems = $stmt->get_result();
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h3>Order Confirmation</h3>
        <p>Transaction ID: <?php echo htmlspecialchars($order['transaction_id']); ?></p>
        <p>Status: <?php echo htmlspecialchars($order['status']); ?></p>
        <p>Total Price: ₱<?php echo number_format($order['total_price'], 2); ?></p>
        
        <h4>Order Items</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Size</th>
                    <th>Temperature</th>
                    <th>Quantity</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $orderItems->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['size']); ?></td>
                        <td><?php echo htmlspecialchars($item['temperature']); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <a href="order-now.php" class="btn btn-primary">Go back to Shopping</a>
    </div>
</body>
</html>
