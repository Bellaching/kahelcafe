<?php
session_start();
include './../../connection/connection.php';    


date_default_timezone_set("Asia/Manila");
$order_date = date("Y-m-d H:i:s");

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    die("Cart is empty.");
}

if (!isset($_POST['reservation_date'], $_POST['reservation_time'], $_POST['party_size'])) {
    die("Missing reservation details.");
}

$reservation_date = $_POST['reservation_date'];
$reservation_time = $_POST['reservation_time'];
$party_size = $_POST['party_size'];

$userId = $_SESSION['user_id'];
$userNote = isset($_POST['note']) ? $_POST['note'] : "";
$total_price = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_price += $item['price'] * $item['quantity'];
}

$stmt_order = $conn->prepare("INSERT INTO orders (user_id, order_date, total_price, reservation_date, reservation_time, party_size) VALUES (?, ?, ?, ?, ?, ?)");
$stmt_order->bind_param("issssi", $userId, $order_date, $total_price, $reservation_date, $reservation_time, $party_size);
if (!$stmt_order->execute()) {
    die("Order Insert Failed: " . $stmt_order->error);
}
$orderId = $stmt_order->insert_id;
$stmt_order->close();

$stmt_items = $conn->prepare("INSERT INTO order_items (order_id, item_name, size, temperature, quantity, note, price, reservation_date, reservation_time, party_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
if ($stmt_items === false) {
    die("Prepare failed: " . $conn->error);
}

foreach ($_SESSION['cart'] as $item) {
    $stmt_items->bind_param("isssisssii", $orderId, $item['name'], $item['size'], $item['temperature'], $item['quantity'], $userNote, $item['price'], $reservation_date, $reservation_time, $party_size);
    if (!$stmt_items->execute()) {
        die("Execute failed: " . $stmt_items->error);
    }
}
$stmt_items->close();

unset($_SESSION['cart']);
echo "Order placed successfully!";
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<div class="col-lg-4">
   
<div class="card ">

        <div class="calendar2 w-100">
    <div class="ratio" style="--bs-aspect-ratio: 65%;">
        <iframe src="../inc/calendar2.php" name="reservation_date" class="border-0"></iframe>
    </div>
</div>
<div class="Available-Time pb-2">
    <h4 class="cart-right-header1">Available Time</h4>
    <p id="date-picker" class="form-control d-none"></p>

    <div class="Available-Time-show d-grid gap-2" id="Available-Time-show" style="grid-template-columns: repeat(2, 1fr);">
        <?php foreach ($times as $i => $time): ?>
          

            <button class="available-time-btn btn rounded-pill text-white p-2 small" name="reservation_time" id="time-btn-<?= $i ?>" data-time-id="<?= $time['time_id'] ?>" style="background-color: #07D090;" style="background-color: #07D090;">
                        <?= $time['time'] ?>
                    </button>
        <?php endforeach; ?>
    </div>
</div>
<div class="party-size d-flex align-items-center gap-2" id="party-size">
    <strong class="fs-6 text-dark">Party Size</strong>
    <div class="input-group scale-size ms-auto w-auto">
        <button class="btn btn-outline-secondary" type="button" id="button-minus">-</button>
        <input type="number" name="party_size" class="form-control text-center" value="1" id="number-input">

        <button class="btn btn-outline-secondary" type="button" id="button-plus">+</button>
    </div>
</div>
<p class="d-flex justify-content-between">
    <strong>Reservation fee:</strong>
    <span>P 50</span>
</p>

<p class="d-flex justify-content-between">
    <strong>Date:</strong>
    <p id="date-result" class="result-sum date-result">0</p>
    <input type="hidden" name="reservation_date" id="reservation_date_input">
</p>

<p class="d-flex justify-content-between">
    <strong>Time:</strong>
    <p class="result-sum time-result" id="time-result">Select time</p>
    <input type="hidden" name="reservation_time" id="reservation_time_input">
</p>

            <div class="text-end">
            <button type="button" class="btn proceedBtn text-light text-center container-fluid bold-1" data-bs-toggle="modal" data-bs-target="#checkoutModal">
    Confirm Order
</button>
            </div>
        </div>
    </div>
</div>
        </div>
  
</div>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
        fetchReservationStatus(selectedDate);
    }
});
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.available-time-btn');

    buttons.forEach((button) => {
        button.addEventListener('click', function () {
            if (button.style.backgroundColor === 'rgb(7, 208, 144)') { // RGB value for #07D090
                const selectedTime = button.innerText; // Get the clicked button's time
                document.getElementById('time-result').innerText = selectedTime; // Update time result
                document.getElementById('reservation_time_input').value = button.dataset.timeId; // Set hidden input value
            }
        });
    });
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
                                buttons[index].style.backgroundColor = color;
                                buttons[index].disabled = isDisabled;
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
                document.addEventListener('DOMContentLoaded', function() {
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('date-picker').value = today;
                    fetchReservationStatus(today);
                });
                document.addEventListener('DOMContentLoaded', function() {
                    const numberInput = document.getElementById("number-input");
                    const partySizeResult = document.getElementById("party-size-result");
                    numberInput.value = 1;
                    partySizeResult.textContent = numberInput.value;
                    document.getElementById('party-size-result').textContent = document.getElementById('number-input').value;
                });
                document.getElementById("button-plus").addEventListener("click", function() {
                    const numberInput = document.getElementById("number-input");
                    const partySizeResult = document.getElementById("party-size-result");
                    document.getElementById("party_size_input").value=partySizeResult;
                    numberInput.value = parseInt(numberInput.value) + 1;
                    partySizeResult.textContent = numberInput.value;
                });
                document.getElementById("button-minus").addEventListener("click", function() {
                    const numberInput = document.getElementById("number-input");
                    const partySizeResult = document.getElementById("party-size-result");
                    if (parseInt(numberInput.value) > 1) {
                        numberInput.value = parseInt(numberInput.value) - 1;
                    }        partySizeResult.textContent = numberInput.value;
                });
    </script>
<script>
$(document).ready(function() {
    
    $("#checkoutBtn").click(function () {
        const note = $("#user-note").val();
        const reservationType = $("#reservation-type").val();

        $.ajax({
    url: '',
    type: 'POST',
    data: { checkout: true, note: note, reservation_type: reservationType },

            success: function (response) {
                const result = JSON.parse(response);
                if (result.success) {
                    alert("Order placed successfully! Transaction ID: " + result.redirect.split("transaction_id=")[1]);
                    window.location.href = result.redirect;
                } else {
                    // This will show the alert for pending orders
                    alert(result.message);
                }
            }
        });
    });

 
});
</script>


</body>
</html>
