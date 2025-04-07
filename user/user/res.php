<?php
include '../../connection/connection.php';

header('Content-Type: application/json');

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Initialize response array
$response = [
    'times' => [],
    'status_reservations' => []
];

// Fetch all available times
$time_query = "SELECT * FROM res_time";
$time_result = mysqli_query($conn, $time_query);

if ($time_result) {
    while ($time = mysqli_fetch_assoc($time_result)) {
        $time_id = $time['id'];
        $time_value = $time['time'];
        
        // Check reservation table
        $res_query = "SELECT client_id, res_status FROM reservation 
                     WHERE reservation_time_id = '$time_id' AND reservation_date = '$date' 
                     AND (res_status = 'pending' OR res_status = 'confirmed') LIMIT 1";
        $res_result = mysqli_query($conn, $res_query);
        
        // Check order table
        $order_query = "SELECT user_id, status FROM `orders` 
                       WHERE reservation_time_id = '$time_id' AND reservation_date = '$date'
                       AND (status = 'pending' OR status = 'confirmed') LIMIT 1";
        $order_result = mysqli_query($conn, $order_query);
        
        $status = 'available';
        $client_id = null;
        
        // Priority to reservation table
        if (mysqli_num_rows($res_result) > 0) {
            $res = mysqli_fetch_assoc($res_result);
            $status = 'booked';
            $client_id = $res['client_id'];
        } 
        // Then check order table
        elseif (mysqli_num_rows($order_result) > 0) {
            $order = mysqli_fetch_assoc($order_result);
            $status = 'booked';
            $client_id = $order['user_id'];
        }
        
        $response['times'][] = $time_value;
        $response['status_reservations'][] = [
            'time_id' => $time_id,
            'time' => $time_value,
            'status' => $status,
            'client_id' => $client_id
        ];
    }
}

echo json_encode($response);
?>