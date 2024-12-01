<?php
include './../inc/topNav.php'; 

include './../../connection/connection.php';


$user_id = 25;  // Use the actual user_id dynamically if necessary

// Fetch available times from the res_time table
$times = [];
$time_query = "SELECT * FROM res_time";
$time_result = mysqli_query($conn, $time_query);

if ($time_result) {
    while ($row = mysqli_fetch_assoc($time_result)) {
        $times[] = [
            'time_id' => $row['id'],
            'time' => $row['time']
        ];
    }
}

// Fetch the reservation fee from the menu table
$reservation_fee = 0;
$reservation_fee_query = "SELECT price FROM menu1 WHERE name = 'Reservation'";
$reservation_fee_result = mysqli_query($conn, $reservation_fee_query);

if ($reservation_fee_result) {
    $row = mysqli_fetch_assoc($reservation_fee_result);
    $reservation_fee = $row['price'];  // Store the reservation fee
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the data from the POST request
    $client_id = $_POST['client_id'];
    $transaction_code = uniqid('TXN-', true); // Generate a unique transaction code
    $reservation_date = $_POST['reservation_date'];
    $reservation_time = $_POST['reservation_time'];
    $party_size = $_POST['party-size-result'];
    $note = $_POST['note-area'];
    $amount = $reservation_fee;
    $res_status = "for payment"; // Default value

    // Insert the reservation data into the database
    $insert_query = "INSERT INTO reservation (transaction_code, client_id, reservation_date, reservation_time, party_size, note, amount, res_status) 
                     VALUES ('$transaction_code', '$client_id', '$reservation_date', '$reservation_time', '$party_size', '$note', '$amount', '$res_status')";

    if (mysqli_query($conn, $insert_query)) {
        echo "<script>alert('Reservation successfully created!'); window.location.href='reservation-confirmation.php';</script>";
    } else {
        echo "<script>alert('Error creating reservation: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../asset/css/resrvation.css">
 
   
</head>
<body class="reservation">
    <div class="res-body">
        <div class="reservation-left">
            <div class="left-header">
                <h1 id="title">Reservation</h1>
            </div>

            <div class="calendar">
                <iframe src="../inc/calendar.php"></iframe>
            </div>

            <div class="note">
                <h5 class="header-h1-note">Note</h5>
                <textarea name="note-area" id="note-area" maxlength="100%"></textarea>
            </div>
        </div>

        <div class="reservation-right">
            <div class="seat-reservation">
                <h4 class="cart-right-header1">Seat Reservation</h4>

                <div class="reservation-color">
                    <p class="color-coding" style="background-color:#07D090;">Available</p>
                    <p class="color-coding" style="background-color:red;">Fully Booked</p>
                    <p class="color-coding" style="background-color:purple;">Your Reservation</p>
                </div>
            </div>

              
            <div class="Available-Time">
                <h4 class="cart-right-header1">Available Time</h4>
                <!-- <input type="date" id="date-picker" class="form-control">    -->
                <p id="date-picker" class="form-control" style="display: none;"> </p>
                <div class="Available-Time-show" id="Available-Time-show">
                    <?php
                    foreach ($times as $i => $time) {
                        // Initially setting the button to available color
                        echo "<button class='available-time-btn' id='time-btn-$i' data-time-id='{$time['time_id']}' style='background-color: #07D090;'>{$time['time']}</button>";
                    }
                    ?>
                </div>
            </div>
    
            <div class="party-size" id="party-size">
                <small>Party Size</small>
                <div class="input-group scale-size">
                    <button class="btn btn-outline-secondary" type="button" id="button-minus">-</button>
                    <input type="number" class="form-control" value="1" id="number-input" min="0">
                    <button class="btn btn-outline-secondary" type="button" id="button-plus">+</button>
                </div>
            </div>


            <div class="order-summary-container">
    <h1 class="cart-right-header1">Order Summary</h1>

    <div class="order-summary-info">
        <form action="" method="POST">
            <!-- Client ID (hidden) -->
            <input type="hidden" name="client_id" value="<?php echo $user_id; ?>">

            <div class="reservation-info" id="reservation-info-name">
                <p class="name" id="name">Name</p>
                <p class="result-sum name-result" id="name-result">Name Result</p>
            </div>

            <div class="reservation-info" id="reservation-info-transaction-no">
                <p class="transaction-no" id="transaction-no">Transaction Code</p>
                <p class="result-sum transaction-no-result" id="transaction-no-result">Confirm reservation to generate</p>
            </div>

            <div class="reservation-info" id="reservation-info-party-size">
                <p class="party-size-info" id="party-size">Party Size</p>
                <p class="result-sum party-size-result" id="party-size-result"></p>
                <input type="hidden" name="party_size" id="party_size_input">
            </div>

            <div class="reservation-info" id="reservation-info-date">
                <p class="date" id="date">Date</p>
                <p id="date-result" class="result-sum date-result">0</p>
                <input type="hidden" name="reservation_date" id="reservation_date_input">
            </div>

            <div class="reservation-info" id="reservation-info-time">
                <p class="time" id="time">Time</p>
                <p class="result-sum time-result" id="time-result">Select time</p>
                <input type="hidden" name="reservation_time" id="reservation_time_input">
            </div>

            <div class="reservation-info" id="reservation-info-reservation-fee">
                <p class="reservation-fee" id="reservation-fee">Reservation Fee</p>
                <p class="result-sum reservation-fee-result" id="reservation-fee-result">
                    <?php echo '₱' . number_format($reservation_fee, 2); ?>
                </p>
                <input type="hidden" name="amount" value="<?php echo '₱' . number_format($reservation_fee, 2); ?>">
            </div>

            <button type="submit" id="confirm-order">Confirm Order</button>
        </form>


                    
                </div>
                </div>
       
    </div>

    <script>
                // Listener to handle date selection from the calendar iframe
                window.addEventListener('message', function(event) {
                        if (event.data && event.data.selectedDate) {
                            const selectedDate = event.data.selectedDate;
                            document.getElementById('date-picker').textContent = selectedDate;
                            
                            const dateParts = selectedDate.split('-');
                            if (dateParts.length === 3) {
                                const dateObject = new Date(Date.UTC(dateParts[0], dateParts[1] - 1, dateParts[2]));
                                const formattedDate = dateObject.toISOString().split('T')[0];  // Format as yyyy-mm-dd
                                document.getElementById('date-picker').value = formattedDate;
                            }

                            const resultElement = document.getElementById("date-result");
                            document.getElementById('date-result').value=resultElement;

                            const today = new Date();
                            const todayFormatted = today.toISOString().split('T')[0];

                            if (selectedDate === todayFormatted) {
                                resultElement.textContent = "Select another date";
                            } else {
                                resultElement.textContent = `${selectedDate}`;
                                document.getElementById('time-result').innerText = 'Select Time';  // Clear time result
                            }

                            //fetch selected date
                            fetchReservationStatus(selectedDate);
                        }
                    });

   // Attach click listeners only once when the DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.available-time-btn');

    buttons.forEach((button) => {
        button.addEventListener('click', function () {
            // Ensure only clickable (green) buttons respond
            if (button.style.backgroundColor === 'rgb(7, 208, 144)') { // RGB value for #07D090
                const selectedTime = button.innerText; // Get the clicked button's time
                document.getElementById('time-result').innerText = selectedTime; // Update time result
            }
        });
    });

    // Initial fetch when page loads (today's date)
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('date-picker').value = today;
    fetchReservationStatus(today);
});

// Fetch reservation status based on selected date
function fetchReservationStatus(date) {
    fetch(`../user/res.php?date=${date}`)
        .then(response => response.json())
        .then(data => {
            const buttons = document.querySelectorAll('.available-time-btn');

            data.status_reservations.forEach((status, index) => {
                const button = buttons[index];
                let color = '#07D090'; // Default to available
                let isDisabled = false;

                if (status.client_id == <?php echo json_encode($user_id); ?>) {
                    color = 'purple';
                    isDisabled = true;
                } else if (status.status === 'booked') {
                    color = 'red';
                    isDisabled = true;
                }

                button.style.backgroundColor = color;
                button.disabled = isDisabled;
            });
        })
        .catch(error => console.error('Error fetching data:', error));
}


                // Initial fetch when page loads (for today's date)
                document.addEventListener('DOMContentLoaded', function() {
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('date-picker').value = today;
                    fetchReservationStatus(today);
                });

                // Initial setup when the page loads
                document.addEventListener('DOMContentLoaded', function() {
                    const numberInput = document.getElementById("number-input");
                    const partySizeResult = document.getElementById("party-size-result");

                    // Set initial value of the number input to 1
                    numberInput.value = 1;

                    // Update the party size result
                    partySizeResult.textContent = numberInput.value;
                });

                // Update the party size result when the number input changes
                document.getElementById("button-plus").addEventListener("click", function() {
                    const numberInput = document.getElementById("number-input");
                    const partySizeResult = document.getElementById("party-size-result");

                    // Increment the value
                    numberInput.value = parseInt(numberInput.value) + 1;

                    // Update the result text
                    partySizeResult.textContent = numberInput.value;
                });

                document.getElementById("button-minus").addEventListener("click", function() {
                    const numberInput = document.getElementById("number-input");
                    const partySizeResult = document.getElementById("party-size-result");

                    // Decrement the value, but prevent going below 1
                    if (parseInt(numberInput.value) > 1) {
                        numberInput.value = parseInt(numberInput.value) - 1;
                    }

                    // Update the result text
                    partySizeResult.textContent = numberInput.value;
                });

//                 document.getElementById('confirm-order').addEventListener('click', () => {
//     // Gather data from the order summary
//     const data = {
//         transaction_code: document.getElementById('transactionCode').innerText.trim(),
//         client_id: document.getElementById('clientId').innerText.trim(),
//         reservation_date: document.getElementById('reservation_date_input').value,
//             reservation_time: document.getElementById('reservation_time_input').value,
//             party_size: document.getElementById('party-size-result').innerText.trim(),
//             note: document.getElementById('note-area').value.trim(),
//         amount: document.getElementById('amount').innerText.trim(),
//         res_status: "for payment"
//     };

   
// });



                

    </script>
</body>
</html>
