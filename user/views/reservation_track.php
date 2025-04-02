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

// Check if reservation exists and is not cancelled
if (!$reservation || $reservation['res_status'] === 'cancelled') {
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
                    res_status = 'cancelled', 
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
    echo json_encode(['status' => 'success', 'message' => 'Reservation cancelled due to timeout']);
    exit();
}

// 4. Handle Manual Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation'], $_POST['id'])) {
    $reservationId = $_POST['id'];

    $cancelQuery = "UPDATE reservation SET 
                    res_status = 'cancelled', 
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
        echo json_encode(['status' => 'success', 'message' => 'Reservation cancelled successfully']);
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
        die("Error: Reservation ID is missing.");
    }

    if (!empty($_FILES['receipt']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = $_FILES['receipt']['type'];
        $fileName = uniqid() . '_' . basename($_FILES['receipt']['name']); // Add unique ID to filename
        $targetDir = './../../uploads/';
        $targetFile = $targetDir . $fileName;

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $targetFile)) {
                // Update both receipt and status to 'paid'
                $uploadQuery = "UPDATE reservation SET receipt = ?, res_status = 'paid' WHERE id = ?";
                $stmt = $conn->prepare($uploadQuery);

                if ($stmt) {
                    $stmt->bind_param("si", $fileName, $reservationId);
                    if ($stmt->execute()) {
                        // Check if rows were affected
                        if ($stmt->affected_rows > 0) {
                            echo "<div class='alert alert-success'>Receipt uploaded successfully and status updated to Paid!</div>";
                            // Refresh the page to show updated status
                            echo "<script>setTimeout(function(){ window.location.reload(); }, 1500);</script>";
                        } else {
                            echo "<div class='alert alert-warning'>No changes were made to the database. Maybe the record wasn't found?</div>";
                        }
                    } else {
                        echo "<div class='alert alert-danger'>Database error: " . $stmt->error . "</div>";
                    }
                    $stmt->close();
                } else {
                    echo "<div class='alert alert-danger'>Failed to prepare database statement.</div>";
                }
            } else {
                echo "<div class='alert alert-danger'>Failed to move uploaded file. Check directory permissions.</div>";
                error_log("Failed to move uploaded file. Target: " . $targetFile);
            }
        } else {
            echo "<div class='alert alert-danger'>Only JPG, JPEG, and PNG files are allowed.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Please select a file to upload.</div>";
    }
}

// 6. Display cancellation message if reservation is cancelled
if ($reservation['res_status'] === 'cancelled'): ?>
    <div class='container text-center mt-5'>
        <h4>No Order Tracking History</h4>
        <p>We're sorry, but no current orders are available. 😔</p>
        <p>If you have any questions or believe this was a mistake, please contact us for assistance.</p>
    </div>
    <?php exit(); ?>
<?php endif; 

// 7. Generate QR Code for booked reservations
if ($reservationId && $reservation['res_status'] === 'booked' || $reservationId && $reservation['res_status'] === 'rate us') {
    $qrData = "Reservation ID: {$reservationId}\nName: {$reservation['clientFullName']}\nTime: {$reservationTimeValue}\nTotal: ₱" . number_format($reservation['amount'], 2);

    $qrCode = Builder::create()
        ->writer(new PngWriter())
        ->data($qrData)
        ->encoding(new Encoding('UTF-8'))
        ->size(200)
        ->margin(10)
        ->build();

    $qrFile = './../../uploads/qrcodes/reservation_' . $reservationId . '.png';
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

$note = $orderItems[0]['note'] ?? 'No notes available.';

// Display the reservation information
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Confirmation</title>
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

        .amount {
            color: #FF902B;
            font-weight: bold;
        }

        .back-btn {
            border: none;
            background-color: 07D090;
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
            color:  #FF902Bf;
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
            border: 5px solid  #FF902B;
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
        <h3>Reservation Tracking</h3>

        <!-- Reservation Tracking Steps -->
        <div class="order-tracking">
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
                
            </div>
        </div>

        <div class="down-con container-fluid d-flex flex-column">

            <?php if ($reservation['res_status'] === 'booked'): ?>
                <div class="order-sum container-fluid">
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

                <div class="note-section mt-4 container-fluid">
                    <label for="user-note" class="p-1">Notes</label>
                    <div id="user-note" class="form-control"
                        style="border-color: #B3B3B3; border-radius: 10px; height: 150px; overflow-y: auto; padding: 10px; 
                        background-color: #f8f9fa; pointer-events: none; user-select: none;">
                        <?php 
                        $hasNotes = false;
                        foreach ($reservationDetails as $item) {
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

            <?php elseif ($reservation['res_status'] === 'for confirmation'): ?>
                <!-- Content for "for confirmation" status -->
            <?php endif; ?>

            <?php if ($reservation['res_status'] === 'payment'): ?>
                <div class="container-fluid">
                    <div class="row">
                        <!-- Left Side: Reservation Summary -->
                        <div class="col-md-7">
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
                                    $hasNotes = false;
                                    foreach ($reservationDetails as $item) {
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
                        </div>

                        <!-- Right Side: Form -->
                       
                        <div class="col-md-5  text-center">

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
                                <button type="submit" class="btn rounded send text-light fw-bold container-fluid" id="sendReceiptBtn" disabled>Send Receipt</button>
                            </form>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
                                <button type="button" class="btn btn-danger rounded mt-3 container-fluid" data-bs-toggle="modal" data-bs-target="#cancelReservationModal">Cancel Reservation</button>
                            </form>
                           
                    </div>
                </div>

                <?php elseif ($reservation['res_status'] === 'paid'): ?>
                <div class="container-fluid">
                    <div class="row">
                        <!-- Left Side: Reservation Summary -->
                        <div class="col-md-7">
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
                                    $hasNotes = false;
                                    foreach ($reservationDetails as $item) {
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
                        </div>

                        <!-- Right Side: Form -->
                       
                        <div class="col-md-5  text-center">

                   
                          
                        
                             
                             <div class="payment-success">
                                    <div class="check-circle">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <h4>Thank You for Your Payment!</h4>
                                    <p>Your payment has been received successfully.</p>
                                    <p>Please wait for the shop to confirm your reservation.</p>
                                </div>


                      
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

            <?php elseif ($reservation['res_status'] === 'booked' ): ?>
                <form method="POST" class="mt-3">
                    <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
                    <div class="text-center mt-4">
                        <h5>Scan to View Reservation Details</h5>
                        <img src="<?php echo $qrFile; ?>" alt="Reservation QR Code" class="img-fluid" />
                        <br>
                    </div>
                    <div class="mb-3 text-left">
    <label class="form-label upl-p">Important</label>
    <div class="alert alert-info">
        <strong>Please show this QR code to the cashier.</strong>
    </div>
</div>
                    <div class="qr container-fluid my-4 d-flex justify-content-center rounded-pill">
                        <a href="<?php echo $qrFile; ?>" download="reservation_qr_<?php echo $reservation['id']; ?>.png" class="btn btn-primary mt-2">
                            Download QR Code
                        </a>
                    </div>
                </form>
            <?php elseif ($reservation['res_status'] === 'for confirmation'): ?>
                <div class="order-sum">
                    <h5 class="bold">Reservation Summary</h5>
                    <div class="row container-fluid">
                        <div class="col-md-6 ">
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
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
                        <button type="button" class="btn btn-danger container-fluid rounded-pill" data-bs-toggle="modal" data-bs-target="#cancelReservationModal">Cancel Reservation</button>
                    </form>
                    <?php elseif ($reservation['res_status'] === 'rate us'): ?>
    <div class="order-sums container-fluid">
        <h5 class="bold py-3">Reservation Summary</h5>

        <div class="d-flex justify-content-between w-100">
            <p><strong>Name:</strong></p>
            <p><?php echo htmlspecialchars($reservation['clientFullName']); ?></p>
        </div>
        <div class="d-flex justify-content-between w-100">
            <p><strong>Transaction ID:</strong></p>
            <p><?php echo htmlspecialchars($reservation['transaction_code']); ?></p>
        </div>
    
        <?php if (!empty($reservationDetails)): ?>
            <div class="d-flex justify-content-between w-100">
                <p><strong>Reservation Date:</strong></p>
                <p><?php echo htmlspecialchars($reservationDetails[0]['reservation_date']); ?></p>
            </div>
            <div class="d-flex justify-content-between w-100">
                <p><strong>Reservation Time:</strong></p>
                <p><?php echo htmlspecialchars($res); ?></p>
            </div>
            <div class="d-flex justify-content-between w-100">
                <p><strong>Party Size:</strong></p>
                <p><?php echo htmlspecialchars($reservationDetails[0]['party_size']); ?></p>
            </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between w-100">
            <p><strong>Subtotal:</strong></p>
            <p><?php echo htmlspecialchars($reservation['clientFullName']); ?></p>
        </div>
        <div class="d-flex justify-content-between w-100">
            <p><strong>Reservation Fee:</strong></p>
            <p><?php echo htmlspecialchars($reservation['clientFullName']); ?></p>
        </div>
        <div class="d-flex justify-content-between w-100">
            <p><strong>Total Price:</strong></p>
            <p class="amount"> ₱<?php echo number_format($reservation['amount'], 2); ?></p>
        </div>

        <form method="POST" class="mt-3">
                    <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
                    <div class="text-center mt-4">
                        <h5>Scan to View Reservation Details</h5>
                        <img src="<?php echo $qrFile; ?>" alt="Reservation QR Code" class="img-fluid" />
                        <br>
                    </div>
                    <div class="mb-3 text-left">
    <label class="form-label upl-p">Important</label>
    <div class="alert alert-info">
        <strong>Please show this QR code to the cashier.</strong>
    </div>
</div>
                    <div class="qr container-fluid my-4 d-flex justify-content-center rounded-pill">
                        <a href="<?php echo $qrFile; ?>" download="reservation_qr_<?php echo $reservation['id']; ?>.png" class="btn btn-primary mt-2">
                            Download QR Code
                        </a>
                    </div>
                </form>

       
        <div class="back">
            <button class="back-btn m-3 p-2 container-fluid rounded-pill text-light" onclick="window.location.href='../../user/views/index.php'">Back to home</button>
        </div>
    </div>
<?php endif; ?>
       <!-- Modal Structure -->
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
    </div>
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
        
        // Validate file type before submission
        $('#receiptForm').on('submit', function(e) {
            const fileInput = $('#receipt')[0];
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!validTypes.includes(file.type)) {
                    alert('Only JPG, JPEG, and PNG files are allowed.');
                    e.preventDefault();
                    return false;
                }
            }
            return true;
        });

        // Timer functionality for payment status
        <?php if ($reservation['res_status'] === 'payment'): ?>
            // Calculate time remaining
            const createdTime = new Date("<?php echo $reservation['date_created']; ?>").getTime();
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
                // Cancel the reservation
                $.ajax({
                    type: 'POST',
                    url: window.location.href,
                    data: {
                        cancel_reservation: true,
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
        
        // Cancel reservation handler
        $('#confirmCancel').on('click', function () {
            var reservationId = <?php echo json_encode($reservation['id']); ?>;
            
            console.log('Attempting to cancel reservation ID:', reservationId);
            
            // Create a hidden form to submit the request
            var form = $('<form>', {
                'method': 'POST',
                'action': window.location.href
            }).append(
                $('<input>', {
                    'type': 'hidden',
                    'name': 'cancel_reservation',
                    'value': 'true'
                }),
                $('<input>', {
                    'type': 'hidden',
                    'name': 'id',
                    'value': reservationId
                })
            );
            
            // Submit the form
            $.ajax({
                type: 'POST',
                url: window.location.href,
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    console.log('Response:', response);
                    if (response && response.status === 'success') {
                        alert('Reservation cancelled successfully');
                        window.location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Unknown error occurred'));
                    }
                },
                error: function(xhr, status, error) {
                    location.reload(); 
                }
            });
        });
        
        // Receipt upload form handler
        $('#receiptForm').on('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(this);
            
            $.ajax({
                type: 'POST',
                url: window.location.href,
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Check if the response contains our success message
                    if (response.indexOf('alert-success') !== -1) {
                        // Reload the page after a short delay
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        // Show error message if something went wrong
                        alert('Error uploading receipt. Please try again.');
                    }
                },
                error: function() {
                    alert('Error uploading receipt. Please try again.');
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
<?php if (!in_array($reservation['res_status'], ['cancelled', 'rate us'])): ?>
    // Start checking after short delay to let page fully load
    $(document).ready(function() {
        setTimeout(checkReservationStatus, 1000);
    });
<?php endif; ?>
    });
    </script>
</body>
</html>