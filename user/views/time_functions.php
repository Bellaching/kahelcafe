<?php
function getAvailableTimes($conn, $user_id, $date = null) {
    $times = [];
    
    // Get all time slots
    $timeQuery = $conn->query("SELECT id AS time_id, time FROM res_time ORDER BY time");
    while ($row = $timeQuery->fetch_assoc()) {
        $times[] = $row;
    }
    
    // If a date is provided, check availability
    if ($date) {
        foreach ($times as &$time) {
            // Check if already booked or user has reservation
            $checkQuery = $conn->prepare("
                SELECT status, client_id 
                FROM reservation 
                WHERE reservation_date = ? 
                AND reservation_time_id = ?
            ");
            $checkQuery->bind_param("si", $date, $time['time_id']);
            $checkQuery->execute();
            $result = $checkQuery->get_result();
            
            if ($result->num_rows > 0) {
                $status = $result->fetch_assoc();
                if ($status['client_id'] == $user_id) {
                    $time['status'] = 'your_reservation';
                    $time['disabled'] = true;
                } elseif ($status['status'] == 'booked') {
                    $time['status'] = 'booked';
                    $time['disabled'] = true;
                }
            } else {
                $time['status'] = 'available';
                $time['disabled'] = false;
            }
        }
    }
    
    return $times;
}
?>