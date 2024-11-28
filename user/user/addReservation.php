<?php
require_once '../../connection/connection.php';

// Retrieve form data
$client_id = $_POST['client_id'];
$selectedDate = $_POST['reservation_date'];
$selectedTime = $_POST['reservation_time'];
$partySizeResult = $_POST['party_size'];
$amount = $_POST['amount'];

// Prepare the SQL query
$sql = "INSERT INTO reservation (
            client_id, 
            transaction_code, 
            reservation_date, 
            reservation_time, 
            party_size, 
            note, 
            amount, 
            res_status
        ) VALUES (?, NULL, ?, ?, ?, NULL, ?, 'pending')";

// Prepare and execute statement
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("issss", $client_id, $selectedDate, $selectedTime, $partySizeResult, $amount);
    if ($stmt->execute()) {
        echo "Reservation successfully added!";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Error preparing query: " . $conn->error;
}
$conn->close();
?>
