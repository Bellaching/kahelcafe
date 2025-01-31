<?php
include './../../connection/connection.php';
include './../inc/topNav.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view this page.");
}

$userId = $_SESSION['user_id']; // Fetch the user ID from the session

// Handle cancel order request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $orderId = $_POST['order_id'];

    // Update the order status to "Cancelled"
    $cancelQuery = "UPDATE Orders SET status = 'cancelled' WHERE order_id = ?";
    $stmt = $conn->prepare($cancelQuery);

    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $stmt->close();
}

// Handle receipt upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_receipt'])) {
    $orderId = $_POST['order_id'];

    if (!empty($_FILES['receipt']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png'];
        $fileType = $_FILES['receipt']['type'];
        $fileName = basename($_FILES['receipt']['name']);
        $targetDir = './../../uploads/';
        $targetFile = $targetDir . $fileName;

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $targetFile)) {
                // Save the receipt file name in the database
                $uploadQuery = "UPDATE Order_Items SET receipt = ? WHERE order_id = ?";
                $stmt = $conn->prepare($uploadQuery);

                if (!$stmt) {
                    die("SQL Error: " . $conn->error);
                }

                $stmt->bind_param("si", $fileName, $orderId);
                $stmt->execute();
                $stmt->close();

                echo "<div class='alert alert-success'>Receipt uploaded successfully!</div>";
            } else {
                echo "<div class='alert alert-danger'>Failed to upload the receipt.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Only JPG and PNG files are allowed.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Please select a file to upload.</div>";
    }
}

// Fetch the most recent order for the logged-in user
$query = "SELECT * FROM Orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order || $order['status'] === 'cancelled') {
    echo "<div class='container text-center mt-5'>
            <h4>The order has been cancelled.</h4>
            <p>We're sorry, but no current orders are available. 😔</p>
            <p>The reason why the order was cancelled:</p>
            <ul class='text-start'>
              <li>You cancelled the order.</li>
              <li>The shop cancelled your order due to one of the following reasons:
                <ul>
                  <li>Payment was not completed or confirmed.</li>
                  <li>The item is no longer available.</li>
                  <li>Suspected fake or invalid payment details.</li>
                  <li>Technical issues at the shop's end.</li>
                </ul>
              </li>
            </ul>
            <p>If you have any questions or believe this was a mistake, please contact support for assistance.</p>
          </div>";
    exit();
}

// Fetch order items for the most recent order
$query = "SELECT * FROM Order_Items WHERE order_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

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

        <?php if ($order['status'] === 'payment'): ?>
            <form method="POST" enctype="multipart/form-data" class="mt-3">
                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                <div class="mb-3">
                    <label for="receipt" class="form-label">Upload Receipt (JPG, PNG only):</label>
                    <input type="file" class="form-control" id="receipt" name="receipt" required>
                </div>
                <button type="submit" name="upload_receipt" class="btn btn-success">Upload Receipt</button>
            </form>
        <?php endif; ?>

        <form method="POST" class="mt-3">
            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">Cancel Order</button>
        </form>

        <!-- Modal Structure -->
        <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelOrderModalLabel">Confirm Cancellation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to cancel this order?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                            <button type="submit" name="cancel_order" class="btn btn-danger">Yes, Cancel Order</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <a href="order-now.php" class="btn btn-primary mt-3">Go back to Shopping</a>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
