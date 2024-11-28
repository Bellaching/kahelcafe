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
$reservation_fee_query = "SELECT price FROM menu WHERE name = 'reservation fee'";
$reservation_fee_result = mysqli_query($conn, $reservation_fee_query);

if ($reservation_fee_result) {
    $row = mysqli_fetch_assoc($reservation_fee_result);
    $reservation_fee = $row['price'];  // Store the reservation fee
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
        <form action="../inc/addReservation.php" method="POST">
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

       

                // Fetch reservation status based on selected date
                function fetchReservationStatus(date) {
                    fetch(`../user/res.php?date=${date}`)
                        .then(response => response.json())
                        .then(data => {
                            const buttons = document.querySelectorAll('.available-time-btn');

                            data.status_reservations.forEach((status, index) => {
                                let color = '#07D090'; // Default color
                                let isDisabled = false;

                                // Check if the user has a reservation or the status is booked
                                if (status.client_id == <?php echo json_encode($user_id); ?>) {
                                    color = 'purple';
                                    isDisabled = true;
                                } else if (status.status === 'booked') {
                                    color = 'red'; 
                                    isDisabled = true; 
                                }

                                // Apply the color and disable status
                                buttons[index].style.backgroundColor = color;
                                buttons[index].disabled = isDisabled;

                                // Only allow click if the button is available (green)
                                if (color === '#07D090') {
                                    buttons[index].disabled = false;
                                }

                                buttons[index].addEventListener('click', function() {
                                    if (color === '#07D090' && !buttons[index].disabled) { 
                                        const selectedTime = buttons[index].innerText; // Get the time of the clicked button
                                        document.getElementById('time-result').innerText = `${selectedTime}`; // Display the time 
                                        
                                    }
                                });
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

                document.getElementById('confirm-order').addEventListener('click', function () {
    // Update hidden inputs with visible field values
    document.getElementById('party_size_input').value = document.getElementById('party-size-result').textContent;
    document.getElementById('reservation_date_input').value = document.getElementById('date-result').textContent;
    document.getElementById('reservation_time_input').value = document.getElementById('time-result').textContent;
});



                

    </script>
</body>
</html>
