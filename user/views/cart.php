<?php
session_start();
include './../../connection/connection.php';

// If there is no transaction ID in the session, generate one
if (!isset($_SESSION['transaction_id'])) {
    $_SESSION['transaction_id'] = uniqid('txn_', true); // Generate a unique transaction ID
}

// Handle quantity update without reload (AJAX)
if (isset($_POST['update_quantity'])) {
    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $item_id) {
            $item['quantity'] = $quantity;
            break;
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

// Handle item removal
if (isset($_POST['remove_item'])) {
    $item_id = intval($_POST['item_id']);
    
   
    
    $stmt = $conn->prepare("DELETE FROM cart WHERE item_id = ?");
    $stmt->bind_param("i", $item_id); // Bind the item_id to the query
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    // Remove item from the session cart
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $item_id) {
            unset($_SESSION['cart'][$key]); // Remove item from cart session
            break;
        }
    }

    echo json_encode(['success' => true]);
}


// Initialize totalPrice
$totalPrice = 0;

// Loop through the cart items and calculate the total price
foreach ($_SESSION['cart'] as $item) {
    $totalPrice += $item['price'] * $item['quantity'];
}

// Check if the cart is empty
$cartEmpty = empty($_SESSION['cart']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .con-cart{
            padding: 5%;
        }

        .cart-table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Light shadow */
        }

        .cart-table thead {
            background-color: #FF902A;
            color: white;
        }

        .quantity-buttons {
            display: flex;
            align-items: center;
        }

        .quantity-buttons .btn {
            border-radius: 50%;
            width: 30px;
            height: 30px;
        }

        .quantity-buttons .form-control {
            width: 50px;
            text-align: center;
        }

        .cart-item img {
            border-radius: 10px;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Light shadow */
        }

        /* Modal styling */
        .modal-content {
            border-radius: 10px;
        }

        .back-to-order a, i{
            color: #FF902B;
        }

        .alert-dismissible {
            display: none;
        }
    </style>
</head>
<body>
    
<div class="container-fluid con-cart">
    <div class="back-to-order mb-5">
        <a href="order-now.php" class="text-decoration-none text-dark "><i class="fa-solid fa-arrow-left"></i>  <i class="fa-solid fa-mug-saucer"></i></a>
    </div>

    <h1>Your Shopping Cart</h1>

    <?php if ($cartEmpty): ?>
        <p>Your cart is empty. <a href="order-now.php">Start shopping</a></p>
    <?php else: ?>
        <div class="row">
            <!-- Cart Table Section -->
            <div class="col-lg-8">
    <table class="table cart-table table-striped table-responsive">
        <thead>
            <tr>
                <th>Item</th>
                <th>Price</th>
                <th>Size</th>
                <th>Temperature</th>
                <th>Quantity</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($_SESSION['cart'] as $item): ?>
            <tr class="cart-item" data-id="<?php echo $item['id']; ?>">
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                <td><?php echo isset($item['size']) ? $item['size'] : ''; ?></td>
                <td><?php echo isset($item['temperature']) ? $item['temperature'] : ''; ?></td>
                <td>
                    <div class="input-group quantity-buttons">
                        <button class="btn btn-secondary btn-sm btn-decrease" data-id="<?php echo $item['id']; ?>">-</button>
                        <input type="text" class="form-control quantity-input text-center" value="<?php echo $item['quantity']; ?>" data-id="<?php echo $item['id']; ?>" readonly>
                        <button class="btn btn-secondary btn-sm btn-increase" data-id="<?php echo $item['id']; ?>">+</button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Cart Items List Above the Total -->
<div class="col-lg-4">
    <!-- Total Section -->
    <div class="card mt-3">
        <div class="card-body">
            <strong>Transaction ID:</strong> <?php echo $_SESSION['transaction_id']; ?>
            <h5 class="card-title">Total</h5>
            <p class="card-text" id="totalAmount">₱<?php echo number_format($totalPrice, 2); ?></p>
            <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
        </div>
    </div>
</div>
        </div>
    <?php endif; ?>
</div>

<!-- Alert for Item Removed -->
<div id="alert" class="alert alert-danger alert-dismissible fade show" role="alert">
  Item removed from cart.
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<!-- Modal HTML -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this item?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="deleteBtn">Delete</button>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Handle quantity increase/decrease for specific items
    $('.btn-decrease, .btn-increase').click(function() {
        let itemId = $(this).data('id');
        let $quantityInput = $(`.quantity-input[data-id="${itemId}"]`);
        let currentQuantity = parseInt($quantityInput.val());

        // Adjust quantity
        if ($(this).hasClass('btn-increase')) {
            currentQuantity += 1;
        } else if ($(this).hasClass('btn-decrease') && currentQuantity > 1) {
            currentQuantity -= 1;
        }

        // Update input field
        $quantityInput.val(currentQuantity);

        // AJAX update for new quantity
        $.post("cart.php", { update_quantity: true, item_id: itemId, quantity: currentQuantity }, function() {
            if (currentQuantity === 1) {
                $('#deleteModal').modal('show');
                $('#deleteBtn').data('id', itemId);  // Store item id for removal
            }
        }, 'json');
    });

    // Confirm removal when user clicks 'Delete'
    $('#deleteBtn').click(function() {
        let itemId = $(this).data('id');
        
        $.post("cart.php", { remove_item: true, item_id: itemId }, function(response) {
            if (response.success) {
                $(`.cart-item[data-id="${itemId}"]`).remove();
                $('#alert').fadeIn().delay(3000).fadeOut();  // Show alert
                $('#deleteModal').modal('hide');
            }
        }, 'json');
    });
});
</script>

</body>
</html>
