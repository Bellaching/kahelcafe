<?php
session_start();

include './../../connection/connection.php';    


// Fetch cart from database if session expired
if (!isset($_SESSION['cart']) && isset($_SESSION['user_id'])) {
    $clientId = $_SESSION['user_id'];
    $query = "SELECT item_id, item_name, size, temperature, quantity, price , note FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error); // Debugging error
    }

    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $result = $stmt->get_result();

    $_SESSION['cart'] = [];
    while ($row = $result->fetch_assoc()) {
        $_SESSION['cart'][] = [
            'id' => $row['item_id'],
            'name' => $row['item_name'],
            'size' => $row['size'],
            'temperature' => $row['temperature'],
            'quantity' => $row['quantity'],
            'note' => $row['note'],
            'price' => $row['price'],
        ];
    }
    $stmt->close();
}

// Retrieve client details
$clientFullName = 'Unknown';
$clientId = $_SESSION['user_id'];


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
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    die("User not logged in.");
}

// Fetch the reservation fee from the Orders table
$reservation_fee = 0;
$reservation_fee_query = "SELECT reservation_fee FROM Orders WHERE name = 'Reservation'";
$reservation_fee_result = mysqli_query($conn, $reservation_fee_query);
if ($reservation_fee_result) {
    $row = mysqli_fetch_assoc($reservation_fee_result);
    $reservation_fee = $row['reservation_fee'];
}


// Fetch the reservation fee from the menu table
$reservation_fee = 0;
$reservation_fee_query = "SELECT reservation_fee FROM Orders WHERE name = 'Reservation'";
$reservation_fee_result = mysqli_query($conn, $reservation_fee_query);

if ($reservation_fee_result) {
    $row = mysqli_fetch_assoc($reservation_fee_result);
    $reservation_fee = $row['reservation_fee'];  // Store the reservation fee
}


// Handle quantity update (AJAX)
if (isset($_POST['update_quantity'])) {
    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);

    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $item_id) {
                $item['quantity'] = $quantity;
                break;
            }
        }
    }

    // Update database cart as well
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE item_id = ?");
    $stmt->bind_param("ii", $quantity, $item_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
    exit;
}

// Handle item removal
if (isset($_POST['remove_item'])) {
    $item_id = intval($_POST['item_id']);

    $stmt = $conn->prepare("DELETE FROM cart WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->close();

    // Remove from session cart
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $item_id) {
            unset($_SESSION['cart'][$key]);
            break;
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['cart_error'] = "You need to log in to add items to the cart.";
    header("Location: ./../../user/views/login.php");
    exit();
}

// Check if cart is empty
$cartEmpty = empty($_SESSION['cart']);

// Initialize totalPrice
$totalPrice = 0;
foreach ($_SESSION['cart'] as $item) {
    $totalPrice += $item['price'] * $item['quantity'];
}

// Retrieve client details
$clientFullName = 'Unknown';
$clientId = $_SESSION['user_id'];

$query = "SELECT firstname, lastname FROM client WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $clientId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $clientFullName = htmlspecialchars($row['firstname'] . ' ' . $row['lastname']);
}
$stmt->close();

// Handle checkout
if (isset($_POST['checkout'])) {
    $userNote = isset($_POST['note']) ? $_POST['note'] : '';

    $reservationType = isset($_POST['reservation_type']) ? $_POST['reservation_type'] : '';
    $transactionId = strtoupper(bin2hex(random_bytes(6)));
    $reservation_date =   isset($_POST['reservation_date']) ? $_POST['reservation_date'] : '';
$reservation_time = isset($_POST['reservation_time']) ? $_POST['reservation_time'] : ''; 
$party_size = isset($_POST['party_size']) ? $_POST['party_size'] : '';


    // Check for pending orders
    $pendingStatuses = ['for confirmation', 'payment', 'booked'];
    $query = "SELECT COUNT(*) FROM Orders WHERE user_id = ? AND status IN (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $clientId, ...$pendingStatuses);
    $stmt->execute();
    $stmt->bind_result($pendingOrderCount);
    $stmt->fetch();
    $stmt->close();

    if ($pendingOrderCount > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending order.']);
        exit;
    }

 
    $stmt = $conn->prepare("INSERT INTO Orders (user_id, client_full_name, total_price, transaction_id, reservation_type, status, reservation_fee) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $reservation_fee = 50; 
    $status = "for confirmation";
    $stmt->bind_param("isssssi", $clientId, $clientFullName, $totalPrice, $transactionId, $reservationType, $status, $reservation_fee);
  
    $stmt->execute();
    $orderId = $stmt->insert_id;
    $stmt->close();

    // Insert into Order_Items table
    foreach ($_SESSION['cart'] as $item) {
        $stmt_items = $conn->prepare("INSERT INTO Order_Items (order_id, item_name, size, temperature, quantity, note, price, reservation_date, reservation_time, party_size) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt_items->bind_param(  
            "isssisssii",  // Ensure 10 placeholders match
            $orderId, 
            $item['name'],  
            $item['size'], 
            $item['temperature'], 
            $item['quantity'], 
            $userNote, 
            $item['price'],
            $reservation_date, 
            $reservation_time, 
            $party_size
        );
        
        
        
        $stmt_items->execute();
        $stmt_items->close();
    }
    

    // Clear cart in session and database
    unset($_SESSION['cart']);
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $stmt->close();

    if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo json_encode(['success' => true, 'redirect' => "order-track.php?transaction_id=" . $transactionId]);
    } else {
        header("Location: order-track.php?transaction_id=" . $transactionId);
    }
    exit;
}


?>


<?php if (!empty($orderStatus)): ?>
    <div class="alert alert-info">
        <?php
        switch ($orderStatus) {
            case "for confirmation":
                echo "Your order is awaiting confirmation.";
                break;
            case "cancelled":
                echo "Your order has been cancelled.";
                break;
            case "payment":
                echo "Your payment is being processed.";
                break;
            case "booked":
                echo "Your order has been successfully booked.";
                break;
            case "rate us":
                echo "Thank you for your order! Please rate us.";
                break;
            default:
                echo "Status: " . $orderStatus;
        }
        ?>
    </div>
<?php endif; ?>


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
    <style>
      

        .cart-table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Light shadow */
        }

        .cart-table thead {
            background-color: #FF902A;
            color: white;
        }

        .quantity-buttons {
            display: flex;
            align-items: center;
        }

        .quantity-buttons .btn {
            border-radius: 50%;
            width: 30px;
            height: 30px;
        }

        .quantity-buttons .form-control {
            width: 50px;
            text-align: center;
        }

        .cart-item img {
            border-radius: 10px;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Light shadow */
        }

        /* Modal styling */
        .modal-content {
            border-radius: 10px;
        }

        .back-to-order a, i{
            color: #FF902B;
        }

        .alert-dismissible {
            display: none;
        }

        .order-underline {
    display: inline-block;
    border-bottom: 3px solid #FF902A;
    padding-bottom: 3px; 
    margin-bottom: 1rem;

}

.proceedBtn{
        background-color: #FF902A;
        border: none;
        border-radius: 15px;
    }

    .order-h4{
        color: #FF902A;
        font-weight: bold;
    }

    .alert {
    margin-top: 20px;
    padding: 15px;
    border-radius: 5px;
    font-size: 16px;
}

.re-op:hover{
    background-color: #FF902B;
    color: white;
}


    </style>
</head>
<body>
    
<div class="container-fluid con-cart p-4">
    <div class="back-to-order mb-5">
        <a href="order-now.php" class="text-decoration-none text-dark "><i class="fa-solid fa-arrow-left"></i>  <i class="fa-solid fa-mug-saucer"></i></a>
    </div>

    <h3>Your <span class="order-underline">Order</span></h3>




    <?php if ($cartEmpty): ?>
        <p>Your cart is empty. <a href="order-now.php">Start shopping</a></p>
    <?php else: ?>
        <div class="row">
            <!-- Cart Table Section -->
            <div class="col-lg-8">
    <table class="table cart-table table-striped table-responsive">
        <thead class="thead">
            <tr>
                <th>Item</th>
                <th>Price</th>
                <th>Size</th>
                <th>Temperature</th>
                <th>Quantity</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($_SESSION['cart'] as $item): ?>
            <tr class="cart-item" data-id="<?php echo $item['id']; ?>">
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                <td><?php echo isset($item['size']) ? $item['size'] : ''; ?></td>
                <td><?php echo isset($item['temperature']) ? $item['temperature'] : ''; ?></td>
                <td>
                    <div class="input-group quantity-buttons">
                        <button class="btn btn-secondary btn-sm btn-decrease" data-id="<?php echo $item['id']; ?>">-</button>
                        <input type="text" class="form-control quantity-input text-center" value="<?php echo $item['quantity']; ?>" data-id="<?php echo $item['id']; ?>" readonly>
                        <button class="btn btn-secondary btn-sm btn-increase" data-id="<?php echo $item['id']; ?>">+</button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

   

    <!-- Note Form Section -->

<div class="note-section mt-4">
    <label for="user-note">Notes</label>
    <textarea id="user-note" class="form-control" placeholder="Enter your note here" name= "note"
              style="border-color: #B3B3B3; border-radius: 10px; height: 150px; resize: none;"></textarea>
</div>




</div>

<!-- Cart Items List Above the Total -->

<div class="col-lg-4">
   
<div class="card ">
       <!-- Seat Reservation -->
       <strong><h4 class="m-3 order-h4">Seat Reservation</h4></strong> 
        <div class="card-body">
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



        <p class="card-text d-flex justify-content-between align-items-center flex-wrap">
    <label for="reservation-type" class="form-label mb-2 mb-md-0">
        <strong class="fs-6 text-dark">Reservation Type:</strong>
    </label>
    <select id="reservation-type" class="form-control w-auto">
        <option class="re-op" value="Over the counter">Over the counter</option>
        <option  class="re-op" value="Pickup">Pick-up</option>
    </select>
</p>

<div class="party-size d-flex align-items-center gap-2" id="party-size">
    <strong class="fs-6 text-dark">Party Size</strong>
    <div class="input-group scale-size ms-auto w-auto">
        <button class="btn btn-outline-secondary" type="button" id="button-minus">-</button>
        <input type="number" name="party_size" id="party_size" class="form-control text-center" value="1" id="number-input">

        <button class="btn btn-outline-secondary" type="button" id="button-plus">+</button>
    </div>
</div>







    <!-- Order Summary Card -->
 
       <strong><h4 class="m-3 order-h4">Order Summary</h4></strong> 
        <div class="card-body">
  
        <p class="d-flex justify-content-between">
    <strong>Name:</strong>
    <span><?php echo $clientFullName; ?></span>
</p>


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
<!-- <p class="d-flex justify-content-between">
    <strong>Transaction ID:</strong>
    <span><?php echo htmlspecialchars($_SESSION['transaction_id']); ?></span>
</p> -->

            <p class="card-text" id="totalAmount">
            <strong class="fs-5" style="color: #616161;">Total:</strong>

            <strong> <span class="float-end fs-5" style="color: #FF902B;">₱<?php echo number_format($totalPrice, 2); ?></span></strong> 
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
    <?php endif; ?>
</div>

<!-- Alert for Item Removed -->
<div id="alert" class="alert alert-danger alert-dismissible fade show" role="alert">
  Item removed from cart.
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<!-- Modal HTML -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this item?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="deleteBtn">Delete</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Structure -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="checkoutModalLabel">Confirm Your Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to checkout your item?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn proceedBtn" id="checkoutBtn">Checkout</button>
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

<script>
$(document).ready(function() {
    
    $(".available-time-btn").click(function () {


    const selectedTime = $(this).data("time-id"); // Get the time ID
    $("#selected-time").val(selectedTime); // Store it in a hidden input for later use
});


    $("#checkoutBtn").click(function () {
        const note = $("#user-note").val();
        const reservationType = $("#reservation-type").val();
        const party_size = $("#party_size").val();
        const selectedTime = $("#selected-time").val();

        $.ajax({
    url: '',
    type: 'POST',
    data: { checkout: true, note: note, reservation_type: reservationType , party_size: party_size , reservation_time: selectedTime},

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

    $('.btn-decrease, .btn-increase').click(function() {
        let itemId = $(this).data('id');
        let $quantityInput = $(`.quantity-input[data-id="${itemId}"]`);
        let currentQuantity = parseInt($quantityInput.val());

        // Adjust quantity
        if ($(this).hasClass('btn-increase')) {
            currentQuantity += 1;
        } else if ($(this).hasClass('btn-decrease') && currentQuantity > 1) {
            currentQuantity -= 1;
        }

        // Update input field
        $quantityInput.val(currentQuantity);

        // AJAX update for new quantity
        $.post("cart.php", { update_quantity: true, item_id: itemId, quantity: currentQuantity }, function(response) {
            if (response.success) {
                if (currentQuantity === 1) {
                    $('#deleteModal').modal('show');
                    $('#deleteBtn').data('id', itemId);  // Store item id for removal
                }
            } else {
                // Handle any errors from the server response
                alert('Failed to update quantity. Please try again.');
            }
        }, 'json');
    });

    // Handle delete button click
    $('#deleteBtn').click(function() {
        let itemId = $(this).data('id');

        // Perform AJAX request to delete the item
        $.post("cart.php", { delete_item: true, item_id: itemId }, function(response) {
            if (response.success) {
                // Close the modal first
                $('#deleteModal').modal('hide');

                // Reload the page after successful deletion
                location.reload();  // This will reload the page
            } else {
                alert('Failed to delete item. Please try again.');
            }
        }, 'json');
    });

    // Confirm removal when user clicks 'Delete'
    $('#deleteBtn').click(function() {
        let itemId = $(this).data('id');
        
        $.post("cart.php", { remove_item: true, item_id: itemId }, function(response) {
            if (response.success) {
                $(`.cart-item[data-id="${itemId}"]`).remove();
                $('#alert').fadeIn().delay(3000).fadeOut();  // Show alert
                $('#deleteModal').modal('hide');
                // This will reload the current page
                location.reload();  // Automatically reload the page after successful deletion
            }
        }, 'json');
    });
});
</script>


</body>
</html>
