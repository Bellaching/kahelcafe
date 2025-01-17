<?php
session_start();
include './../../connection/connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ./../../user/views/login.php");
    exit();
}

// Handle checkout process after confirmation
if (isset($_POST['confirm_checkout'])) {
    // Retrieve user details
    $userId = $_SESSION['user_id'];
    $transactionId = generateTransactionId($conn); // Function to generate unique transaction ID
    $userNote = $_POST['note'] ?? '';
    $reservationType = $_POST['reservation_type'] ?? '';
    $clientFullName = getClientFullName($conn, $userId); // Function to get full name

    // Insert order items into 'orders' table
    foreach ($_SESSION['cart'] as $item) {
        $totalPrice = $item['price'] * $item['quantity'];  // Store the calculated value in a variable

        $stmt = $conn->prepare("INSERT INTO orders (user_id, item_name, size, temperature, quantity, note, total_price, transaction_id, reservation_type, client_full_name, status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'For Confirmation')");
        $stmt->bind_param("isssiissss", $userId, $item['name'], $item['size'], $item['temperature'], $item['quantity'], $userNote, $totalPrice, $transactionId, $reservationType, $clientFullName);
        
        $stmt->execute();
    }

    // Clear cart after successful checkout
    unset($_SESSION['cart']);

    // Redirect or show a confirmation message
    header("Location: order-success.php?transaction_id=" . $transactionId);
    exit();
}
function generateTransactionId($conn) {
    do {
        $transactionId = strtoupper(bin2hex(random_bytes(6))); // Generate unique ID
        $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE transaction_id = ?");
        $stmt->bind_param("s", $transactionId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
    } while (isset($count) && $count > 0); // Make sure $count is set before checking its value

    return $transactionId;
}

function getClientFullName($conn, $userId) {
    $query = "SELECT firstname, lastname FROM client WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['firstname'] . ' ' . $row['lastname'];
    }
    return "Unknown";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
    <style>
        .modal-content {
            border-radius: 10px;
        }
        .checkout-btn {
            background-color: #FF902A;
            border: none;
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h3>Your Order</h3>
        
        <?php if (empty($_SESSION['cart'])): ?>
            <p>Your cart is empty. Please add items to your cart first.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <button class="btn checkout-btn" data-bs-toggle="modal" data-bs-target="#checkoutModal">Checkout</button>

            <!-- Checkout Confirmation Modal -->
            <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="checkoutModalLabel">Are you sure you want to checkout?</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="checkout.php">
                                <div class="mb-3">
                                    <label for="note" class="form-label">Order Note</label>
                                    <textarea id="note" name="note" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="reservation-type" class="form-label">Reservation Type</label>
                                    <select id="reservation-type" name="reservation_type" class="form-select">
                                        <option value="Over the counter">Over the counter</option>
                                        <option value="Pickup">Pickup</option>
                                    </select>
                                </div>
                                <button type="submit" name="confirm_checkout" class="btn btn-primary">Confirm Checkout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
