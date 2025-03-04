<?php


// Check if the user is logged in and get the user_id dynamically
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    die("User not logged in.");
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

// Fetch the reservation fee from the Orders table
$reservation_fee = 0;
$reservation_fee_query = "SELECT reservation_fee FROM Orders WHERE name = 'Reservation'";
$reservation_fee_result = mysqli_query($conn, $reservation_fee_query);
if ($reservation_fee_result) {
    $row = mysqli_fetch_assoc($reservation_fee_result);
    $reservation_fee = $row['reservation_fee'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $reservation_date = mysqli_real_escape_string($conn, $_POST['reservation_date']);
  

    // Insert into reservation table using the retrieved $clientFullName
    $insert_query = "INSERT INTO Order_Items (reservation_date) 
                     VALUES ('$reservation_date')";

    if (mysqli_query($conn, $insert_query)) {
        echo "<script>alert('Reservation successfully created!'); window.location.href='reservation.php';</script>";
    } else {
        echo "<script>alert('Error creating reservation: " . mysqli_error($conn) . "');</script>";
    }
}



?>