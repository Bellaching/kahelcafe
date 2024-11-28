<?php
include '../../connection/connection.php';

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');  // Default to today's date if no date is provided

// Initialize arrays to store the reservation status for each time slot
$times = [];
$status_reservations = [];

// Fetch available times from res_time table
$query = "SELECT * FROM res_time";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $time_id = $row['id'];
        $time = $row['time'];
        
        // Check if a reservation exists for this time slot on the selected date
        $status_query = "SELECT client_id FROM reservation WHERE reservation_time = '$time_id' AND reservation_date = '$date'";
        $status_result = mysqli_query($conn, $status_query);
        
        if (mysqli_num_rows(result: $status_result) > 0) {
            // Reservation exists; get the client_id
            $reservation = mysqli_fetch_assoc($status_result);
            $client_id = $reservation['client_id'];
            $status = 'booked';
        } else {
            // No reservation exists
            $client_id = null;
            $status = 'available';
        }
        
        $times[] = $time;
        $status_reservations[] = [
            'time_id' => $time_id,
            'time' => $time,
            'status' => $status,
            'client_id' => $client_id
        ];
    }
}

// Return JSON response
echo json_encode([
    'times' => $times,
    'status_reservations' => $status_reservations
]);
?>
