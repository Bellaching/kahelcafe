<?php
session_start();

include './../../connection/connection.php';    

// Fetch cart from database if session expired
if (!isset($_SESSION['cart']) && isset($_SESSION['user_id'])) {
    $clientId = $_SESSION['user_id'];
    $query = "SELECT item_id, item_name, size, temperature, quantity, price , note FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error); // Debugging error
    }
 
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $result = $stmt->get_result();

    $_SESSION['cart'] = [];
    while ($row = $result->fetch_assoc()) {
        $_SESSION['cart'][] = [
            'id' => $row['item_id'],
            'name' => $row['item_name'],
            'size' => $row['size'],
            'temperature' => $row['temperature'],
            'quantity' => $row['quantity'],
            'note' => $row['note'],
            'price' => $row['price'],
        ];
    }
    $stmt->close();
} 

// Retrieve client details
$clientFullName = 'Unknown';
$clientId = $_SESSION['user_id'];

if ($clientId) {
    $query = "SELECT firstname, lastname FROM client WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $clientFullName = htmlspecialchars($row['firstname'] . ' ' . $row['lastname']);
    }
    $stmt->close();
}

function getAvailableTimes($conn, $user_id, $date = null) {
    $times = [];
    $timeQuery = $conn->query("SELECT id AS time_id, time FROM res_time ORDER BY time");
    if (!$timeQuery) {
        die("Time query failed: " . $conn->error);
    }
    
    while ($row = $timeQuery->fetch_assoc()) {
        $times[] = $row;
    }
    
    if ($date) {
        foreach ($times as &$time) {
            $checkQuery = $conn->prepare("
                SELECT res_status, client_id 
                FROM reservation 
                WHERE reservation_date = ? 
                AND reservation_time_id = ?
            ");
            
            if (!$checkQuery) {
                die("Prepare failed: " . $conn->error);
            }
            
            $checkQuery->bind_param("si", $date, $time['time_id']);
            $checkQuery->execute();
            $result = $checkQuery->get_result();
            
            if ($result->num_rows > 0) {
                $status = $result->fetch_assoc();
                if ($status['client_id'] == $user_id) {
                    $time['status'] = 'your_reservation';
                    $time['disabled'] = true;
                } elseif ($status['res_status'] == 'booked') {
                    $time['status'] = 'booked';
                    $time['disabled'] = true;
                }
            } else {
                $time['status'] = 'available';
                $time['disabled'] = false;
            }
            
            $checkQuery->close();
        }
    }
    
    return $times;
}

$selectedDate = $_POST['reservation_date'] ?? '';
$times = getAvailableTimes($conn, $clientId, $selectedDate ? date('Y-m-d', strtotime($selectedDate)) : null);

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    die("User not logged in.");
}

// Fetch the reservation fee from the Orders table
$reservation_fee = 0;
$reservation_fee_query = "SELECT reservation_fee FROM Orders WHERE name = 'Reservation'";
$reservation_fee_result = mysqli_query($conn, $reservation_fee_query);
if ($reservation_fee_result) {
    $row = mysqli_fetch_assoc($reservation_fee_result);
    $reservation_fee = $row['reservation_fee'];
}

// Handle quantity update (AJAX)
if (isset($_POST['update_quantity'])) {
    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);

    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $item_id) {
                $item['quantity'] = $quantity;
                break;
            }
        }
    }

    // Update database cart as well
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE item_id = ?");
    $stmt->bind_param("ii", $quantity, $item_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
    exit;
}

// Handle item removal
if (isset($_POST['remove_item'])) {
    $item_id = intval($_POST['item_id']);

    $stmt = $conn->prepare("DELETE FROM cart WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();

    // Remove from session cart
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $item_id) {
            unset($_SESSION['cart'][$key]);
            break;
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['cart_error'] = "You need to log in to add items to the cart.";
    header("Location: ./../../user/views/login.php");
    exit();
}

// Check if cart is empty
$cartEmpty = empty($_SESSION['cart']);

// Initialize totalPrice
$totalPrice = 0;
foreach ($_SESSION['cart'] as $item) {
    $totalPrice += $item['price'] * $item['quantity'];
}

// Handle checkout
if (isset($_POST['checkout'])) {
    $userNote = $_POST['note'] ?? '';
    $reservationType = $_POST['reservation_type'] ?? '';
    $transactionId = strtoupper(bin2hex(random_bytes(6)));
    $reservation_date = $_POST['reservation_date'] ?? '';
    $reservation_time_id = $_POST['reservation_time_id'] ?? 0;
    $party_size = $_POST['party_size'] ?? 1;

    // First get the actual time value from the database
    $timeQuery = $conn->prepare("SELECT time FROM res_time WHERE id = ?");
    $timeQuery->bind_param("i", $reservation_time_id);
    $timeQuery->execute();
    $timeResult = $timeQuery->get_result();
    $timeRow = $timeResult->fetch_assoc();
    $reservation_time = $timeRow['time'] ?? '';
    $timeQuery->close();

    // Check for pending orders - fixed query
    $pendingStatuses = ['for confirmation', 'payment', 'booked'];
    $placeholders = implode(',', array_fill(0, count($pendingStatuses), '?'));
    $query = "SELECT COUNT(*) FROM Orders WHERE user_id = ? AND status IN ($placeholders)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    // Bind parameters correctly
    $params = array_merge([$clientId], $pendingStatuses);
    $types = str_repeat('s', count($pendingStatuses));
    $stmt->bind_param("i" . $types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_array();
    $pendingCount = $row[0];
    $stmt->close();

    if ($pendingCount > 0) {
        die(json_encode(['success' => false, 'message' => "You already have pending orders. Please wait for them to be processed."]));
    }

    // Insert into Orders table
    $reservation_fee = 50; 
    $status = "for confirmation";
  
    
    $stmt = $conn->prepare("INSERT INTO Orders (user_id, client_full_name, total_price, transaction_id, reservation_type, status, reservation_fee, reservation_time_id, reservation_time, reservation_date, party_size) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Corrected bind_param with proper types
    $bindResult = $stmt->bind_param(
        "isdsssiissi",  // i=integer, s=string, d=double
        $clientId, 
        $clientFullName, 
        $totalPrice, 
        $transactionId, 
        $reservationType, 
        $status, 
        $reservation_fee, 
        $reservation_time_id, 
        $reservation_time, 
        $reservation_date, 
        $party_size
    );

    if (!$bindResult) {
        die("Bind failed: " . $stmt->error);
    }

    $executeResult = $stmt->execute();
    if (!$executeResult) {
        die("Execute failed: " . $stmt->error);
    }

    $orderId = $stmt->insert_id;
    $stmt->close();

    // Insert order items
    foreach ($_SESSION['cart'] as $item) {
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, item_name, size, temperature, quantity, price, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "iisssids",
            $orderId,
            $item['id'],
            $item['name'],
            $item['size'],
            $item['temperature'],
            $item['quantity'],
            $item['price'],
            $item['note']
        );
        $stmt->execute();
        $stmt->close();
    }

    // Clear cart after successful order
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $stmt->close();
    unset($_SESSION['cart']);

    // Return success response
    echo json_encode([
        'success' => true,
        'redirect' => "./../../user/views/order-track.php?transaction_id=" . $transactionId
    ]);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="cart.css">
</head>
<body>
    
<div class="container-fluid con-cart p-4">
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
                        <input type="text" class="form-control quantity-input text-center" value="<?php echo $item['quantity']; ?>" data-id="<?php echo $item['id']; ?>" readonly>
                        <button class="btn btn-secondary btn-sm btn-increase" data-id="<?php echo $item['id']; ?>">+</button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Note Form Section -->
<div class="note-section mt-4">
    <label for="user-note">Notes</label>
    <textarea id="user-note" class="form-control" placeholder="Enter your note here" name="note"
              style="border-color: #B3B3B3; border-radius: 10px; height: 150px; resize: none;"></textarea>
</div>
</div>
<div class="col-lg-4">
<div class="card ">
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

        <div class="calendar2 w-100">
    <div class="ratio" style="--bs-aspect-ratio: 65%;">
        <iframe src="../inc/calendar2.php" name="reservation_date" class="border-0"></iframe>
    </div>
</div>

<div class="mb-4">
    <h4 class="mb-3 order-h4">Available Time</h4>
    <p id="date-picker" class="d-none"></p>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-3 g-2" id="Available-Time-show">
        <?php foreach ($times as $i => $time): ?>
            <div class="col">
                <button type="button" class="available-time-btn w-100 btn btn-sm" 
                    id="time-btn-<?= $i ?>" 
                    data-time-id="<?= $time['time_id'] ?>"
                    data-time-value="<?= htmlspecialchars($time['time']) ?>"
                    style="background-color: #07D090;">
                    <span class="text-truncate d-block text-light border-0"><?= htmlspecialchars($time['time']) ?></span>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
    <div id="time-error" class="text-danger mt-2 d-none">Please select a time slot</div>
</div>


                        

<p class="card-text d-flex justify-content-between align-items-center flex-wrap">
    <label for="reservation-type" class="form-label mb-2 mb-md-0">
        <strong class="fs-6 text-dark">Reservation Type:</strong>
    </label>
    <select id="reservation-type" name="reservation_type" class="form-control w-auto">
        <option class="re-op" value="Over the counter">Over the counter</option>
        <option class="re-op" value="Pickup">Pick-up</option>
    </select>
</p>

<div class="party-size d-flex align-items-center gap-2" id="party-size">
    <strong class="fs-6 text-dark">Party Size</strong>
    <div class="input-group scale-size ms-auto w-auto">
        <button class="btn btn-outline-secondary" type="button" id="button-minus">-</button>
        <input type="number" name="party_size" id="party_size" class="form-control text-center" value="1" min="1">
        <button class="btn btn-outline-secondary" type="button" id="button-plus">+</button>
    </div>
</div>
       <strong><h4 class="m-3 order-h4">Order Summary</h4></strong> 
        <div class="card-body">
  
        <p class="d-flex justify-content-between">
    <strong>Name:</strong>
    <span><?php echo $clientFullName; ?></span>
</p>

<p class="d-flex justify-content-between">
    <strong>Reservation fee:</strong>
    <span>P 50</span>
</p>
 
<div class="d-flex justify-content-between">
    <strong>Date:</strong>
    <span id="date-result" class="result-sum date-result text-danger fw-bold">Not selected</span>
</div>

<div class="d-flex justify-content-between">
    <strong>Time:</strong>
    <span class="result-sum time-result text-danger fw-bold" id="time-result">Not selected</span>
</div>

<input type="hidden" name="reservation_time_id" id="reservation_time_id">
<input type="hidden" name="reservation_date" id="reservation_date">

            <p class="card-text" id="totalAmount">
            <strong class="fs-5" style="color: #616161;">Total:</strong>
            <strong> <span class="float-end fs-5" style="color: #FF902B;">₱<?php echo number_format($totalPrice, 2); ?></span></strong> 
            </p>
            <div class="text-end">
            <button type="button" id="confirm-order" class="btn proceedBtn text-light text-center container-fluid bold-1" data-bs-toggle="modal" data-bs-target="#checkoutModal">
    Confirm Order
</button>
            </div>
        </div>
    </div>
</div>
        </div>
    <?php endif; ?>
</div>

<!-- Alert for Item Removed -->
<div id="alert" class="alert alert-danger alert-dismissible fade show" role="alert" style="display: none;">
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

<!-- Modal Structure -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="checkoutModalLabel">Confirm Your Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to checkout your item?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn proceedBtn" id="checkoutBtn">Checkout</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize variables
    let selectedTimeId = '';
    let selectedDate = '';
    let selectedTime = '';
    let itemToDelete = null;

    // Handle time selection
    $('.available-time-btn').click(function() {
        if (!$(this).attr('disabled')) {
            $('.available-time-btn').removeClass('selected-time');
            $(this).addClass('selected-time');
            selectedTimeId = $(this).data('time-id');
            selectedTime = $(this).data('time-value');
            $('#time-result').text(selectedTime);
            $('#reservation_time_id').val(selectedTimeId);
            $('#time-error').hide();
        }
    });

    // Listen for date selection from iframe
    window.addEventListener('message', function(event) {
        if (event.data && event.data.selectedDate) {
            selectedDate = event.data.selectedDate;
            $('#date-result').text(selectedDate);
            $('#reservation_date').val(selectedDate);
            
            // Fetch updated times for the selected date
            $.ajax({
                url: '',
                type: 'POST',
                data: { reservation_date: selectedDate },
                success: function(response) {
                    // This would need to be handled by your server-side code
                    // to return updated time slots for the selected date
                }
            });
        }
    });

    // Handle party size buttons
    $('#button-plus').click(function() {
        let currentVal = parseInt($('#party_size').val());
        if (currentVal < 10) {
            $('#party_size').val(currentVal + 1);
        }
    });

    $('#button-minus').click(function() {
        let currentVal = parseInt($('#party_size').val());
        if (currentVal > 1) {
            $('#party_size').val(currentVal - 1);
        }
    });

    // Handle checkout button click
    $("#checkoutBtn").click(function() {
        const note = $("#user-note").val();
        const reservationType = $("#reservation-type").val();
        const partySize = $("#party_size").val();
        
        // Validate required fields
        if (!selectedDate || !selectedTimeId) {
            $('#time-error').show();
            $('#checkoutModal').modal('hide');
            return;
        }

        // Send AJAX request to the server
        $.ajax({
            url: '',
            type: 'POST',
            data: { 
                checkout: true, 
                note: note, 
                reservation_type: reservationType, 
                party_size: partySize,
                reservation_date: selectedDate, 
                reservation_time_id: selectedTimeId
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        window.location.href = result.redirect;
                    } else {
                        alert(result.message);
                        $('#checkoutModal').modal('hide');
                    }
                } catch (e) {
                    console.error("Error parsing response:", e, response);
                    alert("An error occurred. Please try again.");
                    $('#checkoutModal').modal('hide');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", status, error);
                alert("An error occurred. Please try again.");
                $('#checkoutModal').modal('hide');
            }
        });
    });

    // Quantity adjustment buttons
    $('.btn-decrease, .btn-increase').click(function() {
        let itemId = $(this).data('id');
        let $quantityInput = $(`.quantity-input[data-id="${itemId}"]`);
        let currentQuantity = parseInt($quantityInput.val());

        // Adjust quantity
        if ($(this).hasClass('btn-increase')) {
            currentQuantity += 1;
        } else if ($(this).hasClass('btn-decrease')) {
            currentQuantity -= 1;
        }

        // Don't allow quantity to go below 0
        if (currentQuantity < 0) {
            currentQuantity = 0;
        }

        // Update input field
        $quantityInput.val(currentQuantity);

        // AJAX update for new quantity
        $.ajax({
            url: '',
            type: 'POST',
            data: { 
                update_quantity: true, 
                item_id: itemId, 
                quantity: currentQuantity 
            },
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    if (currentQuantity === 0) {
                        // Show delete confirmation modal
                        itemToDelete = itemId;
                        $('#deleteModal').modal('show');
                    } else {
                        // Reload to update total price
                        location.reload();
                    }
                } else {
                    alert('Failed to update quantity. Please try again.');
                }
            }
        });
    });

    // Handle delete button click
    $('#deleteBtn').click(function() {
        if (!itemToDelete) return;
        
        $.ajax({
            url: '',
            type: 'POST',
            data: { remove_item: true, item_id: itemToDelete },
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    $(`.cart-item[data-id="${itemToDelete}"]`).remove();
                    $('#alert').show().delay(3000).fadeOut();
                    $('#deleteModal').modal('hide');
                    location.reload();
                }
            }
        });
    });
    function fetchReservationStatus(date) {
    if (!date) return;
    
    fetch(`../user/res.php?date=${date}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            const buttons = document.querySelectorAll('.available-time-btn');
            
            if (data.status_reservations && data.status_reservations.length === buttons.length) {
                data.status_reservations.forEach((res_status, index) => {
                    let color = '#07D090'; // Available
                    let isDisabled = false;
                    
                    if (res_status.client_id == <?php echo json_encode($user_id); ?>) {
                        color = '#9647FF'; // Your reservation
                        isDisabled = true;
                    } else if (res_status.status === 'booked') {  // Changed from res_status.res_status to res_status.status
                        color = '#E60000'; // Booked
                        isDisabled = true;
                    }
                    
                    buttons[index].style.backgroundColor = color;
                    buttons[index].disabled = isDisabled;
                    
                    // Update the data attributes if needed
                    buttons[index].dataset.timeId = res_status.time_id;
                    buttons[index].dataset.timeValue = res_status.time;
                });
            }
        })
        .catch(error => {
            console.error('Error fetching reservation status:', error);
        });
}

// Make sure to call this when date changes
document.getElementById('date-picker').addEventListener('change', function() {
    fetchReservationStatus(this.value);
});

// // Call it initially if you have a default date
// fetchReservationStatus(document.getElementById('date-picker').value);


});
</script>

</body>
</html>