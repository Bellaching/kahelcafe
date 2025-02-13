<?php
include './../../connection/connection.php';
include './../inc/topNav.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view this page.");
}

$userId = $_SESSION['user_id']; // Fetch the user ID from the session

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'], $_POST['rating'])) {
    $itemId = (int)$_POST['item_id'];
    $rating = (int)$_POST['rating'];

    // Check if item exists in order_items
    $checkQuery = "SELECT 1 FROM order_items WHERE item_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Error: item_id not found or not linked to menu1.");
    }
    $stmt->close();

    // Correct Update Query
    $query = "UPDATE menu1 m
              JOIN order_items oi ON m.id = oi.item_id
              SET m.rating = ?
              WHERE oi.item_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $rating, $itemId);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "Rating updated successfully!";
        } else {
            echo "No rows were affected. Check if the item ID exists.";
        }
    } else {
        echo "Error updating rating: " . $stmt->error;
    }

    $stmt->close();
    exit(); // Prevent further execution
}


// Fetch all order items
$query = "SELECT oi.* FROM order_items oi 
          JOIN Orders o ON oi.order_id = o.order_id
          WHERE oi.order_id = ?";

$stmt = $conn->prepare($query);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $order['order_id']);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all order items
$orderItems = [];
while ($row = $result->fetch_assoc()) {
    $orderItems[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
<div class="container-fluid">
    <table class="table custom-table">
        <thead>
            <tr>
                <th>Order Item</th>
                <th>Size</th>
                <th>Temperature</th>
                <th>Quantity</th>
                <th>Price</th>
                <?php if ($order['status'] === 'rate us'): ?>
                <th>Rate Us</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orderItems as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td><?php echo htmlspecialchars($item['size']); ?></td>
                <td><?php echo htmlspecialchars($item['temperature']); ?></td>
                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                <?php if ($order['status'] === 'rate us'): ?>
                    <td>
    <div class="star-rating" data-item-id="<?php echo isset($item['id']) ? htmlspecialchars($item['id']) : '0'; ?>">
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="fa-star fa <?php echo ($i <= (int)($item['rating'] ?? 0)) ? 'fa-solid' : 'fa-regular'; ?>" data-rate="<?php echo $i; ?>"></i>
        <?php endfor; ?>
    </div>
</td>

                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
$(document).ready(function() {
    $(".star-rating i").on("click", function() {
        let itemId = $(this).parent().data("item-id");
        let rating = $(this).data("rate");

        $.post("", { item_id: itemId, rating: rating }, function(response) {
            alert(response);
        });

        // Update UI
        $(this).siblings().removeClass("fa-solid").addClass("fa-regular");
        $(this).addClass("fa-solid");
        $(this).prevAll().addClass("fa-solid").removeClass("fa-regular");
    });
});
</script>

</body>
</html>
