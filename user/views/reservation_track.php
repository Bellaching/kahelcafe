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

// Fetch the most recent reservation for the logged-in user
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

// If no reservation or it's cancelled, don't proceed with the script
if (!$reservation || $reservation['res_status'] === 'cancelled') {
    echo "<div class='container text-center mt-5'>
            <h4>The reservation has been cancelled.</h4>
            <p>We're sorry, but no current reservations are available. 😔</p>
            <p>If you have any questions or believe this was a mistake, please contact us for assistance.</p>
          </div>";
    exit();
}

// Handle cancel reservation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation'])) {
    $reservationId = $_POST['id'];

    // Update the reservation res_status to "Cancelled"
    $cancelQuery = "UPDATE reservation SET res_status = 'cancelled' WHERE id = ?";
    $stmt = $conn->prepare($cancelQuery);

    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("i", $reservationId);
    $stmt->execute();
    
    // Ensure the changes are reflected immediately
    $stmt->close();

    // Fetch the updated reservation res_status
    $checkQuery = "SELECT res_status FROM reservation WHERE id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $reservationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $updatedReservation = $result->fetch_assoc();
    $stmt->close();

    // Refresh reservation res_status in the session or variable
    $reservation['res_status'] = $updatedReservation['res_status'];
}

// Recheck the res_status to prevent requiring multiple cancels
if ($reservation['res_status'] === 'cancelled') {
    echo "<div class='container text-center mt-5'>
            <h4>The reservation has been cancelled.</h4>
            <p>We're sorry, but no current reservations are available. 😔</p>
            <p>If you have any questions or believe this was a mistake, please contact us for assistance.</p>
          </div>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_receipt'])) {
    $reservationId = $_POST['reservation_id'] ?? null;
    if (!$reservationId) {
        die("Error: Reservation ID is missing.");
    }

    if (!empty($_FILES['receipt']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png'];
        $fileType = $_FILES['receipt']['type'];
        $fileName = basename($_FILES['receipt']['name']);
        $targetDir = './../../uploads/';
        $targetFile = $targetDir . $fileName;

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $targetFile)) {
                // Save the receipt file name in the database
                $uploadQuery = "UPDATE reservation SET receipt = ? WHERE id = ?";
                $stmt = $conn->prepare($uploadQuery);

                if (!$stmt) {
                    die("SQL Error: " . $conn->error);
                }

                $stmt->bind_param("si", $fileName, $reservationId);
                $stmt->execute();
                $stmt->close();

                echo "<div class='alert alert-success'>Receipt uploaded successfully!</div>";
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
if ($reservation && $reservation['res_status'] === 'booked') {
    $qrData = "Reservation ID: {$reservation['id']}\nName: {$reservation['clientFullName']}\nTotal: ₱" . number_format($reservation['amount'], 2);

    $qrCode = Builder::create()
        ->writer(new PngWriter())
        ->data($qrData)
        ->encoding(new Encoding('UTF-8'))
        ->size(200)
        ->margin(10)
        ->build();

    $qrFile = './../../uploads/qrcodes/reservation_' . $reservation['id'] . '.png';
    file_put_contents($qrFile, $qrCode->getString());
}

// Handle Rating Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'], $_POST['rating'])) {
    $itemId = (int)$_POST['item_id'];
    $rating = (int)$_POST['rating'];

    echo "Received item ID: $itemId, Rating: $rating"; // Debugging output

    $query = "UPDATE menu1 SET rating = ? WHERE id = ?";
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

// Fetch reservation details
$reservationDetails = [];
if ($reservation && isset($reservation['id'])) {
    $query = "SELECT * FROM reservation WHERE id = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("i", $reservation['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $reservationDetails[] = $row; // Store each reservation detail in an array
    }

    $stmt->close();
}

// Get note (if multiple items, get the first one)
$note = $reservationDetails[0]['note'] ?? 'No notes available.';

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
            <div class="step <?php echo ($reservation['res_status'] === 'for payment' ? 'active' : ''); ?>">
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
                <div class="title">Rate Us</div>
                <div class="description">Please rate your experience</div>
            </div>
        </div>

        <div class="down-con container-fluid d-flex flex-column">

           
            

                <?php if ( $reservation['res_status'] === 'booked'): ?>
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
                                    <p><?php echo htmlspecialchars($reservation['reservation_time']); ?></p>
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
                  

                    
                <?php endif; ?>
            
            <!-- left ending -->
            <!-- right-start  -->

          
                <?php if ($reservation['res_status'] === 'for payment'): ?>
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
                            <p><?php echo htmlspecialchars($reservation['reservation_time']); ?></p>
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
        <div class="col-md-5 border rounded shadow-sm ">
        <form method="POST" enctype="multipart/form-data" class="mt-3">
    <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
    <div class="mb-3">
        <label for="receipt" class="form-label upl-p">Upload Receipt</label>
        <input type="file" class="form-control" id="receipt" name="receipt">
    </div>
    <div class="mb-3">
        <label for="receipt" class="form-label upl-p">Instructions</label>
        <ul>
            <li>1. Please upload receipt upon confirmation</li>
            <li>2. Upon confirmation, there will be an allotted time of 30 minutes to confirm for payment.</li>
            <li>3. Failure to do so will result in cancellation of reservation.</li>
        </ul>
    </div>
    <button type="submit" name="upload_receipt" class="btn rounded send text-light fw-bold container-fluid">Send Receipt</button>
</form>
            <form method="POST" class="mt-3">
                <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
                <button type="button" class="btn btn-danger rounded mt-3 container-fluid" data-bs-toggle="modal" data-bs-target="#cancelReservationModal">Cancel Reservation</button>
            </form>
        </div>
    </div>
</div>

                <?php elseif ($reservation['res_status'] === 'booked'): ?>
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
                        <div class="text-center mt-4">
                            <h5>Scan to View Reservation Details</h5>
                            <img src="<?php echo $qrFile; ?>" alt="Reservation QR Code" class="img-fluid" />
                            <br>
                        </div>
                        <div class="mb-3 text-left">
                            <label for="receipt" class="form-label upl-p">Instructions</label>
                            <ul>1. Please upload receipt upon confirmation </ul>
                            <ul>2. Upon confirmation, there will be an allotted time of 30 minutes to confirm payment.</ul>
                            <ul>3. Failure to do so will result in cancellation of reservation.</ul>
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
                            <p><?php echo htmlspecialchars($reservation['reservation_time']); ?></p>
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
                                <p><?php echo htmlspecialchars($reservationDetails[0]['reservation_time']); ?></p>
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
                 

                    <div class="back">
                        <button class="back-btn m-3 p-2 container-fluid rounded-pill text-light" onclick="window.location.href='../../user/views/index.php'">Back to home</button>
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
                                <form method="POST">
                                    <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation['id']); ?>">
                                    <button type="submit" name="cancel_reservation" class="btn btn-danger">Yes, Cancel Reservation</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    // Event listener for star click
    $('.star-rating i').on('click', function () {
        var rating = $(this).data('rate'); // Get the rating value from clicked star
        var itemId = $(this).closest('.star-rating').data('item-id'); // Get item ID

        // Update the star UI (change filled/unfilled stars)
        $(this).parent().children('i').removeClass('fa-solid').addClass('fa-regular');
        $(this).prevAll().addClass('fa-solid'); // Add solid class for all previous stars

        // Send rating to server via AJAX
        $.ajax({
            type: 'POST',
            url: 'reservation-track.php', // Path to the PHP file handling the request
            data: { item_id: itemId, rating: rating },
            success: function(response) {
                alert('Rating updated successfully!');
            },
            error: function() {
                alert('Error updating rating.');
            }
        });
    });
});
</script>