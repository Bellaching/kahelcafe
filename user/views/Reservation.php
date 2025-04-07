<?php
// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

ob_start();
include './../inc/topNav.php'; 
include './../../connection/connection.php';

// First, verify the reservation_time table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'resservation_time'");
if ($tableCheck->num_rows == 0) {
    die("Error: The reservation_time table does not exist in the database.");
}

$clientFullName = "";
$user_id = $_SESSION['user_id'] ?? 0;
$isLoggedIn = isset($_SESSION['user_id']);

if ($isLoggedIn) {
    $clientQuery = $conn->prepare("SELECT CONCAT(firstname, ' ', lastname) AS full_name FROM client WHERE id = ?");
    $clientQuery->bind_param("i", $user_id);
    $clientQuery->execute();
    $result = $clientQuery->get_result();
    if ($row = $result->fetch_assoc()) {
        $clientFullName = $row['full_name'];
    }
    $clientQuery->close();
}

function getAvailableTimes($conn, $user_id, $date = null) {
    $times = [];
    $timeQuery = $conn->query("SELECT id, time FROM res_time ORDER BY time");
    while ($row = $timeQuery->fetch_assoc()) {
        $times[] = $row;
    }
    
    if ($date) {
        foreach ($times as &$time) {
            $checkQuery = $conn->prepare("
                SELECT r.id, r.res_status, r.client_id, rt.time
                FROM reservation r
                JOIN res_time rt ON r.reservation_time_id = rt.id
                WHERE r.reservation_date = ? 
                AND r.reservation_time_id = ?
            ");
            $checkQuery->bind_param("si", $date, $time['id']);
            $checkQuery->execute();
            $result = $checkQuery->get_result();
            
            if ($result->num_rows > 0) {
                $res_status = $result->fetch_assoc();
                if ($res_status['client_id'] == $user_id) {
                    $time['res_status'] = 'your_reservation';
                    $time['disabled'] = true;
                } elseif ($res_status['res_status'] == 'booked') {
                    $time['res_status'] = 'booked';
                    $time['disabled'] = true;
                }
            } else {
                $time['res_status'] = 'available';
                $time['disabled'] = false;
            }
        }
    }
    
    return $times;
}

$selectedDate = $_POST['reservation_date'] ?? '';
$times = getAvailableTimes($conn, $user_id, $selectedDate);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Double check user is logged in
    if (!$isLoggedIn) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'You must be logged in to make a reservation']);
        exit();
    }

    $client_id = (int)$_POST['client_id'];
    $party_size = (int)$_POST['party_size'];
    $reservation_date = $conn->real_escape_string($_POST['reservation_date']);
    $reservation_time_id = (int)$_POST['reservation_id'];
    $note = $conn->real_escape_string($_POST['note_area'] ?? '');
    $amount = 50;
    $transaction_code = 'RES-' . strtoupper(uniqid());

    try {
        if (empty($client_id) || empty($reservation_date) || empty($reservation_time_id)) {
            throw new Exception("Missing required fields");
        }

        // Verify the client_id matches the logged in user
        if ($client_id !== $user_id) {
            throw new Exception("Invalid user credentials");
        }

        // Get time slot details
        $timeQuery = $conn->prepare("SELECT id, time FROM res_time WHERE id = ?");
        $timeQuery->bind_param("i", $reservation_time_id);
        $timeQuery->execute();
        $timeResult = $timeQuery->get_result();
        
        if ($timeResult->num_rows === 0) {
            throw new Exception("Invalid time slot selected");
        }
        
        $timeData = $timeResult->fetch_assoc();
        $time_slot = $timeData['time'];

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("
                INSERT INTO reservation (
                    transaction_code,
                    client_id,
                    clientFullName,
                    reservation_date,
                    reservation_time_id,
                    reservation_time,
                    party_size,
                    note_area,
                    amount,
                    res_status,
                    date_created
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'for confirmation', NOW())
            ");
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param(
                "sissssisi",
                $transaction_code,
                $client_id,
                $clientFullName,
                $reservation_date,
                $reservation_time_id,
                $time_slot,
                $party_size,
                $note,
                $amount
            );

            if (!$stmt->execute()) {
                throw new Exception("Reservation failed: " . $stmt->error);
            }
            
            $new_reservation_id = $conn->insert_id;
            
            // Insert into reservation_time table
            $timeStmt = $conn->prepare("
                INSERT INTO resservation_time (
                    id,
                    reservation_time,
                    reservation_time_id
                ) VALUES (?, ?, ?)
            ");
            
            $timeStmt->bind_param(
                "isi",
                $new_reservation_id,
                $time_slot,
                $reservation_time_id
            );
            
            if (!$timeStmt->execute()) {
                throw new Exception("Failed to save time details: " . $timeStmt->error);
            }
            
            $conn->commit();
            
            ob_end_clean();
            
            echo json_encode([
                'status' => 'success',
                'reservation_id' => $new_reservation_id,
                'message' => 'Reservation successfully created'
            ]);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        error_log("RESERVATION ERROR: " . $e->getMessage());
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Reservation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        .order-h4 {
            color: #FF902A;
            font-weight: bold;
        }
    </style>
</head>
<body class="border-0">
    <!-- Login Required Modal -->
    <div class="modal fade" id="loginRequiredModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="confirmation-icon fs-1 text-warning mb-3">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h4>Login Required</h4>
                    <p>You need to log in first to make a reservation.</p>
                    <div class="d-flex justify-content-center gap-3 mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <a href="login.php" class="btn btn-primary">Go to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="confirmation-icon fs-1 text-warning mb-3">?</div>
                    <h4>Confirm Reservation</h4>
                    <p>Are you sure you want to reserve your table?</p>
                    <div class="d-flex justify-content-center gap-3 mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirmReservation">Yes, Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="success-icon fs-1 text-success mb-3">✓</div>
                    <h4>Reservation Confirmed!</h4>
                    <p class="success-message">Your reservation has been successfully created.</p>
                    <p class="redirect-message">You will be redirected to your reservation details in <span id="countdown">3</span> seconds...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid mt-3">
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card h-100 border-0">
                    <div class="card-body">
                        <h1 class="card-title text-center text-lg-start mb-4">Reservation</h1>
                        <div class="ratio ratio-16x9">
                            <iframe src="../inc/calendar2.php" class="border-0"></iframe>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 border-0">
                <div class="card h-100">
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                        <?php endif; ?>

                        <div class="card border-0">
                            <strong><h4 class="m-3 order-h4">Seat Reservation</h4></strong> 
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
                        </div>

                        <div class="mb-4">
                            <h4 class="mb-3 order-h4">Available Time</h4>
                            <p id="date-picker" class="d-none"></p>
                            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-3 g-2" id="Available-Time-show">
                                <?php foreach ($times as $i => $time): ?>
                                    <div class="col">
                                        <button type="button" class="available-time-btn w-100 btn btn-sm" 
                                            id="time-btn-<?= $i ?>" 
                                            data-time-id="<?= $time['id'] ?>"
                                            data-time-value="<?= htmlspecialchars($time['time']) ?>"
                                            style="background-color: #07D090;">
                                            <span class="text-truncate d-block text-light border-0"><?= htmlspecialchars($time['time']) ?></span>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div id="time-error" class="text-danger mt-2 d-none">Please select a time slot</div>
                        </div>
            
                        <div class="mb-4 d-flex align-items-center justify-content-between">
                            <label class="form-label mb-0">Party Size</label>
                            <div class="input-group" style="max-width: 150px;">
                                <button class="btn btn-outline-secondary" type="button" id="button-minus">-</button>
                                <input type="number" class="form-control text-center" value="1" id="number-input" min="1" max="20">
                                <button class="btn btn-outline-secondary" type="button" id="button-plus">+</button>
                            </div>
                        </div>

                        <div class="card border-0">
                            <div class="card-body">
                                <h4 class="mb-3 order-h4">Reservation Summary</h4>
                                <form action="" method="POST" id="reservation-form">
                                    <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($user_id); ?>">

                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-bold">Name</span>
                                        <span class="name-result"><?php echo htmlspecialchars($clientFullName) ?></span>
                                    </div>

                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-bold">Party Size</span>
                                        <span class="party-size-result">1</span>
                                        <input type="hidden" name="party_size" id="party_size_input" value="1">
                                    </div>

                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-bold">Date</span>
                                        <span class="date-result" id="date-result">Not selected</span>
                                        <input type="hidden" name="reservation_date" id="reservation_date_input">
                                    </div>

                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-bold">Time</span>
                                        <span class="time-result" id="time-result">Not selected</span>
                                        <input type="hidden" name="reservation_id" id="reservation_time_input">
                                    </div>

                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="fw-bold">Reservation Fee</span>
                                        <span class="reservation-fee-result">₱ 50</span>
                                        <input type="hidden" name="amount" value="50">
                                    </div>

                                    <div class="mb-3">
                                        <label for="user-note" class="form-label"><strong>Notes</strong></label>
                                        <textarea name="note_area" maxlength="500" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
                                    </div>
                                    <button type="button" class="btn proceedBtn text-light text-center container-fluid bold-1" style="background-color: #FF902A;" id="confirm-order">Confirm Reservation</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize variables
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date-picker').value = today;
            
            // Modal instances
            const loginRequiredModal = new bootstrap.Modal(document.getElementById('loginRequiredModal'));
            const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            
            // Form elements
            const reservationForm = document.getElementById("reservation-form");
            const numberInput = document.getElementById("number-input");
            const partySizeResult = document.querySelector(".party-size-result");
            const partySizeInput = document.getElementById("party_size_input");
            const dateResult = document.getElementById("date-result");
            const timeResult = document.getElementById("time-result");
            const reservationDateInput = document.getElementById("reservation_date_input");
            const reservationTimeInput = document.getElementById("reservation_time_input");
            const timeError = document.getElementById("time-error");
            const confirmOrderBtn = document.getElementById("confirm-order");
            
            // Set initial values
            numberInput.value = 1;
            partySizeResult.textContent = numberInput.value;
            partySizeInput.value = numberInput.value;
            
            // Check if user is logged in
            const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
            
            // Party size controls
            document.getElementById("button-plus").addEventListener("click", function() {
                if (parseInt(numberInput.value) < 20) {
                    numberInput.value = parseInt(numberInput.value) + 1;
                    updatePartySize();
                }
            });
            
            document.getElementById("button-minus").addEventListener("click", function() {
                if (parseInt(numberInput.value) > 1) {
                    numberInput.value = parseInt(numberInput.value) - 1;
                    updatePartySize();
                }
            });
            
            numberInput.addEventListener("change", function() {
                let value = parseInt(this.value);
                if (isNaN(value) || value < 1) value = 1;
                if (value > 20) value = 20;
                this.value = value;
                updatePartySize();
            });
            
            function updatePartySize() {
                partySizeResult.textContent = numberInput.value;
                partySizeInput.value = numberInput.value;
            }
            
            // Time button selection
            document.querySelectorAll('.available-time-btn').forEach(button => {
                button.addEventListener('click', function() {
                    // Remove selection from all buttons
                    document.querySelectorAll('.available-time-btn').forEach(btn => {
                        btn.classList.remove('border-dark', 'border-2');
                    });
                    
                    // Add selection to clicked button
                    this.classList.add('border-dark', 'border-2');
                    
                    // Get time data
                    const timeId = this.getAttribute('data-time-id');
                    const timeValue = this.getAttribute('data-time-value');
                    
                    // Update form fields
                    timeResult.textContent = timeValue;
                    reservationTimeInput.value = timeId;
                    timeError.classList.add('d-none');
                });
            });
            
            // Calendar iframe communication
            window.addEventListener('message', function(event) {
                if (event.data && event.data.selectedDate) {
                    const selectedDate = event.data.selectedDate;
                    reservationDateInput.value = selectedDate;
                    
                    const today = new Date();
                    const todayFormatted = today.toISOString().split('T')[0];
                    
                    if (selectedDate === todayFormatted) {
                        dateResult.textContent = "Select another date";
                        reservationDateInput.value = "";
                    } else {
                        dateResult.textContent = selectedDate;
                        timeResult.textContent = 'Not selected';
                        reservationTimeInput.value = "";
                        
                        // Clear time selection
                        document.querySelectorAll('.available-time-btn').forEach(btn => {
                            btn.classList.remove('border-dark', 'border-2');
                        });
                    }
                    
                    fetchReservationStatus(selectedDate);
                }
            });
            
            // Form validation and submission
            confirmOrderBtn.addEventListener('click', function() {
                let isValid = true;
                
                if (!reservationDateInput.value) {
                    alert('Please select a date');
                    isValid = false;
                }
                
                if (!reservationTimeInput.value) {
                    timeError.classList.remove('d-none');
                    isValid = false;
                }
                
                if (isValid) {
                    if (!isLoggedIn) {
                        // Show login required modal
                        loginRequiredModal.show();
                    } else {
                        // Show confirmation modal
                        confirmationModal.show();
                    }
                }
            });
            
            // Confirm reservation button in modal
            document.getElementById('confirmReservation').addEventListener('click', function() {
                confirmationModal.hide();
                
                // Submit form via AJAX
                const formData = new FormData(reservationForm);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Show success modal
                        successModal.show();
                        
                        // Start countdown
                        let seconds = 3;
                        const countdownElement = document.getElementById("countdown");
                        const countdown = setInterval(() => {
                            seconds--;
                            countdownElement.textContent = seconds;
                            if (seconds <= 0) {
                                clearInterval(countdown);
                                window.location.href = "reservation_track.php?reservation_id=" + data.reservation_id;
                            }
                        }, 1000);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing your reservation.');
                });
            });
            
            // Fetch reservation status for a date
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
                                } else if (res_status.res_status === 'booked') {
                                    color = '#E60000'; // Booked
                                    isDisabled = true;
                                }
                                
                                buttons[index].style.backgroundColor = color;
                                buttons[index].disabled = isDisabled;
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching reservation status:', error);
                    });
            }
        });
    </script>
</body>
</html>