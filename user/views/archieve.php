<?php
include './../inc/topNav.php'; 
include './../../connection/connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$user_id = $_SESSION['user_id'];

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);
$reservation_date = isset($data['reservation_date']) ? mysqli_real_escape_string($conn, $data['reservation_date']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reservation_date) {
    // Step 1: Insert into the orders table first
    $insert_order_query = "INSERT INTO orders (user_id, created_at) VALUES ('$user_id', NOW())";

    if (mysqli_query($conn, $insert_order_query)) {
        // Get the last inserted order_id
        $order_id = mysqli_insert_id($conn);

        // Step 2: Insert into Order_Items with the valid order_id
        $insert_item_query = "INSERT INTO Order_Items (order_id, reservation_date) VALUES ('$order_id', '$reservation_date')";

        if (mysqli_query($conn, $insert_item_query)) {
            echo "Reservation successfully created!";
        } else {
            echo "Error creating reservation: " . mysqli_error($conn);
        }
    } else {
        echo "Error creating order: " . mysqli_error($conn);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="reservation-container">
        <h1>Reservation</h1>
        <div class="calendar">
            <iframe src="../inc/calendar2.php"></iframe>
        </div>
        <input type="hidden" id="reservation_date_input">
        <p id="date-result">Select a date</p>
        <button type="button" class="btn btn-primary" id="confirm-order">Confirm Order</button>
    </div>

    <script>
        window.addEventListener('message', function(event) {
            if (event.data && event.data.selectedDate) {
                const selectedDate = event.data.selectedDate;
                document.getElementById('reservation_date_input').value = selectedDate;
                document.getElementById('date-result').textContent = selectedDate;
            }
        });

        $(document).ready(function() {
            $('#confirm-order').click(function() {
                let reservationDate = $('#reservation_date_input').val();
                if (!reservationDate) {
                    alert('Please select a date.');
                    return;
                }
                
                $.ajax({
                    url: '',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ reservation_date: reservationDate }),
                    success: function(response) {
                        alert(response);
                        window.location.href = 'reservation.php';
                    },
                    error: function() {
                        alert('Error processing reservation.');
                    }
                });
            });
        });
    </script>
</body>
</html>