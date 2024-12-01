<?php
// Connect to the database
include './../../connection/connection.php';

// Get the data from the frontend (JSON format)
$data = json_decode(file_get_contents('php://input'), true);

// Check if all required data exists
if (isset($data['transaction_code'], $data['client_id'], $data['reservation_date'], $data['reservation_time'], 
          $data['party_size'], $data['note'], $data['amount'], $data['res_status'])) {

    // Assign data to variables
    $transaction_code = mysqli_real_escape_string($conn, $data['transaction_code']);
    $client_id = mysqli_real_escape_string($conn, $data['client_id']);
    $reservation_date = mysqli_real_escape_string($conn, $data['reservation_date']);
    $reservation_time = mysqli_real_escape_string($conn, $data['reservation_time']);
    $party_size = mysqli_real_escape_string($conn, $data['party_size']);
    $note = mysqli_real_escape_string($conn, $data['note']);
    $amount = mysqli_real_escape_string($conn, $data['amount']);
    $res_status = mysqli_real_escape_string($conn, $data['res_status']);

    // SQL query to insert data into the reservation table
    $query = "INSERT INTO reservation (transaction_code, client_id, reservation_date, reservation_time, party_size, note, amount, res_status)
              VALUES ('$transaction_code', '$client_id', '$reservation_date', '$reservation_time', '$party_size', '$note', '$amount', '$res_status')";

    if (mysqli_query($conn, $query)) {
        // Success
        echo json_encode(['success' => true]);
    } else {
        // Error
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
}

mysqli_close($conn);
?>
