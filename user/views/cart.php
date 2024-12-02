<?php
session_start();
include './../../connection/connection.php';



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
    
   
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $item_id) {
            unset($_SESSION['cart'][$key]); 
            break;
        }
    }

    echo json_encode(['success' => true]);
}




$cartEmpty = empty($_SESSION['cart']);

if (!isset($_SESSION['user_id'])) {
    $_SESSION['cart_error'] = "You need to log in to add items to the cart.";
    header("Location: ./../../user/views/login.php");
    exit();
}




$totalPrice = 0;


if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $totalPrice += $item['price'] * $item['quantity'];
    }
}


$cartEmpty = empty($_SESSION['cart']);


$clientFullName = 'Unknown';
$clientId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if ($clientId) {
    $query = "SELECT firstname, lastname FROM client WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $clientFullName = " " . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']);
    }
} else {
    echo "User is not logged in. Please log in to view your cart.";
}


function generateTransactionId($conn) {
    do {
        $transactionId = strtoupper(bin2hex(random_bytes(6))); // 12-character random ID
        // Check if the transaction ID already exists in the database
        $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE transaction_id = ?");
        $stmt->bind_param("s", $transactionId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if (!isset($count)) {
            $count = 0; // Default to 0 if not set, to avoid unassigned variable usage
        }
    } while ($count > 0); // If exists, regenerate the transaction ID

    return $transactionId;
}

if (isset($_POST['checkout'])) {
    // Collect note and reservation type from the form
    $userNote = isset($_POST['note']) ? $_POST['note'] : '';
    $reservationType = isset($_POST['reservation_type']) ? $_POST['reservation_type'] : '';

    // Generate a single unique transaction ID for this checkout
    $transactionId = generateTransactionId($conn);

    // Insert each cart item into the orders table with the same transaction ID
    foreach ($_SESSION['cart'] as $item) {
        $itemName = $item['name'];
        $itemSize = isset($item['size']) ? $item['size'] : '';
        $itemTemperature = isset($item['temperature']) ? $item['temperature'] : '';
        $itemQuantity = $item['quantity'];
        $itemPrice = $item['price'];
        $totalItemPrice = $itemPrice * $itemQuantity;

        // Prepare and execute the insert statement with the shared transaction ID
        $stmt = $conn->prepare("INSERT INTO orders (user_id, item_name, size, temperature, quantity, note, total_price, transaction_id, reservation_type, client_full_name) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssiissss", $clientId, $itemName, $itemSize, $itemTemperature, $itemQuantity, $userNote, $totalItemPrice, $transactionId, $reservationType, $clientFullName);
        $stmt->execute();
    }

    // Delete all items from the cart table for the current user
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $stmt->close();

    // Clear the cart session after successful checkout
    unset($_SESSION['cart']);

    // Respond with a success message
    echo json_encode(['success' => true, 'message' => 'Order placed successfully and cart cleared.', 'transaction_id' => $transactionId]);
    exit;
}




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

        .order-underline {
    display: inline-block;
    border-bottom: 3px solid #FF902A;
    padding-bottom: 3px; 
    margin-bottom: 1rem;

}

.proceedBtn{
        background-color: #FF902A;
        border: none;
        border-radius: 15px;
    }

    .order-h4{
        color: #FF902A;
        font-weight: bold;
    }

    .btn-increase,
    .btn-decrease{
        background-color:#FF902A ;
        border: none;
    }


    </style>
</head>
<body>
    
<div class="container-fluid con-cart">
    <div class="back-to-order mb-5">
        <a href="order-now.php" class="text-decoration-none text-dark "><i class="fa-solid fa-arrow-left"></i>  <i class="fa-solid fa-mug-saucer"></i></a>
    </div>

    <h3>Your <span class="order-underline">Order</span></h3>




    <?php if ($cartEmpty): ?>
        <p>Your cart is empty. <a href="order-now.php">Start shopping</a></p>
    <?php else: ?>
        <div class="row">
            <!-- Cart Table Section -->
            <div class="col-lg-8">
    <table class="table cart-table table-striped table-responsive">
        <thead class="thead">
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
                        <input type="text" class="form-control quantity-input text-center" max="<?php echo $menu['quantity']; ?>" value="<?php echo $item['quantity']; ?>" data-id="<?php echo $item['id']; ?>" readonly>
                        <button class="btn btn-secondary btn-sm btn-increase" data-id="<?php echo $item['id']; ?>">+</button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Note Form Section -->

<div class="note-section mt-4">
    <label for="user-note">Note</label>
    <textarea id="user-note" class="form-control" placeholder="Enter your note here" 
              style="border-color: #B3B3B3; border-radius: 10px; height: 150px; resize: none;"></textarea>
</div>




</div>

<!-- Cart Items List Above the Total -->

<div class="col-lg-4">
   
<div class="card ">
       <!-- Seat Reservation -->
       <strong><h4 class="m-3 order-h4">Seat Reservation</h4></strong> 
        <div class="card-body">
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="p-1 text-white text-center" style="background-color: #17A1FA; border-radius: 5px;">
                No Slots
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="p-1 text-white text-center" style="background-color: #07D090; border-radius: 5px;">
                Available
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="p-1 text-white text-center" style="background-color: #E60000; border-radius: 5px;">
                Fully Booked
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="p-1 text-white text-center" style="background-color: #9647FF; border-radius: 5px;">
                Your Reservation
              </div>
            </div>
          </div>
        </div>

        <p class="card-text d-flex justify-content-between align-items-center flex-wrap">
    <label for="reservation-type" class="form-label mb-2 mb-md-0">
        <strong class="fs-6 text-dark">Reservation Type:</strong>
    </label>
    <select id="reservation-type" class="form-control w-auto" required>
        <option value="Over the counter">Over the counter</option>
        <option value="Pickup">Pickup</option>
    </select>
</p>





    <!-- Order Summary Card -->
 
       <strong><h4 class="m-3 order-h4">Order Summary</h4></strong> 
        <div class="card-body">
  
        <p class="d-flex justify-content-between">
    <strong>Name:</strong>
    <span><?php echo $clientFullName; ?></span>
</p>
<!-- <p class="d-flex justify-content-between">
    <strong>Transaction ID:</strong>
    <span><?php echo htmlspecialchars($_SESSION['transaction_id']); ?></span>
</p> -->

            <p class="card-text" id="totalAmount">
            <strong class="fs-5" style="color: #616161;">Total:</strong>

            <strong> <span class="float-end fs-5" style="color: #FF902B;">₱<?php echo number_format($totalPrice, 2); ?></span></strong> 
            </p>
            <div class="text-end">
            <button type="button" id="proceed-to-checkout" class="btn proceedBtn w-100 text-light">Proceed to Checkout</button>

            </div>
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

<!-- Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="checkoutModalLabel">Confirm Checkout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to check out your items?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="proceed-btn">Proceed</button>
            </div>
        </div>
    </div>
</div>


<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
 

$(document).ready(function() {
    $('#proceed-to-checkout').click(function (e) {
        e.preventDefault(); // Prevent form submission

        // Show the confirmation modal
        $('#checkoutModal').modal('show');
    });

    // When the user clicks "Proceed" in the modal
    $('#confirmCheckout').click(function () {
        // Trigger form submission to checkout
        $('#checkoutForm').submit(); // Replace with your checkout form ID if necessary
    });
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

    $('#deleteBtn').click(function() {
    let itemId = $(this).data('id' );
    
    $.post("cart.php", { remove_item: true, item_id: itemId }, function(response) {
        if (response.success) {
            $(`.cart-item[data-id="${itemId}"]`).remove();
            $('#alert').fadeIn().delay(3000).fadeOut();  // Show alert
            $('#deleteModal').modal('hide');
            location.reload();  // Reload the page
        }
    }, 'json');
});


         // Handle Checkout process
         $('#proceed-btn').on('click', function() {
        var userNote = $('#user-note').val();
        var reservationType = $("#reservation-type").val();
        $.ajax({
            method: 'POST',
            url: '',
            data: {
                checkout: true,
                note: userNote,
                reservation_type: reservationType
            },
            success: function(response) {
                var result = JSON.parse(response);
                if (result.success) {
                    alert(result.message);
                    window.location.href = 'order-track.php';
                }
            }
        });
    });


});
</script>

</body>
</html>
