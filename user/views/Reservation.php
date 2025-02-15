<?php
include './../inc/topNav.php'; 

include './../../connection/connection.php';
include '../views/reserve.php'

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../asset/css/resrvation.css">
 
   
</head>
<body class="reservation ">
    <div class="res-body">
        <div class="reservation-left">
            <div class="left-header">
                <h1 id="title">Reservation</h1>
            </div>

            <div class="calendar">
                <iframe src="../inc/calendar2.php"></iframe>
            </div>

         
        </div>

        <div class="reservation-right  ">
            <div class="seat-reservation">
                <h4 class="cart-right-header1 px-4">Seat Reservation</h4>

                <div class="reservation-color ">
                    <p class="color-coding text-light p-2" style="background-color:#07D090;">Available</p>
                    <p class="color-coding text-light p-2" style="background-color:#E60000;">Fully Booked</p>
                    <p class="color-coding text-light p-2" style="background-color:#9647FF;">Your Reservation</p>
                </div>
            </div>

              
            <div class="Available-Time">
                <h4 class="cart-right-header1">Available Time</h4>
                <!-- <input type="date" id="date-picker" class="form-control">    -->
                <p id="date-picker" class="form-control" style="display: none;"> </p>
                <div class="Available-Time-show" id="Available-Time-show">
                <?php foreach ($times as $i => $time): ?>
                    <button class="available-time-btn" id="time-btn-<?= $i ?>" data-time-id="<?= $time['time_id'] ?>" style="background-color: #07D090;">
                        <?= $time['time'] ?>
                    </button>
                <?php endforeach; ?>
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


            <div class="order-summary-container container-fluid px-4">
    <h1 class="cart-right-header1">Reservation Summary</h1>

    <div class="order-summary-info">
        <form action="" method="POST">
            <!-- Client ID (hidden) -->
            <input type="hidden" name="client_id" value="<?php echo $user_id; ?>">

            <div class="reservation-info" id="reservation-info-name">
                <p class="name" id="name">Name</p> 
                <p class="result-sum name-result" id="name-result"><?php echo $clientFullName; ?></p>
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

          

                  <div class="con-reser mb-3">

                  <div class="note my-2 container-fluid">
                  <label for="user-note">Notes</label>
          <textarea name="note_area" id="" maxlength="500" class="container-fluid " style="border-color: #B3B3B3; border-radius: 10px; height: 150px; resize: none;" placeholder="Additional notes..."></textarea>
         
      
      
                  </div>
                  <button type="submit" class="container-fluid" id="confirm-order" class="mb-3" >Confirm Order</button>
                  </div>

       
        </form>


                    
                </div>
                </div>
       
    </div>

    <script>
              // Listener to handle date selection from the calendar iframe
window.addEventListener('message', function(event) {
    if (event.data && event.data.selectedDate) {
        const selectedDate = event.data.selectedDate;
        
        // Set the selected date in the hidden input field
        document.getElementById('reservation_date_input').value = selectedDate; // Pass the date to the hidden input

        // Update the date result in the UI
        const resultElement = document.getElementById("date-result");
        const today = new Date();
        const todayFormatted = today.toISOString().split('T')[0];  // Format as yyyy-mm-dd

        if (selectedDate === todayFormatted) {
            resultElement.textContent = "Select another date";
        } else {
            resultElement.textContent = selectedDate;
            document.getElementById('time-result').innerText = 'Select Time';  // Clear time result
        }

        // Fetch reservation status
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
                document.getElementById('reservation_time_input').value = button.dataset.timeId; // Set hidden input value
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
                    document.getElementById('party-size-result').textContent = document.getElementById('number-input').value;
                    
                    
                });

                // Update the party size result when the number input changes
                document.getElementById("button-plus").addEventListener("click", function() {
                    const numberInput = document.getElementById("number-input");
                    const partySizeResult = document.getElementById("party-size-result");
                    document.getElementById("party_size_input").value=partySizeResult;

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
