<?php
session_start();
include './../../connection/connection.php';

// Get client information
$clientFullName = "";
$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id) {
    $clientQuery = $conn->prepare("SELECT CONCAT(firstname, ' ', lastname) AS full_name FROM client WHERE id = ?");
    $clientQuery->bind_param("i", $user_id);
    $clientQuery->execute();
    $result = $clientQuery->get_result();
    if ($row = $result->fetch_assoc()) {
        $clientFullName = $row['full_name'];
    }
    $clientQuery->close();
}

// Function to get available times
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
$times = getAvailableTimes($conn, $user_id, $selectedDate);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = (int)$_POST['client_id'];
    $party_size = (int)$_POST['party_size'];
    $reservation_date = $conn->real_escape_string($_POST['reservation_date']);
    $reservation_time_id = (int)$_POST['reservation_id'];
    $note = $conn->real_escape_string($_POST['note_area'] ?? '');
    $amount = 50;
    $transaction_code = 'RES-' . strtoupper(uniqid());

    try {
        // Validate required fields
        if (empty($client_id) || empty($reservation_date) || empty($reservation_time_id)) {
            throw new Exception("Missing required fields");
        }

        // Get the time slot
        $timeQuery = $conn->prepare("SELECT id, time FROM res_time WHERE id = ?");
        $timeQuery->bind_param("i", $reservation_time_id);
        $timeQuery->execute();
        $timeResult = $timeQuery->get_result();
        
        if ($timeResult->num_rows === 0) {
            throw new Exception("Invalid time slot selected");
        }
        
        $timeData = $timeResult->fetch_assoc();
        $time_slot = $timeData['time'];
        
        // Insert reservation
        $insertQuery = $conn->prepare("
            INSERT INTO reservation (
                transaction_code,
                client_id,
                clientFullName,
                reservation_date,
                reservation_time,
                reservation_time_id,
                party_size,
                note_area,
                amount,
                res_status,
                date_created
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'for confirmation', NOW())
        ");
        
        $insertQuery->bind_param(
            "sisssiisi",
            $transaction_code,
            $client_id,
            $clientFullName,
            $reservation_date,
            $time_slot,
            $reservation_time_id,
            $party_size,
            $note,
            $amount
        );
        
        if ($insertQuery->execute()) {
            $new_id = $conn->insert_id;
            header("Location: reservation_track.php?reservation_id=" . $new_id);
            exit();
        } else {
            throw new Exception("Insert failed: " . $insertQuery->error);
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../asset/css/resrvation.css">
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
        .confirmation-icon {
            font-size: 60px;
            color: #f39c12;
            margin-bottom: 20px;
        }
        .confirmation-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
    </style>
</head>
<body class="reservation">
    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="confirmation-icon">?</div>
                    <h4>Confirm Reservation</h4>
                    <p>Are you sure you want to reserve your table?</p>
                    <div class="confirmation-buttons">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirmReservation">Yes, Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="res-body">
        <div class="reservation-left">
            <div class="left-header">
                <h1 id="title">Reservation</h1>
            </div>
            <div class="calendar">
                <iframe src="../inc/calendar2.php"></iframe>
            </div>
        </div>

        <div class="reservation-right">
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="seat-reservation">
                <h4 class="cart-right-header1 px-4">Seat Reservation</h4>
                <div class="reservation-color">
                    <p class="color-coding text-light p-2" style="background-color:#07D090;">Available</p>
                    <p class="color-coding text-light p-2" style="background-color:#E60000;">Fully Booked</p>
                    <p class="color-coding text-light p-2" style="background-color:#9647FF;">Your Reservation</p>
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
    
            <div class="party-size" id="party-size">
                <small>Party Size</small>
                <div class="input-group scale-size">
                    <button class="btn btn-outline-secondary" type="button" id="button-minus">-</button>
                    <input type="number" class="form-control" value="1" id="number-input" min="1" max="20">
                    <button class="btn btn-outline-secondary" type="button" id="button-plus">+</button>
                </div>
            </div>

            <div class="order-summary-container container-fluid px-4">
                <h1 class="cart-right-header1">Reservation Summary</h1>
                <div class="order-summary-info">
                    <form action="" method="POST" id="reservation-form">
                        <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($user_id); ?>">

                        <div class="reservation-info" id="reservation-info-name">
                            <p class="name">Name</p> 
                            <p class="result-sum name-result"><?php echo htmlspecialchars($clientFullName) ?></p>
                        </div>

                        <div class="reservation-info" id="reservation-info-party-size">
                            <p class="party-size-info">Party Size</p>
                            <p class="result-sum party-size-result">1</p>
                            <input type="hidden" name="party_size" id="party_size_input" value="1">
                        </div>

                        <div class="reservation-info" id="reservation-info-date">
                            <p class="date">Date</p>
                            <p id="date-result" class="result-sum date-result">Not selected</p>
                            <input type="hidden" name="reservation_date" id="reservation_date_input">
                        </div>

                        <div class="reservation-info" id="reservation-info-time">
                            <p class="time">Time</p>
                            <p id="time-result" class="result-sum time-result">Not selected</p>
                            <input type="hidden" name="reservation_id" id="reservation_time_input">
                        </div>

                        <div class="reservation-info" id="reservation-info-reservation-fee">
                            <p class="reservation-fee">Reservation Fee</p>
                            <p class="result-sum reservation-fee-result">₱ 50</p>
                            <input type="hidden" name="amount" value="50">
                        </div>

                        <div class="con-reser mb-3">
                            <div class="note my-2 container-fluid">
                                <label for="user-note">Notes</label>
                                <textarea name="note_area" maxlength="500" class="container-fluid" 
                                          style="border-color: #B3B3B3; border-radius: 10px; height: 150px; resize: none;" 
                                          placeholder="Additional notes..."></textarea>
                            </div>
                            <button type="button" class="container-fluid btn btn-primary" id="confirm-order">Confirm Reservation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize variables
        const today = new Date().toISOString().split('T')[0];
        $('#date-picker').val(today);
        
        // Party size controls
        $('#button-plus').click(function() {
            const $input = $('#number-input');
            let val = parseInt($input.val());
            if (val < 20) $input.val(val + 1);
            updatePartySize();
        });
        
        $('#button-minus').click(function() {
            const $input = $('#number-input');
            let val = parseInt($input.val());
            if (val > 1) $input.val(val - 1);
            updatePartySize();
        });
        
        $('#number-input').change(function() {
            let val = parseInt(this.value);
            if (isNaN(val) || val < 1) val = 1;
            if (val > 20) val = 20;
            this.value = val;
            updatePartySize();
        });
        
        function updatePartySize() {
            const val = $('#number-input').val();
            $('.party-size-result').text(val);
            $('#party_size_input').val(val);
        }
        
        // Time button selection
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
        
        // Calendar iframe communication
        window.addEventListener('message', function(event) {
            if (event.data && event.data.selectedDate) {
                const selectedDate = event.data.selectedDate;
                $('#reservation_date_input').val(selectedDate);
                
                const today = new Date().toISOString().split('T')[0];
                
                if (selectedDate === today) {
                    $('#date-result').text("Select another date");
                    $('#reservation_date_input').val("");
                } else {
                    $('#date-result').text(selectedDate);
                    $('#time-result').text('Not selected');
                    $('#reservation_time_input').val("");
                    $('.available-time-btn').removeClass('selected-time');
                }
                
                // Fetch updated time slots
                $.get(`../user/res.php?date=${selectedDate}&user_id=<?php echo $user_id; ?>`, function(data) {
                    data.status_reservations.forEach((status, index) => {
                        const btn = $(`#time-btn-${index}`);
                        let color = '#07D090';
                        let disabled = false;
                        
                        if (status.client_id == <?php echo $user_id; ?>) {
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
        
        // Form validation and submission
        $('#confirm-order').click(function() {
            let isValid = true;
            
            if (!$('#reservation_date_input').val()) {
                alert('Please select a date');
                isValid = false;
            }
            
            if (!$('#reservation_time_input').val()) {
                $('#time-error').show();
                isValid = false;
            }
            
            if (isValid) {
                $('#confirmationModal').modal('show');
            }
        });
        
        $('#confirmReservation').click(function() {
            $('#reservation-form').submit();
        });
    });
    </script>
</body>
</html>