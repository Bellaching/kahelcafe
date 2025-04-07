<?php
include './../../connection/connection.php';
include './../inc/topNav.php';

require './../../vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Label\Label;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view this page.");
}

$userId = $_SESSION['user_id'];

// 1. First check reservation status from reservation table
$query = "SELECT * FROM reservation WHERE client_id = ? ORDER BY date_created DESC LIMIT 1";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$reservation = $result->fetch_assoc();
$stmt->close();

// Check if reservation exists and is not cancel
if (!$reservation || $reservation['res_status'] === 'cancel') {
    echo "<div class='container text-center mt-5'>
            <h4>No reservation found.</h4>
            <p>We're sorry, but no current reservations are available. 😔</p>
            <p>If you have any questions or believe this was a mistake, please contact us for assistance.</p>
          </div>";
    exit();
}

$reservationId = $reservation['id'] ?? null;
$reservationTimeValue = 'Time not specified'; // Default value

if ($reservationId) {
    // Direct query now that we know the exact table/column names
    $timeQuery = "SELECT reservation_time FROM resservation_time WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($timeQuery);
    
    if ($stmt) {
        $stmt->bind_param("i", $reservationId);
        if ($stmt->execute()) {
            $timeResult = $stmt->get_result();
            if ($timeResult->num_rows > 0) {
                $timeData = $timeResult->fetch_assoc();
                $reservationTimeValue = $timeData['reservation_time'];
                
                // Debug output (check your error logs)
                error_log("Reservation time found: " . $reservationTimeValue . 
                         " for reservation ID: " . $reservationId);
            } else {
                error_log("No time found in resservation_time for ID: " . $reservationId);
            }
        } else {
            error_log("Error executing time query: " . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log("Failed to prepare time query: " . $conn->error);
    }
} else {
    error_log("No valid reservation ID found");
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

$res = $reservationTimeValue;

// 3. Handle Timeout Reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['timeout_reservation'], $_POST['id'])) {
    $reservationId = $_POST['id'];

    // Update reservation status
    $timeoutQuery = "UPDATE reservation SET 
                    res_status = 'cancel', 
                    reservation_date = NULL 
                    WHERE id = ?";
    $stmt = $conn->prepare($timeoutQuery);

    if (!$stmt) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'SQL Error: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("i", $reservationId);
    $stmt->execute();
    
    // Delete from resservation_time
    $deleteTimeQuery = "DELETE FROM resservation_time WHERE reservation_time_id = ?";
    $stmt2 = $conn->prepare($deleteTimeQuery);
    $stmt2->bind_param("i", $reservationId);
    $stmt2->execute();
    
    $stmt->close();
    $stmt2->close();
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Reservation cancel due to timeout']);
    exit();
}

// 4. Handle Manual Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation'], $_POST['id'])) {
    $reservationId = $_POST['id'];

    $cancelQuery = "UPDATE reservation SET 
                    res_status = 'cancel', 
                    reservation_date = NULL 
                    WHERE id = ?";
    $stmt = $conn->prepare($cancelQuery);

    if (!$stmt) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'SQL Error: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("i", $reservationId);
    $stmt->execute();
    
    $deleteTimeQuery = "DELETE FROM resservation_time WHERE reservation_time_id = ?";
    $stmt2 = $conn->prepare($deleteTimeQuery);
    $stmt2->bind_param("i", $reservationId);
    $stmt2->execute();
    
    if ($stmt->execute()) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Reservation cancel successfully']);
        exit();
    } else {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error executing query: ' . $stmt->error]);
        exit();
    }
    $stmt->close();

    exit();
}

// 5. Handle Receipt Upload - FIXED SECTION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_receipt'])) {
    $reservationId = $_POST['reservation_id'] ?? null;
    if (!$reservationId) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Reservation ID is missing']);
        exit();
    }

    if (!empty($_FILES['receipt']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = $_FILES['receipt']['type'];
        $fileName = uniqid() . '_' . basename($_FILES['receipt']['name']);
        $targetDir = './../../uploads/';
        $targetFile = $targetDir . $fileName;

        // Create uploads directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $targetFile)) {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Update both receipt and status to 'paid'
                    $uploadQuery = "UPDATE reservation SET receipt = ?, res_status = 'paid' WHERE id = ?";
                    $stmt = $conn->prepare($uploadQuery);

                    if ($stmt) {
                        $stmt->bind_param("si", $fileName, $reservationId);
                        if ($stmt->execute()) {
                            // Check if rows were affected
                            if ($stmt->affected_rows > 0) {
                                $conn->commit();
                                $_SESSION['payment_success'] = true;
                                header('Content-Type: application/json');
                                echo json_encode([
                                    'status' => 'success', 
                                    'message' => 'Receipt uploaded successfully and status updated to Paid!'
                                ]);
                                exit();
                            } else {
                                $conn->rollback();
                                header('Content-Type: application/json');
                                echo json_encode([
                                    'status' => 'warning', 
                                    'message' => 'No changes were made to the database. Maybe the record wasn\'t found?'
                                ]);
                                exit();
                            }
                        } else {
                            $conn->rollback();
                            header('Content-Type: application/json');
                            echo json_encode([
                                'status' => 'error', 
                                'message' => 'Database error: ' . $stmt->error
                            ]);
                            exit();
                        }
                        $stmt->close();
                    } else {
                        $conn->rollback();
                        header('Content-Type: application/json');
                        echo json_encode([
                            'status' => 'error', 
                            'message' => 'Failed to prepare database statement.'
                        ]);
                        exit();
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    header('Content-Type: application/json');
                    echo json_encode([
                        'status' => 'error', 
                        'message' => 'Transaction failed: ' . $e->getMessage()
                    ]);
                    exit();
                }
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Failed to move uploaded file. Check directory permissions.'
                ]);
                exit();
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error', 
                'message' => 'Only JPG, JPEG, and PNG files are allowed.'
            ]);
            exit();
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error', 
            'message' => 'Please select a file to upload.'
        ]);
        exit();
    }
}

// 6. Display cancellation message if reservation is cancel
if ($reservation['res_status'] === 'cancel'): ?>
    <div class='container text-center mt-5'>
        <h4>No Order Tracking History</h4>
        <p>We're sorry, but no current orders are available. 😔</p>
        <p>If you have any questions or believe this was a mistake, please contact us for assistance.</p>
    </div>
    <?php exit(); ?>
<?php endif; 

// 7. Generate QR Code for booked reservations
if ($reservationId && $reservation['res_status'] === 'booked' || $reservationId && $reservation['res_status'] === 'rate us') {
    $qrData = "Reservation ID: {$reservationId}\n"
    . "Name: {$reservation['clientFullName']}\n"
    . "Date: {$reservation['reservation_date']}\n"
    . "Time: {$reservation['reservation_time']}\n"
    . "Party Size: {$reservation['party_size']}\n"
    . "Note: {$reservation['note_area']}\n"
    . "Total: ₱" . number_format($reservation['amount'], 2);


    $qrCode = Builder::create()
        ->writer(new PngWriter())
        ->data($qrData)
        ->encoding(new Encoding('UTF-8'))
        ->size(200)
        ->margin(10)
        ->build();

    $qrFile = './../../uploads/qrcodes/reservation_' . $reservationId . '.png';
    
    // Create directory if it doesn't exist
    if (!file_exists(dirname($qrFile))) {
        mkdir(dirname($qrFile), 0755, true);
    }
    
    file_put_contents($qrFile, $qrCode->getString());
}

// 8. Handle Rating Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'], $_POST['rating'])) {
    $itemId = (int)$_POST['item_id'];
    $rating = (int)$_POST['rating'];

    $query = "UPDATE menu1 SET rating = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $rating, $itemId);

    if ($stmt->execute()) {
        echo "Rating updated successfully!";
    } else {
        echo "Error updating rating: " . $stmt->error;
    }

    $stmt->close();
    exit();
}

// 9. Create New Reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_reservation'])) {
    $reservationTimeValue = $_POST['reservation_time'];
    $fullName = $_POST['full_name'];
    $partySize = $_POST['party_size'];
    $reservationDate = $_POST['reservation_date'];
    $amount = $_POST['amount'];
    
    // Insert into reservation table
    $query = "INSERT INTO reservation 
              (client_id, clientFullName, party_size, reservation_date, amount, res_status) 
              VALUES (?, ?, ?, ?, ?, 'for confirmation')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssd", $userId, $fullName, $partySize, $reservationDate, $amount);
    $stmt->execute();
    $reservationId = $stmt->insert_id;
    $stmt->close();
    
    // Insert into resservation_time table
    $timeQuery = "INSERT INTO resservation_time (reservation_time_id, reservation_time) VALUES (?, ?)";
    $stmt2 = $conn->prepare($timeQuery);
    $stmt2->bind_param("is", $reservationId, $reservationTimeValue);
    $stmt2->execute();
    $stmt2->close();
    
    header("Location: reservation_status.php");
    exit();
}

// 10. Fetch Reservation Details for Display
$reservationDetails = [];
if ($reservationId) {
    $query = "SELECT * FROM reservation WHERE id = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("i", $reservationId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $reservationDetails[] = $row;
        }
        $stmt->close();
    }
}

$note = $reservation['note_area'] ?? 'No notes available.';

// Display the reservation information
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Reservation Confirmation</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
        }
        
        .order-tracking {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 50px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .order-tracking .step {
            position: relative;
            text-align: center;
            min-width: 80px;
            flex: 1;
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
            word-break: break-word;
        }
        .order-tracking .step .description {
            font-size: 12px;
            color: #888;
            word-break: break-word;
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
            margin-bottom: 1rem;
        }

        .down-con {
            display: flex;
            flex-direction: column;
        }

        .right-content {
            margin-top: 1rem;
        }

        .upl-p {
            color: #FF902B;
            font-weight: bold;
        }

        .send {
            background: #07D090;
        }

        .amount {
            color: #FF902B;
            font-weight: bold;
        }

        .back-btn {
            border: none;
            background-color: #07D090;
        }
        
        #payment-timer {
            font-size: 2rem;
            font-weight: bold;
            color: #dc3545;
            text-align: center;
            margin: 10px 0;
        }
        
        .btn-disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .payment-success {
            background-color: #fff;
            border: 1px solid  #FF902B;
            color:  #FF902B;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .payment-success i {
            color:  #FF902B;
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .payment-success h4 {
            color: #FF902B;
            margin-bottom: 15px;
        }
        
        .payment-success p {
            margin-bottom: 0;
            font-size: 1.1rem;
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

        /* .qr-container {
            margin-top: 1rem;
            text-align: center;
        }

        .qr-container img {
            max-width: 100%;
            height: auto;
        } */

        /* Center content for confirmation status */
        .confirmation-center {
            text-align: center;
            max-width: 600px;
            margin: 0 auto;
        }

        @media (min-width: 768px) {
            .down-con {
                flex-direction: row;
                align-items: flex-start;
            }
            
            .left-content {
                flex: 0 0 60%;
                max-width: 60%;
                padding-right: 15px;
            }
            
            .right-content {
                flex: 0 0 40%;
                max-width: 40%;
                margin-top: 0;
                padding-left: 15px;
            }
        }

        /* Android touch target sizes */
        button, .btn, [role="button"] {
            min-height: 48px;
            min-width: 48px;
            padding: 12px 16px;
        }
        
        input, select, textarea {
            font-size: 16px !important; /* Prevent zooming on focus */
        }
        
        /* Prevent long press actions */
        a, button, img {
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -khtml-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
    </style>
</head>
<body>
    <div class="container mt-5 ">
        <h3>Reservation Tracking</h3>

        <!-- Reservation Tracking Steps -->
        <div class="order-tracking ">
            <div class="step <?php echo ($reservation['res_status'] === 'for confirmation' ? 'active' : ''); ?>">
                <div class="icon"></div>
                <div class="title">For Confirmation</div>
                <div class="description">We're confirming your reservation</div>
            </div>
            <div class="step <?php echo ($reservation['res_status'] === 'payment' || $reservation['res_status'] === 'paid' ? 'active' : ''); ?>">
                <div class="icon"></div>
                <div class="title">Payment</div>
                <div class="description">Payment processing</div>
            </div>
            <div class="step <?php echo ($reservation['res_status'] === 'booked' ? 'active' : ''); ?>">
                <div class="icon"></div>
                <div class="title">Booked</div>
                <div class="description">Your reservation has been confirmed</div>
            </div>
            <div class="step <?php echo ($reservation['res_status'] === 'rate us' ? 'active' : ''); ?>">
                <div class="icon fs-5"></div>
                <div class="title">Complete</div>
                <div class="description">Thankyou!</div>
            </div>
        </div>

        <div class="down-con  mx-4">
            <div class="left-content">
                <?php if ($reservation['res_status'] === 'booked' || $reservation['res_status'] === 'paid' || $reservation['res_status'] === 'payment' || $reservation['res_status'] === 'for confirmation' || $reservation['res_status'] === 'rate us'): ?>
                    <div class="order-sum">
                        <h5 class="bold">Reservation Summary</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Name:</strong></p>
                                    <p><?php echo htmlspecialchars($reservation['clientFullName']); ?></p>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Transaction ID:</strong></p>
                                    <p><?php echo htmlspecialchars($reservation['transaction_code']); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Reservation Date:</strong></p>
                                    <p><?php echo htmlspecialchars($reservation['reservation_date']); ?></p>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Reservation Time:</strong></p>
                                    <p><?php echo htmlspecialchars($res); ?></p>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Party Size:</strong></p>
                                    <p><?php echo htmlspecialchars($reservation['party_size']); ?></p>
                                </div>
                                <div class="d-flex justify-content-between w-100">
                                    <p><strong>Reservation Fee:</strong></p>
                                    <p><?php echo htmlspecialchars($reservation['clientFullName']); ?></p>
                                </div>
                            </div>
                        </div>
                        <p><strong>Total Price:</strong> ₱<?php echo number_format($reservation['amount'], 2); ?></p>
                    </div>

                    <div class="note-section mt-4">
                        <label for="user-note" class="p-1">Notes</label>
                        <div id="user-note" class="form-control"
                            style="border-color: #B3B3B3; border-radius: 10px; height: 150px; overflow-y: auto; padding: 10px; 
                            background-color: #f8f9fa; pointer-events: none; user-select: none;">
                            <?php 
                            if (!empty($reservation['note_area'])) {
                                echo "<p>" . htmlspecialchars($reservation['note_area']) . "</p>";
                            } else {
                                echo "No notes available.";
                            }
                            ?>
                        </div>
                    </div>
                    
                    <?php if ($reservation['res_status'] === 'for confirmation'): ?>
                        <div class="mt-4 text-center">
                            <form method="POST">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
                                <button type="button" class="btn btn-danger rounded mt-3 container-fluid" data-bs-toggle="modal" data-bs-target="#cancelReservationModal">
                                    Cancel Reservation
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="right-content col-md-4 px-5 ">
                <?php if ($reservation['res_status'] === 'payment'): ?>
                
                        <div class="border rounded shadow-sm p-3">
                            <form method="POST" enctype="multipart/form-data" class="mt-3" id="receiptForm">
                             <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
                             <input type="hidden" name="upload_receipt" value="1">
                             
                             <!-- Timer Section -->
                             <div class="mb-3 text-center">
                                <h5>Time Remaining to Complete Payment</h5>
                                <div id="payment-timer" class="fs-3 fw-bold text-danger">30:00</div>
                                <small class="text-muted">Please complete your payment before time runs out</small>
                             </div>

                                <div class="mb-3">
                                    <label for="receipt" class="form-label upl-p">Upload Receipt</label>
                                    <input type="file" class="form-control" id="receipt" name="receipt" required>
                                </div>
                                <div class="mb-3">
                                    <label for="receipt" class="form-label upl-p">Instructions</label>
                                    <ul>
                                        <li>1. Please upload receipt upon confirmation</li>
                                        <li>2. Upon confirmation, there will be an allotted time of 30 minutes to confirm for payment.</li>
                                        <li>3. Failure to do so will result in cancellation of reservation.</li>
                                    </ul>
                                </div>
                                <button type="submit" class="btn rounded send text-light fw-bold w-100" id="sendReceiptBtn" disabled>Send Receipt</button>
                            </form>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
                                <button type="button" class="btn btn-danger rounded mt-3 w-100" data-bs-toggle="modal" data-bs-target="#cancelReservationModal">Cancel Reservation</button>
                            </form>
                        </div>
                   
                <?php elseif ($reservation['res_status'] === 'paid'): ?>
                    <div class="text-center p-4 rounded shadow container-fluid mb-5" style="background-color: #FF902B; color: white;">
                    <div class="check-circle ">
                            <i class="fas fa-check"></i>
                        </div>
                        <h4 class="mb-2"><i class="bi bi-check-circle-fill"></i> Payment Successful!</h4>
                        <p>Your payment has been received. <strong>Kahel Cafe</strong> is currently verifying your receipt. Please wait for confirmation.</p>
                    </div>
                   
                <?php elseif ($reservation['res_status'] === 'booked' || $reservation['res_status'] === 'rate us'): ?>
                    <form method="POST" class="mt-3">
                  
                    <div class="text-center mt-4">
                        <h5>Scan to View Reservation Details</h5>
                        <img src="<?php echo $qrFile; ?>" alt="Reservation QR Code" class="img-fluid" />
                        <br>
                        </div>
                        </form>
                        <div class="alert alert-info mt-2 text-center">
                            <strong>Please show this QR code to the cashier.</strong>
                        </div>
                      
                        <div class="qr container-fluid my-4 d-flex justify-content-center rounded-pill">
                        <a href="<?php echo $qrFile; ?>" download="reservation_qr_<?php echo $reservation['id']; ?>.png" class="btn p-2 w-100 rounded-pill text-light" style="background-color: #FF902B;">
                            Download QR Code
                        </a>
                   
                        </div>
                   

                    <?php if ($reservation['res_status'] === 'rate us'): ?>
                        <div class="back mt-3">
                            <button class="back-btn p-2 w-100 rounded-pill text-light" onclick="window.location.href='../../user/views/index.php'">Back to home</button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

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
                            <li>Cancel the reservation</li>
                        </ol>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="continuePayment">Continue Payment</button>
                        <button type="button" class="btn btn-danger" id="cancelAfterTimeout">Cancel Reservation</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Reservation Modal -->
        <div class="modal fade" id="cancelReservationModal" tabindex="-1" aria-labelledby="cancelReservationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelReservationModalLabel">Confirm Cancellation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to cancel this reservation?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" id="confirmCancel" class="btn btn-danger">Yes, Cancel Reservation</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Loading Indicator -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Enable/disable send receipt button based on file selection
        $('#receipt').on('change', function() {
            if ($(this).val() !== '') {
                $('#sendReceiptBtn').prop('disabled', false);
            } else {
                $('#sendReceiptBtn').prop('disabled', true);
            }
        });
        
        // Handle receipt upload form submission with better error handling
        $('#receiptForm').on('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = $('#sendReceiptBtn');
            submitBtn.prop('disabled', true);
            submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...');
            
            // Create FormData object
            const formData = new FormData(this);
            
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Show success message and reload page
                        alert(response.message);
                        window.location.reload();
                    } else {
                        // Show error message
                        alert(response.message || 'Error uploading receipt');
                        submitBtn.prop('disabled', false);
                        submitBtn.text('Send Receipt');
                    }
                },
                error: function(xhr, status, error) {
                    // Handle AJAX errors
                    // let errorMessage = 'Error uploading receipt. Please try again.';
                    alert(response.message);
                    window.location.reload();
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage = response.message;
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                    
                    alert(errorMessage);
                    submitBtn.prop('disabled', false);
                    submitBtn.text('Send Receipt');
                }
            });
        });
        
        // Timer functionality for payment status
        <?php if ($reservation['res_status'] === 'payment'): ?>
            // Calculate the exact time remaining in seconds
            const paymentTimeLimit = 30 * 60; // 30 minutes in seconds
            const createdTime = new Date("<?php echo $reservation['date_created']; ?>").getTime();
            const currentTime = new Date().getTime();
            const elapsedSeconds = Math.floor((currentTime - createdTime) / 1000);
            let timeLeft = Math.max(0, paymentTimeLimit - elapsedSeconds);

            const timerElement = document.getElementById('payment-timer');
            const timeoutModal = new bootstrap.Modal(document.getElementById('timeoutModal'));
            
            // Function to update the timer display
            function updateTimer() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = Math.floor(timeLeft % 60);
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
            const timerInterval = setInterval(() => {
                timeLeft--;
                updateTimer();
            }, 1000);
            
            // Handle continue payment button
            $('#continuePayment').click(function() {
                // Reset the timer for another 30 minutes
                timeLeft = 30 * 60;
                updateTimer();
                clearInterval(timerInterval);
                timerInterval = setInterval(() => {
                    timeLeft--;
                    updateTimer();
                }, 1000);
                timeoutModal.hide();
            });
            
            // Handle cancel after timeout button
            $('#cancelAfterTimeout').click(function() {
                // Cancel the reservation
                $.ajax({
                    type: 'POST',
                    url: window.location.href,
                    data: {
                        timeout_reservation: true,
                        id: <?php echo $reservation['id']; ?>
                    },
                    success: function(response) {
                        window.location.reload();
                    },
                    error: function() {
                        alert('Error cancelling reservation');
                    }
                });
            });
            
            // Also update the database when timer runs out
            function handleTimeout() {
                $.ajax({
                    type: 'POST',
                    url: window.location.href,
                    data: {
                        timeout_reservation: true,
                        id: <?php echo $reservation['id']; ?>
                    },
                    success: function(response) {
                        // Modal will handle the UI
                    },
                    error: function() {
                        console.log('Error updating reservation status');
                    }
                });
            }
            
            // Check if we need to show timeout modal on page load
            <?php 
            // Calculate if the payment time has expired
            $createdTime = strtotime($reservation['date_created']);
            $currentTime = time();
            $elapsedTime = $currentTime - $createdTime;
            if ($elapsedTime > 30 * 60) {
                echo 'timeoutModal.show();';
                echo 'handleTimeout();';
            }
            ?>
        <?php endif; ?>
        
        // Cancel reservation handler - FIXED
        $('#confirmCancel').on('click', function () {
            var reservationId = <?php echo json_encode($reservation['id']); ?>;
            
            console.log('Attempting to cancel reservation ID:', reservationId);
            
            // Create form data
            var formData = new FormData();
            formData.append('cancel_reservation', 'true');
            formData.append('id', reservationId);
            
            // Submit the request
            $.ajax({
                type: 'POST',
                url: window.location.href,
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        // Try to parse JSON response
                        var jsonResponse = JSON.parse(response);
                        if (jsonResponse.status === 'success') {
                            alert('Reservation canceled successfully');
                            window.location.reload();
                        } else {
                            alert('Error: ' + (jsonResponse.message || 'Unknown error occurred'));
                        }
                    } catch (e) {
                        // If not JSON, check for HTML response
                        if (response.indexOf('success') !== -1) {
                            alert('Reservation canceled successfully');
                            window.location.reload();
                        } else {
                            alert('Error canceling reservation');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('Error canceling reservation. Please try again.');
                }
            });
        });

        // Add real-time status checking with better error handling and logging
        function checkReservationStatus() {
            console.log('Checking reservation status...');
            
            $.ajax({
                url: 'check_reservation_status.php',
                type: 'GET',
                data: {
                    reservation_id: <?php echo $reservation['id']; ?>
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Current status:', response.status, 'Original status:', '<?php echo $reservation['res_status']; ?>');
                    
                    if (response.status && response.status !== '<?php echo $reservation['res_status']; ?>') {
                        console.log('Status changed to:', response.status, '- Reloading page...');
                        window.location.reload();
                    } else {
                        console.log('Status unchanged');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error checking status:', error);
                    // Retry after delay even if error occurs
                    setTimeout(checkReservationStatus, 5000);
                },
                complete: function() {
                    // Check again after 3 seconds (more frequent checking)
                    setTimeout(checkReservationStatus, 3000);
                }
            });
        }

        // Start checking status if not in final states
        <?php if (!in_array($reservation['res_status'], ['cancel', 'rate us'])): ?>
            // Start checking after short delay to let page fully load
            setTimeout(checkReservationStatus, 1000);
        <?php endif; ?>
    });
    </script>

<script>
        // Android-friendly touch events
        document.addEventListener('DOMContentLoaded', function() {
            // Make buttons more responsive to touch
            const buttons = document.querySelectorAll('button, .btn, [role="button"]');
            buttons.forEach(button => {
                button.addEventListener('touchstart', function() {
                    this.classList.add('active');
                });
                button.addEventListener('touchend', function() {
                    this.classList.remove('active');
                });
            });
            
            // Prevent zooming on input focus
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.fontSize = '16px';
                });
            });
        });
    </script>
</body>
</html>