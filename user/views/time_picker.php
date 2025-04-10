<?php
session_start();
include '../../connection/connection.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get the selected date from the request
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Fetch all available time slots
$times = [];
$query = "SELECT id, time FROM res_time ORDER BY time";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $time_id = $row['id'];
        $time = $row['time'];
        
        // Check both reservation and orders tables for conflicts
        $status_query = "
            (SELECT 'reservation' as source, client_id, res_status as status 
             FROM reservation 
             WHERE reservation_time_id = '$time_id' 
             AND reservation_date = '$date'
             AND res_status IN ('for confirmation', 'payment', 'paid', 'booked')
             LIMIT 1)
            UNION
            (SELECT 'order' as source, user_id as client_id, status 
             FROM orders 
             WHERE reservation_id = '$time_id' 
             AND reservation_date = '$date'
             AND status IN ('for confirmation', 'payment', 'paid', 'booked', 'rate us')
             LIMIT 1)
            LIMIT 1
        ";
        
        $status_result = mysqli_query($conn, $status_query);
        
        if (mysqli_num_rows($status_result) > 0) {
            $reservation = mysqli_fetch_assoc($status_result);
            $client_id = $reservation['client_id'];
            
            // Determine if it's the current user's reservation
            $status = ($client_id == $user_id) ? 'your_reservation' : 'booked';
            $color = ($status == 'your_reservation') ? '#9647FF' : '#E60000';
        } else {
            $status = 'available';
            $color = '#07D090';
        }
        
        $times[] = [
            'id' => $time_id,
            'time' => $time,
            'status' => $status,
            'color' => $color
        ];
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($times);
?>