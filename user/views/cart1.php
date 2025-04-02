<?php
session_start();
include './../../connection/connection.php';    

// Fetch cart from database if session expired
if (!isset($_SESSION['cart']) && isset($_SESSION['user_id'])) {
    $clientId = $_SESSION['user_id'];
    $query = "SELECT item_id, item_name, size, temperature, quantity, price, note FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
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
$clientId = $_SESSION['user_id'] ?? null;

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

// Fetch available times with status
function getAvailableTimes($conn, $user_id, $date = null) {
    $times = [];
    $timeQuery = $conn->query("SELECT id AS time_id, time FROM res_time ORDER BY time");
    while ($row = $timeQuery->fetch_assoc()) {
        $times[] = $row;
    }
    
    if ($date) {
        foreach ($times as &$time) {
            $checkQuery = $conn->prepare("
                SELECT status, client_id 
                FROM reservation 
                WHERE reservation_date = ? 
                AND reservation_time_id = ?
            ");
            $checkQuery->bind_param("si", $date, $time['time_id']);
            $checkQuery->execute();
            $result = $checkQuery->get_result();
            
            if ($result->num_rows > 0) {
                $status = $result->fetch_assoc();
                if ($status['client_id'] == $user_id) {
                    $time['status'] = 'your_reservation';
                    $time['disabled'] = true;
                } elseif ($status['status'] == 'booked') {
                    $time['status'] = 'booked';
                    $time['disabled'] = true;
                }
            } else {
                $time['status'] = 'available';
                $time['disabled'] = false;
            }
        }
    }
    
    return $times;
}

$selectedDate = $_POST['reservation_date'] ?? '';
$times = getAvailableTimes($conn, $clientId, $selectedDate);
stmt->bind_result($pendingOrderCount);
    $stmt->fetch();
    $stmt->close();

    if ($pendingOrderCount > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending order.']);
        exit;
    }

    // Get the selected time
    $timeQuery = $conn->prepare("SELECT time FROM res_time WHERE id = ?");
    $timeQuery->bind_param("i", $reservation_time_id);
    $timeQuery->execute();
    $timeResult = $timeQuery->get_result();
    $timeData = $timeResult->fetch_assoc();
    $reservation_time = $timeData['time'];

// Handle checkout
if (isset($_POST['checkout'])) {
    $userNote = $_POST['note'] ?? '';
    $reservationType = $_POST['reservation_type'] ?? '';
    $transactionId = strtoupper(bin2hex(random_bytes(6)));
    $reservation_date = $_POST['reservation_date'] ?? '';
    $reservation_time_id = $_POST['reservation_time_id'] ?? '';
    $party_size = $_POST['party_size'] ?? 1;

    // Check for pending orders
    $pendingStatuses = ['for confirmation', 'payment', 'booked'];
    $query = "SELECT COUNT(*) FROM Orders WHERE user_id = ? AND status IN (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $clientId, ...$pendingStatuses);
    $stmt->execute();
    $
    // Insert into Orders table
    $reservation_fee = 50; 
    $status = "for confirmation";
    $stmt = $conn->prepare("INSERT INTO Orders (user_id, client_full_name, total_price, transaction_id, reservation_type, status, reservation_fee, reservation_time_id, reservation_time, reservation_date, party_size) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("issdssiissi", 
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

    $stmt->execute();
    $orderId = $stmt->insert_id;
    $stmt->close();

    // Insert into Order_Items table
    foreach ($_SESSION['cart'] as $item) {
        $stmt_items = $conn->prepare("INSERT INTO Order_Items (order_id, item_id, item_name, size, temperature, quantity, note, price) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt_items->bind_param(
            "iisssisd",  
            $orderId,
            $item['id'],
            $item['name'],
            $item['size'],
            $item['temperature'],
            $item['quantity'],
            $userNote,
            $item['price']
        );
        
        $stmt_items->execute();
        $stmt_items->close();
    }

    // Clear cart
    unset($_SESSION['cart']);
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $stmt->close();

    if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo json_encode(['success' => true, 'redirect' => "order-track.php?transaction_id=" . $transactionId]);
    } else {
        header("Location: order-track.php?transaction_id=" . $transactionId);
    }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="cart.css">
    <style>
        .available-time-btn:disabled {
            cursor: not-allowed;
            opacity: 0.7;
        }
        .selected-time {
            border: 2px solid #000 !important;
        }
        #time-error {
            color: red;
            margin-top: 5px;
            display: none;
        }
    </style>
</head>
<body>
    
<div class="container-fluid con-cart p-4">
    <div class="back-to-order mb-5">
        <a href="order-now.php" class="text-decoration-none text-dark"><i class="fa-solid fa-arrow-left"></i>  <i class="fa-solid fa-mug-saucer"></i></a>
    </div>

    <h3>Your <span class="order-underline">Order</span></h3>

    <?php if (empty($_SESSION['cart'])): ?>
        <p>Your cart is empty. <a href="order-now.php">Start shopping</a></p>
    <?php else: ?>
        <div class="row">
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
                            <td><?php echo $item['size'] ?? ''; ?></td>
                            <td><?php echo $item['temperature'] ?? ''; ?></td>
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

                <div class="note-section mt-4">
                    <label for="user-note">Notes</label>
                    <textarea id="user-note" class="form-control" placeholder="Enter your note here" name="note"
                              style="border-color: #B3B3B3; border-radius: 10px; height: 150px; resize: none;"></textarea>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <strong><h4 class="m-3 order-h4">Seat Reservation</h4></strong> 
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="p-1 text-white text-center" style="background-color: #17A1FA; border-radius: 5px;">No Slots</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="p-1 text-white text-center" style="background-color: #07D090; border-radius: 5px;">Available</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="p-1 text-white text-center" style="background-color: #E60000; border-radius: 5px;">Fully Booked</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="p-1 text-white text-center" style="background-color: #9647FF; border-radius: 5px;">Your Reservation</div>
                            </div>
                        </div>
                    </div>

                    <div class="calendar2 w-100">
                        <div class="ratio" style="--bs-aspect-ratio: 65%;">
                            <iframe src="../inc/calendar2.php" name="reservation_date" class="border-0"></iframe>
                        </div>
                    </div>

                    <div class="Available-Time">
                        <h4 class="cart-right-header1">Available Time</h4>
                        <p id="date-picker" class="form-control" style="display: none;"></p>
                        <div class="Available-Time-show" id="Available-Time-show">
                            <?php foreach ($times as $i => $time): ?>
                                <button type="button" class="available-time-btn" id="time-btn-<?= $i ?>" 
                                       data-time-id="<?= $time['time_id'] ?>"
                                       data-time-value="<?= htmlspecialchars($time['time']) ?>"
                                       style="background-color: <?= 
                                           ($time['status'] ?? '') == 'your_reservation' ? '#9647FF' : 
                                           (($time['status'] ?? '') == 'booked' ? '#E60000' : '#07D090') ?>;"
                                       <?= ($time['disabled'] ?? false) ? 'disabled' : '' ?>>
                                    <?= htmlspecialchars($time['time']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <div id="time-error">Please select a time slot</div>
                    </div>

                    <p class="card-text d-flex justify-content-between align-items-center flex-wrap">
                        <label for="reservation-type" class="form-label mb-2 mb-md-0">
                            <strong class="fs-6 text-dark">Reservation Type:</strong>
                        </label>
                        <select id="reservation-type" class="form-control w-auto">
                            <option class="re-op" value="Over the counter">Over the counter</option>
                            <option class="re-op" value="Pickup">Pick-up</option>
                        </select>
                    </p>

                    <div class="party-size d-flex align-items-center gap-2" id="party-size">
                        <strong class="fs-6 text-dark">Party Size</strong>
                        <div class="input-group scale-size ms-auto w-auto">
                            <button class="btn btn-outline-secondary" type="button" id="button-minus">-</button>
                            <input type="number" name="party_size" id="party_size" class="form-control text-center" value="1">
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
                            <span id="date-result" class="result-sum date-result">Not selected</span>
                            <input type="hidden" name="reservation_date" id="reservation_date_input">
                        </div>
                        <div class="d-flex justify-content-between">
                            <strong>Time:</strong>
                            <span class="result-sum time-result" id="time-result">Not selected</span>
                            <input type="hidden" name="reservation_time" id="reservation_time_input">
                        </div>
                        <p class="card-text" id="totalAmount">
                            <strong class="fs-5" style="color: #616161;">Total:</strong>
                            <strong><span class="float-end fs-5" style="color: #FF902B;">₱<?php echo number_format(array_reduce($_SESSION['cart'], function($carry, $item) { return $carry + ($item['price'] * $item['quantity']); }, 0), 2); ?></span></strong> 
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

<!-- Modals -->
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
    // Handle time slot selection
    $('.available-time-btn').click(function() {
        if (!$(this).is(':disabled')) {
            $('.available-time-btn').removeClass('selected-time');
            $(this).addClass('selected-time');
            
            const timeId = $(this).data('time-id');
            const timeValue = $(this).data('time-value');
            
            $('#time-result').text(timeValue);
            $('#reservation_time_input').val(timeId);
            $('#time-error').hide();
        }
    });

    // Handle calendar date selection
    window.addEventListener('message', function(event) {
        if (event.data && event.data.selectedDate) {
            const selectedDate = event.data.selectedDate;
            $('#reservation_date_input').val(selectedDate);
            $('#date-result').text(selectedDate);
            
            // Fetch updated time slots for the selected date
            $.get(`../user/res.php?date=${selectedDate}&user_id=<?php echo $clientId; ?>`, function(data) {
                data.status_reservations.forEach((status, index) => {
                    const btn = $(`#time-btn-${index}`);
                    let color = '#07D090';
                    let disabled = false;
                    
                    if (status.client_id == <?php echo $clientId; ?>) {
                        color = '#9647FF';
                        disabled = true;
                    } else if (status.status === 'booked') {
                        color = '#E60000';
                        disabled = true;
                    }
                    
                    btn.css('background-color', color);
                    btn.prop('disabled', disabled);
                });
            }, 'json');
        }
    });

    // Handle checkout
    $('#checkoutBtn').click(function() {
        const note = $('#user-note').val();
        const reservationType = $('#reservation-type').val();
        const partySize = $('#party_size').val();
        const selectedDate = $('#reservation_date_input').val();
        const selectedTimeId = $('#reservation_time_input').val();

        if (!selectedTimeId) {
            $('#time-error').show();
            return;
        }

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
                const result = JSON.parse(response);
                if (result.success) {
                    window.location.href = result.redirect;
                } else {
                    alert(result.message);
                }
            }
        });
    });

    // Quantity controls
    $('.btn-decrease, .btn-increase').click(function() {
        const itemId = $(this).data('id');
        const $input = $(`.quantity-input[data-id="${itemId}"]`);
        let qty = parseInt($input.val());
        
        if ($(this).hasClass('btn-increase')) {
            qty++;
        } else if (qty > 1) {
            qty--;
        }
        
        $input.val(qty);
        
        $.post("cart.php", { 
            update_quantity: true, 
            item_id: itemId, 
            quantity: qty 
        }, function(response) {
            if (!response.success) {
                alert('Failed to update quantity');
            }
        }, 'json');
    });

    // Party size controls
    $('#button-plus').click(function() {
        const $input = $('#party_size');
        let val = parseInt($input.val());
        if (val < 20) $input.val(val + 1);
    });
    
    $('#button-minus').click(function() {
        const $input = $('#party_size');
        let val = parseInt($input.val());
        if (val > 1) $input.val(val - 1);
    });
});
</script>
</body>
</html>