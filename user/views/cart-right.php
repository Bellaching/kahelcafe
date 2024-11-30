<?php


// If there is no transaction ID in the session, generate one
if (!isset($_SESSION['transaction_id'])) {
    $_SESSION['transaction_id'] = uniqid('txn_', true); // Generate a unique transaction ID
}

// Initialize totalPrice
$totalPrice = 0;

// Loop through the cart items and calculate the total price
foreach ($_SESSION['cart'] as $item) {
    $totalPrice += $item['price'] * $item['quantity'];
}

// Check if the cart is empty
$cartEmpty = empty($_SESSION['cart']);

// Retrieve client details
$clientFullName = 'Unknown';
$clientId = $_SESSION['user_id'];

$query = "SELECT firstname, lastname FROM client WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $clientId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $clientFullName = " " . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']);
}

$stmt->close();
?>


<div class="col-lg-4">
    <!-- Order Summary Card -->
    <div class="card mt-3">
        <h1 class="m-4">Order Summary</h1>
        <div class="card-body">
            <p><strong>Name:</strong> <?php echo $clientFullName; ?></p>
            <p><strong>Transaction ID:</strong> <?php echo $_SESSION['transaction_id']; ?></p>
            <p class="card-text" id="totalAmount"><strong>Total:</strong> ₱<?php echo number_format($totalPrice, 2); ?></p>
            <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
        </div>
    </div>
</div>
