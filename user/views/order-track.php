<?php
include './../../connection/connection.php';
include './../inc/topNav.php';
require './../../vendor/autoload.php'; // Ensure you have the QR library

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Label\Label;

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

// If no order or order is cancelled, display a message and exit
if (!$order || $order['status'] === 'cancelled') {
    echo "<div class='container text-center mt-5'>
            <h4>No Order Tracking History</h4>
            <p>We're sorry, but no current orders are available. 😔</p>
            <p>If you have any questions or believe this was a mistake, please contact us for assistance.</p>
          </div>";
    exit();
}

// Handle timeout reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['timeout_order'], $_POST['order_id'])) {
    $orderId = $_POST['order_id'];

    // Update the order status to 'cancelled'
    $timeoutQuery = "UPDATE Orders SET status = 'cancelled' WHERE order_id = ?";
    $stmt = $conn->prepare($timeoutQuery);

    if (!$stmt) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'SQL Error: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Order cancelled due to timeout']);
    exit();
}

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
    
    // Ensure the changes are reflected immediately
    $stmt->close();

    // Fetch the updated order status
    $checkQuery = "SELECT status FROM Orders WHERE order_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $updatedOrder = $result->fetch_assoc();
    $stmt->close();

    // Refresh order status in the session or variable
    $order['status'] = $updatedOrder['status'];
}

// Recheck the status to prevent requiring multiple cancels
if ($order['status'] === 'cancelled') {
    echo "<div class='container text-center mt-5'>
            <h4>No Order Tracking History</h4>
            <p>We're sorry, but no current orders are available. 😔</p>
            <p>If you have any questions or believe this was a mistake, please contact us for assistance.</p>
          </div>";
    exit();
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

                // Update the order status to "paid"
                $updateStatusQuery = "UPDATE Orders SET status = 'PAID' WHERE order_id = ?";
                $stmt = $conn->prepare($updateStatusQuery);

                if (!$stmt) {
                    die("SQL Error: " . $conn->error);
                }

                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $stmt->close();

                echo "<div class='alert alert-success'>Receipt uploaded successfully and order status updated to paid!</div>";
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

// QR Code Generation (UPDATED CODE)
if ($order && $order['status'] === 'booked') {
    $qrData = "Order ID: {$order['order_id']}\nName: {$order['client_full_name']}\nTotal: ₱" . number_format($order['total_price'], 2);

    $qrCode = Builder::create()
        ->writer(new PngWriter())
        ->data($qrData)
        ->encoding(new Encoding('UTF-8'))
        ->size(200)
        ->margin(10)
        ->build();

    $qrFile = './../../uploads/qrcodes/order_' . $order['order_id'] . '.png';
    file_put_contents($qrFile, $qrCode->getString());
}

// Fetch order items with the correct item_id from menu1 and only unrated items
$query = "SELECT oi.*, m.id AS menu_item_id 
          FROM order_items oi 
          JOIN menu1 m ON oi.item_id = m.id 
          WHERE oi.order_id = ? AND oi.is_rated = 0";

$stmt = $conn->prepare($query);

$stmt->bind_param("i", $order['order_id']);
$stmt->execute();
$result = $stmt->get_result();

$orderItems = [];
while ($row = $result->fetch_assoc()) {
    $orderItems[] = $row;
}
$stmt->close();

// Mark notification as read if notification_id is provided
if (isset($_GET['notification_id'])) {
    $notificationId = $_GET['notification_id'];
    $updateQuery = "UPDATE notifications SET is_read = 1 WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('i', $notificationId);
    $stmt->execute();
    $stmt->close();
}

// Handle Rating Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'], $_POST['rating'])) {
    $itemId = (int)$_POST['item_id'];
    $rating = (int)$_POST['rating'];

    // Validate item ID
    $checkItemQuery = "SELECT id FROM menu1 WHERE id = ?";
    $stmt = $conn->prepare($checkItemQuery);
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Update rating in menu1 table and mark as rated in order_items table
        $updateQuery = "UPDATE menu1 SET rating = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ii", $rating, $itemId);

        if ($stmt->execute()) {
            // Mark the item as rated in the order_items table
            $markRatedQuery = "UPDATE order_items SET is_rated = 1 WHERE item_id = ? AND order_id = ?";
            $stmt = $conn->prepare($markRatedQuery);
            $stmt->bind_param("ii", $itemId, $order['order_id']);
            $stmt->execute();

            echo "Rating updated successfully!";
        } else {
            echo "Error updating rating: " . $stmt->error;
        }
    } else {
        echo "Invalid item ID.";
    }

    $stmt->close();
    exit(); // Prevent further execution
}

// Get note (if multiple items, get the first one)
$note = $orderItems[0]['note'] ?? 'No notes available.';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .order-tracking {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 50px;
        }
        .order-tracking .step {
            position: relative;
            text-align: center;
            width: 24%;
        }
        .order-tracking .step:before {
            content: '';
            position: absolute;
            top: 14px;
            left: 50%;
            width: 100%;
            height: 5px;
            background-color: #ddd;
            transform: translateX(-50%);
        }
        .order-tracking .step.active:before {
            background-color: #12B76A;
        }
        .order-tracking .step.active .icon {
            background-color: #12B76A;
            color: white;
        }
        .order-tracking .step .icon {
            width: 40px;
            height: 40px;
            line-height: 40px;
            border-radius: 50%;
            margin: 0 auto;
            font-weight: bold;
            color: #fff;
            background-color: #ddd;
        }
        .order-tracking .step.active .title {
            color: #12B76A;
        }
        .order-tracking .step .title {
            font-weight: bold;
            font-size: 14px;
        }
        .order-tracking .step .description {
            font-size: 12px;
            color: #888;
        }

        .custom-table thead th {
            background-color: #FF902B;
            color: white;
        }

        .custom-table {
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
        }
        h5 {
            color: #FF902B;
        }

        .order-sum {
            padding: 1rem;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
        }

        .down-con {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
        }

        .right {
            height: 100%;
        }

        .upl-p {
            color: #FF902B;
            font-weight: bold;
        }

        .send {
            background: #07D090;
        }

        .total_price {
            color: #FF902B;
            font-weight: bold;
        }

        .back-btn {
            border: none;
            background-color: #07D090;
        }

        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #12B76A;
            color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .popup-content {
            text-align: center;
        }

        .thank-you-message {
            display: none;
            text-align: center;
            margin-top: 20px;
            font-size: 24px;
            color: #12B76A;
        }
        
        #payment-timer {
            font-size: 2rem;
            font-weight: bold;
            color: #dc3545;
            text-align: center;
            margin: 10px 0;
        }

        .check-circle {
            display: inline-block;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 5px solid  #ffff;
            position: relative;
            margin-bottom: 20px;
        }
        
        .check-circle i {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

    </style>
</head>
<body>
    <div class="container mt-5">
        <h3>Order Tracking</h3>

        <!-- Order Tracking Steps -->
        <div class="order-tracking">
            <div class="step <?php echo ($order['status'] === 'for confirmation' ? 'active' : ''); ?>">
                <div class="icon"></div>
                <div class="title">For Confirmation</div>
                <div class="description">We're confirming your order</div>
            </div>
            <div class="step <?php echo ($order['status'] === 'payment' ? 'active' : ''); ?>">
                <div class="icon"></div>
                <div class="title">Payment</div>
                <div class="description">Payment processing</div>
            </div>
            <div class="step <?php echo ($order['status'] === 'booked' ? 'active' : ''); ?>">
                <div class="icon"></div>
                <div class="title">Booked</div>
                <div class="description">Your order has been confirmed</div>
            </div>
            <div class="step <?php echo ($order['status'] === 'rate us' ? 'active' : ''); ?>">
                <div class="icon fs-5"></div>
                <div class="title">Rate Us</div>
                <div class="description">Please rate your experience</div>
            </div>
        </div>

        <div class="down-con container-fluid">
            <div class="left col-md-8 px-5 rounded">
                <table class="table custom-table container-fluid" id="orderTable">
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
                                        <div class="star-rating" data-item-id="<?php echo isset($item['menu_item_id']) ? htmlspecialchars($item['menu_item_id']) : '0'; ?>">
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

                <div id="thankYouMessage" class="thank-you-message">
                    Thank you for ordering!
                </div>

                <?php if ($order['status'] === 'payment' || $order['status'] === 'booked'): ?>
                    <div class="order-sum container-fluid">
                        <h5 class="bold">Order Summary</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Name:</strong></p>
                                    <p><?php echo htmlspecialchars($order['client_full_name']); ?></p>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Transaction ID:</strong></p>
                                    <p><?php echo htmlspecialchars($order['transaction_id']); ?></p>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Reservation Type:</strong></p>
                                    <p><?php echo htmlspecialchars($order['reservation_type']); ?></p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Reservation Date:</strong></p>
                                    <p><?php echo htmlspecialchars($order['reservation_date']); ?></p>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Reservation Time:</strong></p>
                                    <p><?php echo htmlspecialchars($order['reservation_time']); ?></p>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Party Size:</strong></p>
                                    <p><?php echo htmlspecialchars($order['party_size']); ?></p>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Reservation Fee:</strong></p>
                                    <p><?php echo htmlspecialchars($order['reservation_fee']); ?></p>
                                </div>
                            </div>
                        </div>

                        <p><strong>Total Price:</strong> ₱<?php echo number_format($order['total_price'], 2); ?></p>
                    </div>

                    <div class="note-section mt-4">
                        <label for="user-note" class="p-1">Notes</label>
                        <div id="user-note" class="form-control" style="border-color: #B3B3B3; border-radius: 10px; height: 150px; overflow-y: auto; padding: 10px; background-color: #f8f9fa; pointer-events: none; user-select: none;">
                            <?php
                            $hasNotes = false;
                            foreach ($orderItems as $item) {
                                if (!empty($item['note'])) {
                                    echo "<p>" . htmlspecialchars($item['note']) . "</p>";
                                    $hasNotes = true;
                                }
                            }
                            if (!$hasNotes) {
                                echo "No notes available.";
                            }
                            ?>
                        </div>
                    </div>

                <?php elseif ($order['status'] === 'PAID' ): ?>
                    <div class="order-sum container-fluid">
                        <h5 class="bold">Order Summary</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Name:</strong></p>
                                    <p><?php echo htmlspecialchars($order['client_full_name']); ?></p>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Transaction ID:</strong></p>
                                    <p><?php echo htmlspecialchars($order['transaction_id']); ?></p>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Reservation Type:</strong></p>
                                    <p><?php echo htmlspecialchars($order['reservation_type']); ?></p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Reservation Date:</strong></p>
                                    <p><?php echo htmlspecialchars($order['reservation_date']); ?></p>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Reservation Time:</strong></p>
                                    <p><?php echo htmlspecialchars($order['reservation_time']); ?></p>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Party Size:</strong></p>
                                    <p><?php echo htmlspecialchars($order['party_size']); ?></p>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Reservation Fee:</strong></p>
                                    <p><?php echo htmlspecialchars($order['reservation_fee']); ?></p>
                                </div>
                            </div>
                        </div>

                        <p><strong>Total Price:</strong> ₱<?php echo number_format($order['total_price'], 2); ?></p>
                    </div>

                    <div class="note-section mt-4">
                        <label for="user-note" class="p-1">Notes</label>
                        <div id="user-note" class="form-control" style="border-color: #B3B3B3; border-radius: 10px; height: 150px; overflow-y: auto; padding: 10px; background-color: #f8f9fa; pointer-events: none; user-select: none;">
                            <?php
                            $hasNotes = false;
                            foreach ($orderItems as $item) {
                                if (!empty($item['note'])) {
                                    echo "<p>" . htmlspecialchars($item['note']) . "</p>";
                                    $hasNotes = true;
                                }
                            }
                            if (!$hasNotes) {
                                echo "No notes available.";
                            }
                            ?>
                        </div>
                    </div>
                <?php elseif ($order['status'] === 'for confirmation'): ?>
                    <div id="user-note" class="form-control" style="border-radius: 10px; height: 150px; overflow-y: auto;">
                        <?php
                        $hasNotes = false;
                        foreach ($orderItems as $item) {
                            if (!empty($item['note'])) {
                                echo "<p>" . htmlspecialchars($item['note']) . "</p>";
                                $hasNotes = true;
                            }
                        }
                        if (!$hasNotes) {
                            echo "No notes available.";
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="right col-md-4 px-5 border border-1 rounded">
                <?php if ($order['status'] === 'payment'): ?>
                    <form method="POST" enctype="multipart/form-data" class="mt-3">
                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                        
                        <!-- Timer Section -->
                        <div class="mb-3 text-center">
                            <h5>Time Remaining to Complete Payment</h5>
                            <div id="payment-timer" class="fs-3 fw-bold text-danger">30:00</div>
                            <small class="text-muted">Please complete your payment before time runs out</small>
                        </div>

                        <div class="mb-3">
                            <label for="receipt" class="form-label upl-p">Upload Receipt</label>
                            <input type="file" class="form-control" id="receipt" name="receipt">
                        </div>

                        <div class="mb-3">
                            <label for="receipt" class="form-label upl-p">Instructions</label>
                            <ul>
                                <li>1. Please upload receipt upon confirmation</li>
                                <li>2. Upon confirmation, there will be an allotted time of 30 minutes to confirm payment.</li>
                                <li>3. Failure to do so will result in cancellation of order/reservation.</li>
                            </ul>
                        </div>
                        <button type="submit" name="upload_receipt" class="btn rounded send text-light fw-bold container-fluid">Send Receipt</button>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                            <button type="button" class="btn btn-danger rounded mt-3 container-fluid" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">Cancel Order</button>
                        </form>
                    </form>

                    <!-- Timeout Modal -->
                    <div class="modal fade" id="timeoutModal" tabindex="-1" aria-labelledby="timeoutModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-warning">
                                    <h5 class="modal-title" id="timeoutModalLabel">Payment Time Expired</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Your payment time has expired. Would you like to:</p>
                                    <ol>
                                        <li>Continue with the payment (we'll give you another 30 minutes)</li>
                                        <li>Cancel the order</li>
                                    </ol>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" id="continuePayment">Continue Payment</button>
                                    <button type="button" class="btn btn-danger" id="cancelAfterTimeout">Cancel Order</button>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($order['status'] === 'PAID'): ?>
                    <div class="text-center p-4 rounded shadow container-fluid" style="background-color: #FF902B; color: white;">
                    <div class="check-circle">
                            <i class="fas fa-check"></i>
                        </div>
                        <h4 class="mb-2"><i class="bi bi-check-circle-fill"></i> Payment Successful!</h4>
                        <p>Your payment has been received. <strong>Kahel Cafe</strong> is currently verifying your receipt. Please wait for confirmation.</p>
                    </div>

                <?php elseif ($order['status'] === 'booked'): ?>
                    
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                        <div class="text-center mt-4">
                            <h5>Scan to View Order Details</h5>
                            <img src="<?php echo $qrFile; ?>" alt="Order QR Code" class="img-fluid" />
                            <br>
                        </div>
                    </form>

                    <div class="alert alert-info mt-2">
                            <strong>Please show this QR code to the cashier.</strong>
                        </div>

                    <div class="qr container-fluid my-4 d-flex justify-content-center rounded-pill">
                    <a href="<?php echo $qrFile; ?>" download="reservation_qr_<?php echo $reservation['id']; ?>.png" class="btn p-2 w-100 rounded-pill text-light" style="background-color: #FF902B;">
                            Download QR Code
                        </a>
                    </div>
                <?php elseif ($order['status'] === 'for confirmation'): ?>
                    <div class="order-sums container-fluid">
                        <h5 class="bold py-3">Order Summary</h5>

                        <div class="d-flex justify-content-between w-100">
                            <p><strong>Name:</strong></p>
                            <p><?php echo htmlspecialchars($order['client_full_name']); ?></p>
                        </div>
                        <div class="d-flex justify-content-between w-100">
                            <p><strong>Transaction ID:</strong></p>
                            <p><?php echo htmlspecialchars($order['transaction_id']); ?></p>
                        </div>
                        <div class="d-flex justify-content-between w-100">
                            <p><strong>Reservation Type:</strong></p>
                            <p><?php echo htmlspecialchars($order['reservation_type']); ?></p>
                        </div>
                     
                            <div class="d-flex justify-content-between w-100">
                                <p><strong>Reservation Date:</strong></p>
                                <p><?php echo htmlspecialchars($order['reservation_date']); ?></p>
                            </div>
                            <div class="d-flex justify-content-between w-100">
                                <p><strong>Reservation Time:</strong></p>
                                <p><?php echo htmlspecialchars($order['reservation_time']); ?></p>
                            </div>
                            <div class="d-flex justify-content-between w-100">
                                <p><strong>Party Size:</strong></p>
                                <p><?php echo htmlspecialchars($order['party_size']); ?></p>
                            </div>
                  
                        <div class="d-flex justify-content-between w-100">
                            <p><strong>Reservation Fee:</strong></p>
                            <p><?php echo htmlspecialchars($order['reservation_fee']); ?></p>
                        </div>
                        <div class="d-flex justify-content-between w-100">
                            <p><strong>Total Price:</strong></p>
                            <p class="total_price"> ₱<?php echo number_format($order['total_price'], 2); ?></p>
                        </div>
                    </div>

                    <form method="POST" class="mt-3">
                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                        <button type="button" class="btn btn-danger container-fluid rounded-pill" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">Cancel Order</button>
                    </form>
                <?php elseif ($order['status'] === 'rate us'): ?>
                    <div class="order-sums container-fluid">
                        <h5 class="bold py-3">Order Summary</h5>

                        <div class="d-flex justify-content-between w-100">
                            <p><strong>Name:</strong></p>
                            <p><?php echo htmlspecialchars($order['client_full_name']); ?></p>
                        </div>
                        <div class="d-flex justify-content-between w-100">
                            <p><strong>Transaction ID:</strong></p>
                            <p><?php echo htmlspecialchars($order['transaction_id']); ?></p>
                        </div>
                        <div class="d-flex justify-content-between w-100">
                            <p><strong>Reservation Type:</strong></p>
                            <p><?php echo htmlspecialchars($order['reservation_type']); ?></p>
                        </div>
                    
                            <div class="d-flex justify-content-between w-100">
                                <p><strong>Reservation Date:</strong></p>
                                <p><?php echo htmlspecialchars($order['reservation_date']); ?></p>
                            </div>
                            <div class="d-flex justify-content-between w-100">
                                <p><strong>Reservation Time:</strong></p>
                                <p><?php echo htmlspecialchars($order['reservation_time']); ?></p>
                            </div>
                            <div class="d-flex justify-content-between w-100">
                                <p><strong>Party Size:</strong></p>
                                <p><?php echo htmlspecialchars($order['party_size']); ?></p>
                            </div>
                     
                        <div class="d-flex justify-content-between w-100">
                            <p><strong>Subtotal:</strong></p>
                            <p><?php echo htmlspecialchars($order['client_full_name']); ?></p>
                        </div>
                        <div class="d-flex justify-content-between w-100">
                            <p><strong>Reservation Fee:</strong></p>
                            <p><?php echo htmlspecialchars($order['client_full_name']); ?></p>
                        </div>
                        <div class="d-flex justify-content-between w-100">
                            <p><strong>Total Price:</strong></p>
                            <p class="total_price"> ₱<?php echo number_format($order['total_price'], 2); ?></p>
                        </div>
                    </div>

                    <div class="back">
                        <button class="back-btn m-3 p-2 container-fluid rounded-pill text-light" onclick="window.location.href='../../user/views/index.php'">Back to home</button>
                    </div>
                <?php endif; ?>

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
            </div>
        </div>
    </div>

    <!-- Centered Popup -->
    <div id="ratingPopup" class="popup">
        <div class="popup-content">
            <p>Thank you for your rating!</p>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            // Timer functionality for payment status
            <?php if ($order['status'] === 'payment'): ?>
                // Calculate time remaining
                const createdTime = new Date("<?php echo $order['created_at']; ?>").getTime();
                const currentTime = new Date().getTime();
                const elapsedSeconds = Math.floor((currentTime - createdTime) / 1000);
                let timeLeft = 30 * 60 - elapsedSeconds; // 30 minutes in seconds
                
                // If time is already expired, set to 0
                if (timeLeft < 0) timeLeft = 0;
                
                const timerElement = document.getElementById('payment-timer');
                const timeoutModal = new bootstrap.Modal(document.getElementById('timeoutModal'));
                
                // Function to update the timer display
                function updateTimer() {
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    
                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        timerElement.textContent = "00:00";
                        timeoutModal.show();
                        handleTimeout();
                    }
                }
                
                // Start the timer
                updateTimer();
                let timerInterval = setInterval(() => {
                    timeLeft--;
                    updateTimer();
                }, 1000);
                
                // Handle continue payment button
                $('#continuePayment').click(function() {
                    // Reset the timer for another 30 minutes
                    timeLeft = 30 * 60;
                    updateTimer();
                    timerInterval = setInterval(() => {
                        timeLeft--;
                        updateTimer();
                    }, 1000);
                    
                    // Hide the modal
                    timeoutModal.hide();
                });
                
                // Handle cancel after timeout button
                $('#cancelAfterTimeout').click(function() {
                    // Cancel the order
                    $.ajax({
                        type: 'POST',
                        url: window.location.href,
                        data: {
                            cancel_order: true,
                            order_id: <?php echo $order['order_id']; ?>
                        },
                        success: function(response) {
                            window.location.reload();
                        },
                        error: function() {
                            alert('Error cancelling order');
                        }
                    });
                });
                
                // Also update the database when timer runs out
                function handleTimeout() {
                    $.ajax({
                        type: 'POST',
                        url: window.location.href,
                        data: {
                            timeout_order: true,
                            order_id: <?php echo $order['order_id']; ?>
                        },
                        success: function(response) {
                            // Modal will handle the UI
                        },
                        error: function() {
                            console.log('Error updating order status');
                        }
                    });
                }
                
                // Check if we need to show timeout modal on page load
                <?php 
                // Calculate if the payment time has expired
                $createdTime = strtotime($order['created_at']);
                $currentTime = time();
                $elapsedTime = $currentTime - $createdTime;
                if ($elapsedTime > 30 * 60) {
                    echo 'timeoutModal.show();';
                    echo 'handleTimeout();';
                }
                ?>
            <?php endif; ?>
            
            var totalItems = <?php echo count($orderItems); ?>;
            var ratedItems = 0;

            $('.star-rating i').on('click', function () {
                var rating = $(this).data('rate');
                var itemId = $(this).closest('.star-rating').data('item-id');
                var $itemRow = $(this).closest('tr'); // Get the table row of the rated item

                console.log("Item ID:", itemId, "Rating:", rating); // Debugging

                $.ajax({
                    type: 'POST',
                    url: 'order-track.php',
                    data: { item_id: itemId, rating: rating },
                    success: function(response) {
                        console.log("Server Response:", response); // Debugging

                        // Show the popup
                        $('#ratingPopup').fadeIn();

                        // Hide the popup after 2 seconds
                        setTimeout(function () {
                            $('#ratingPopup').fadeOut();

                            // Remove the rated item from the table
                            $itemRow.fadeOut(500, function () {
                                $(this).remove();
                                ratedItems++;

                                // Check if all items have been rated
                                if (ratedItems >= totalItems) {
                                    $('#orderTable').hide();
                                    $('#thankYouMessage').fadeIn();
                                }
                            });
                        }, 2000);
                    },
                    error: function() {
                        alert('Error updating rating.');
                    }
                });
            });
        });
    </script>
</body>
</html>