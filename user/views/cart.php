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
$clientId = $_SESSION['user_id'] ?? 0;

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
    $timeQuery = $conn->query("SELECT id, time FROM res_time ORDER BY time");
    while ($row = $timeQuery->fetch_assoc()) {
        $times[] = [
            'id' => $row['id'],
            'time' => $row['time'],
            'status' => 'available',
            'color' => '#07D090'
        ];
    }
    
    if ($date) {
        foreach ($times as &$time) {
            $checkQuery = $conn->prepare("
                SELECT r.id, r.res_status, r.client_id, rt.time
                FROM reservation r
                JOIN res_time rt ON r.reservation_time_id = rt.id
                WHERE r.reservation_date = ? 
                AND r.reservation_time_id = ?
                AND r.res_status IN ('for confirmation', 'payment', 'paid', 'booked')
            ");
            $checkQuery->bind_param("si", $date, $time['id']);
            $checkQuery->execute();
            $result = $checkQuery->get_result();
            
            if ($result->num_rows > 0) {
                $res_status = $result->fetch_assoc();
                if ($res_status['client_id'] == $user_id) {
                    $time['status'] = 'your_reservation';
                    $time['color'] = '#9647FF';
                } else {
                    $time['status'] = 'booked';
                    $time['color'] = '#E60000';
                }
            }
        }
    }
    
    return $times;
}

$selectedDate = $_POST['reservation_date'] ?? '';
$times = getAvailableTimes($conn, $clientId, $selectedDate ? date('Y-m-d', strtotime($selectedDate)) : null);

// Fetch user's verification status from database
$userId = $_SESSION['user_id'];
$sql = "SELECT verified FROM client WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($verified);
$stmt->fetch();
$stmt->close();

// Check if user is verified (verified = 1)
if ($verified != 1) {
    header("Location: login.php");
    exit();
}

// Set default value
$reservation_fee = 50;

// Try to get from database
try {
    $stmt = $conn->prepare("SELECT reservation_fee FROM Orders ORDER BY order_id DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $reservation_fee = $row['reservation_fee'];
    }
} catch (Exception $e) {
    // Use default value if query fails
    error_log("Failed to get reservation fee: " . $e->getMessage());
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

// Calculate subtotal (items only)
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Calculate total price (subtotal + reservation fee)
$totalPrice = $subtotal + $reservation_fee;

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $userNote = $_POST['note'] ?? '';
    $reservationType = $_POST['reservation_type'] ?? '';
    $transactionId = strtoupper(bin2hex(random_bytes(6)));
    $reservation_date = $_POST['reservation_date'] ?? '';
    $reservation_id = $_POST['reservation_id'] ?? 0;
    $party_size = $_POST['party_size'] ?? 1;

    // Validate required fields
    if (empty($reservation_date)) {
        echo json_encode(['success' => false, 'message' => "Please select a reservation date"]);
        exit;
    }
    
    if (empty($reservation_id)) {
        echo json_encode(['success' => false, 'message' => "Please select a reservation time"]);
        exit;
    }

    // Get reservation time
    $reservation_time = '';
    $timeQuery = $conn->prepare("SELECT time FROM res_time WHERE id = ?");
    if ($timeQuery) {
        $timeQuery->bind_param("i", $reservation_id);
        $timeQuery->execute();
        $timeResult = $timeQuery->get_result();
        $timeRow = $timeResult->fetch_assoc();
        $reservation_time = $timeRow['time'] ?? '';
        $timeQuery->close();
    }

    // Check for pending orders
    $pendingStatuses = ['for confirmation', 'payment', 'booked'];
    $placeholders = implode(',', array_fill(0, count($pendingStatuses), '?'));
    $query = "SELECT COUNT(*) FROM Orders WHERE user_id = ? AND status IN ($placeholders)";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $params = array_merge([$clientId], $pendingStatuses);
        $types = str_repeat('s', count($pendingStatuses));
        $stmt->bind_param("i" . $types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_array();
        $pendingCount = $row[0];
        $stmt->close();

        if ($pendingCount > 0) {
            echo json_encode(['success' => false, 'message' => "You already have pending orders. Please wait for them to be processed."]);
            exit;
        }
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // First, check and update inventory quantities from menu1
        foreach ($_SESSION['cart'] as $item) {
            // Check current quantity and status from menu1
            $checkStmt = $conn->prepare("SELECT quantity, status FROM menu1 WHERE id = ? FOR UPDATE");
            if (!$checkStmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $checkStmt->bind_param("i", $item['id']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $itemRow = $checkResult->fetch_assoc();
            $checkStmt->close();
            
            if (!$itemRow) {
                throw new Exception("Item not found in menu: " . $item['name']);
            }
            
            if ($itemRow['quantity'] < $item['quantity']) {
                throw new Exception("Not enough stock for item: " . $item['name']);
            }
            
            // Calculate new quantity
            $newQuantity = $itemRow['quantity'] - $item['quantity'];
            $newStatus = ($newQuantity <= 0) ? 'unavailable' : $itemRow['status'];
            
            // Update quantity and status in menu1
            $updateStmt = $conn->prepare("UPDATE menu1 SET quantity = ?, status = ? WHERE id = ?");
            if (!$updateStmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $updateStmt->bind_param("isi", $newQuantity, $newStatus, $item['id']);
            if (!$updateStmt->execute()) {
                throw new Exception("Update failed: " . $updateStmt->error);
            }
            $updateStmt->close();
        }

        // Insert into Orders table
        $status = "for confirmation";
        $stmt = $conn->prepare("INSERT INTO Orders (user_id, client_full_name, total_price, transaction_id, reservation_type, status, reservation_fee, reservation_id, reservation_time, reservation_date, party_size) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $bindResult = $stmt->bind_param(
            "isdsssiissi",
            $clientId, 
            $clientFullName, 
            $totalPrice, 
            $transactionId, 
            $reservationType, 
            $status, 
            $reservation_fee, 
            $reservation_id, 
            $reservation_time, 
            $reservation_date, 
            $party_size
        );

        if (!$bindResult) {
            throw new Exception("Bind failed: " . $stmt->error);
        }

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $orderId = $stmt->insert_id;
        $stmt->close();

        // Insert order items
        foreach ($_SESSION['cart'] as $item) {
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, item_name, size, temperature, quantity, price, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
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
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        }

        // Clear cart after successful order
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $clientId);
            $stmt->execute();
            $stmt->close();
        }
        unset($_SESSION['cart']);

        // Commit transaction
        $conn->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'redirect' => "./../../user/views/order-track.php?transaction_id=" . $transactionId
        ]);
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error during checkout: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => "Error processing order: " . $e->getMessage()
        ]);
        exit;
    }
}

// Function to handle order cancellation and return quantities
function cancelOrderAndReturnQuantities($orderId, $conn) {
    // Start transaction
    $conn->begin_transaction();

    try {
        // Get all items from the order
        $stmt = $conn->prepare("SELECT item_id, quantity FROM order_items WHERE order_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $orderItems = [];
        while ($row = $result->fetch_assoc()) {
            $orderItems[] = $row;
        }
        $stmt->close();

        // Return quantities to menu1 inventory
        foreach ($orderItems as $item) {
            // Get current quantity and status
            $checkStmt = $conn->prepare("SELECT quantity, status FROM menu1 WHERE id = ? FOR UPDATE");
            if (!$checkStmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $checkStmt->bind_param("i", $item['item_id']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $currentRow = $checkResult->fetch_assoc();
            $checkStmt->close();
            
            if (!$currentRow) {
                throw new Exception("Item not found in menu: ID " . $item['item_id']);
            }
            
            $newQuantity = $currentRow['quantity'] + $item['quantity'];
            $newStatus = ($newQuantity > 0 && $currentRow['status'] == 'unavailable') ? 'available' : $currentRow['status'];
            
            // Update quantity and status
            $updateStmt = $conn->prepare("UPDATE menu1 SET quantity = ?, status = ? WHERE id = ?");
            if (!$updateStmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $updateStmt->bind_param("isi", $newQuantity, $newStatus, $item['item_id']);
            if (!$updateStmt->execute()) {
                throw new Exception("Update failed: " . $updateStmt->error);
            }
            $updateStmt->close();
        }

        // Update order status to canceled
        $updateOrderStmt = $conn->prepare("UPDATE Orders SET status = 'canceled' WHERE id = ?");
        if (!$updateOrderStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $updateOrderStmt->bind_param("i", $orderId);
        if (!$updateOrderStmt->execute()) {
            throw new Exception("Update failed: " . $updateOrderStmt->error);
        }
        $updateOrderStmt->close();

        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Error canceling order: " . $e->getMessage());
        return false;
    }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="cart.css">

    <style>
        .pending-slot {
            background-color: #E60000 !important;
            color: white !important;
        }
        .your-reservation {
            background-color: #9647FF !important;
            color: white !important;
        }
        .available-slot {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .booked-slot {
            cursor: not-allowed;
            opacity: 0.7;
        }
        .available-time-btn[disabled] {
            pointer-events: none;
        }
        .time-slot-btn {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        .time-slot-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .time-slot-btn:disabled {
            cursor: not-allowed;
            opacity: 0.8;
        }
        .time-slot-btn.selected {
            border: 2px solid #000 !important;
        }
    </style>
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
            <div class="table-responsive">
  <table class="table cart-table table-striped">
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
            <div class="input-group quantity-buttons flex-nowrap">
                <button class="btn btn-secondary btn-sm btn-decrease border-0" style="background-color: #FF902B;" data-id="<?php echo $item['id']; ?>">-</button>
                <input type="text" class="form-control quantity-input text-center" value="<?php echo $item['quantity']; ?>" data-id="<?php echo $item['id']; ?>" readonly>
                <button class="btn btn-secondary btn-sm btn-increase border-0" style="background-color: #FF902B;" data-id="<?php echo $item['id']; ?>">+</button>
            </div>
        </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>


                <!-- Note Form Section -->
                <div class="note-section mt-4">
                    <label for="user-note">Notes</label>
                    <textarea id="user-note" class="form-control" placeholder="Enter your note here" name="note"
                            style="border-color: #B3B3B3; border-radius: 10px; height: 150px; resize: none;"></textarea>
                </div>
            </div>
            
            <div class="col-lg-4 mt-4">
                <div class="card">
                    <strong><h4 class="m-3 order-h4">Date Reservation</h4></strong> 
                    <div class="card-body">
                        <div class="card-body">
                            <div class="row">
                                
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

                        <div class="mb-4 mt-3">
        <h4 class="mb-3 order-h4">Available Time</h4>
        <p id="date-picker" class="d-none"></p>
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-3 g-2" id="time-slots-container">
            <?php foreach ($times as $time): ?>
                <div class="col">
                    <button type="button" 
                            class="time-slot-btn w-100 btn btn-sm <?= $time['status'] !== 'available' ? 'disabled' : '' ?>"
                            data-time-id="<?= $time['id'] ?>"
                            data-status="<?= $time['status'] ?>"
                            style="background-color: <?= $time['color'] ?>; color: white;"
                            <?= $time['status'] !== 'available' ? 'disabled' : '' ?>>
                        <span class="text-truncate d-block"><?= $time['time'] ?></span>
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
                                <button class="btn  text-light border-0" style="background-color: #FF902B;" type="button" id="button-minus">-</button>
                                <input type="number" name="party_size" id="party_size" class="form-control text-center" value="1" min="1">
                                <button class="btn text-light border-0" style="background-color: #FF902B;" type="button" id="button-plus">+</button>
                            </div>
                        </div>
                        
                        <strong><h4 class="m-3 order-h4">Order Summary</h4></strong> 
                        <div class="card-body">
                            <p class="d-flex justify-content-between">
                                <strong>Name:</strong>
                                <span><?php echo $clientFullName; ?></span>
                            </p>

                            <p class="d-flex justify-content-between">
                                <strong>Subtotal:</strong>
                                <span>₱<?php echo number_format($subtotal, 2); ?></span>
                            </p>

                            <p class="d-flex justify-content-between">
                                <strong>Reservation fee:</strong>
                                <span>₱<?php echo number_format($reservation_fee, 2); ?></span>
                            </p>
                            
                            <div class="d-flex justify-content-between">
                                <strong>Date:</strong>
                                <span id="date-result" class="result-sum date-result text-danger fw-bold">Not selected</span>
                            </div>

                            <div class="d-flex justify-content-between">
                                <strong>Time:</strong>
                                <span class="result-sum time-result text-danger fw-bold" id="time-result">Not selected</span>
                            </div>

                            <input type="hidden" name="reservation_id" id="reservation_id">
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
                <p><strong>Total Amount: ₱<?php echo number_format($totalPrice, 2); ?></strong></p>
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
            $('#reservation_id').val(selectedTimeId);
            $('#time-error').hide();
        }
    });

    // Updated time selection handling to match Reservation.php
    $(document).on('click', '.time-slot-btn:not(:disabled)', function() {
        // Remove selection from all buttons
        $('.time-slot-btn').removeClass('selected');
        
        // Add selection to clicked button
        $(this).addClass('selected');
        
        // Update form fields
        const timeText = $(this).find('span').text();
        const timeId = $(this).data('time-id');
        
        $('#time-result').text(timeText);
        $('#reservation_id').val(timeId);
        $('#time-error').addClass('d-none');
    });

    // Listen for date selection from iframe
    window.addEventListener('message', function(event) {
        if (event.data && event.data.selectedDate) {
            selectedDate = event.data.selectedDate;
            $('#date-result').text(selectedDate);
            $('#reservation_date').val(selectedDate);
            
            // Load time slots for selected date via AJAX
            $.ajax({
                url: 'time_picker.php',
                type: 'GET',
                data: { date: selectedDate },
                dataType: 'json',
                success: function(times) {
                    const container = $('#time-slots-container');
                    container.empty();
                    
                    if (times.length === 0) {
                        container.html('<div class="col-12 text-center py-3">No available time slots for this date</div>');
                        return;
                    }
                    
                    times.forEach(time => {
                        const isDisabled = time.status !== 'available';
                        const button = $(`
                            <div class="col">
                                <button type="button" 
                                        class="time-slot-btn w-100 btn btn-sm ${isDisabled ? 'disabled' : ''}"
                                        data-time-id="${time.id}"
                                        data-status="${time.status}"
                                        style="background-color: ${time.color}; color: white;">
                                    <span class="text-truncate d-block">${time.time}</span>
                                </button>
                            </div>
                        `);
                        
                        if (!isDisabled) {
                            button.find('button').on('click', function() {
                                $('.time-slot-btn').removeClass('selected');
                                $(this).addClass('selected');
                                $('#time-result').text(time.time);
                                $('#reservation_id').val(time.id);
                                $('#time-error').addClass('d-none');
                            });
                        }
                        
                        container.append(button);
                    });
                },
                error: function() {
                    $('#time-slots-container').html('<div class="col-12 text-center py-3 text-danger">Error loading time slots</div>');
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
        const reservationDate = $("#reservation_date").val();
        const reservationId = $("#reservation_id").val();
        
        // Validate required fields
        if (!reservationDate) {
            alert('Please select a reservation date');
            $('#checkoutModal').modal('hide');
            return;
        }
        
        if (!reservationId) {
            $('#time-error').removeClass('d-none');
            $('#checkoutModal').modal('hide');
            return;
        }

        // Show loading state
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

        // Send AJAX request to the server
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: { 
                checkout: true, 
                note: note, 
                reservation_type: reservationType, 
                party_size: partySize,
                reservation_date: reservationDate, 
                reservation_id: reservationId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect;
                } else {
                    alert(response.message);
                    $('#checkoutModal').modal('hide');
                    $("#checkoutBtn").prop('disabled', false).text('Checkout');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", status, error);
                alert("An error occurred. Please try again.");
                $('#checkoutModal').modal('hide');
                $("#checkoutBtn").prop('disabled', false).text('Checkout');
            }
        });
    });

    // Quantity adjustment buttons - using event delegation
    $(document).on('click', '.btn-decrease, .btn-increase', function() {
        let itemId = $(this).data('id');
        let $quantityInput = $(`.quantity-input[data-id="${itemId}"]`);
        let currentQuantity = parseInt($quantityInput.val());

        // Adjust quantity
        if ($(this).hasClass('btn-increase')) {
            currentQuantity += 1;
        } else if ($(this).hasClass('btn-decrease')) {
            currentQuantity -= 1;
            if (currentQuantity < 0) currentQuantity = 0;
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
            dataType: 'json', // Expect JSON response
            success: function(result) {
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
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", status, error);
                alert('Error updating quantity. Please try again.');
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
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $(`.cart-item[data-id="${itemToDelete}"]`).remove();
                    $('#alert').show().delay(3000).fadeOut();
                    $('#deleteModal').modal('hide');
                    location.reload();
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", status, error);
                alert('Error removing item. Please try again.');
            }
        });
    });

    // Enhanced fetchReservationStatus function using orders table
    function fetchReservationStatus(date) {
        if (!date) return;
        
        fetch(`../user/res1.php?date=${date}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                const buttons = document.querySelectorAll('.available-time-btn');
                
                if (data.status_reservations && data.status_reservations.length === buttons.length) {
                    data.status_reservations.forEach((res_status, index) => {
                        // Set button appearance based on status
                        buttons[index].style.backgroundColor = res_status.color;
                        buttons[index].dataset.status = res_status.status;
                        buttons[index].dataset.timeId = res_status.time_id;
                        buttons[index].dataset.timeValue = res_status.time;
                        
                        // Only enable if status is available (green)
                        buttons[index].disabled = res_status.color !== '#07D090';
                        
                        // Add visual feedback classes
                        buttons[index].classList.toggle('available-slot', res_status.color === '#07D090');
                        buttons[index].classList.toggle('booked-slot', res_status.color !== '#07D090');
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
});
</script>

</body>
</html>