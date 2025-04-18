<?php
include '../../connection/connection.php';
session_start();

header('Content-Type: application/json');

// Get current user ID from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Statuses that make a time slot unavailable
$occupied_statuses = [ 'payment', 'paid', 'booked', 'rate us'];
$cancel_status = 'cancel';

// Initialize response
$response = [
    'times' => [],
    'status_reservations' => [],
    'user_id' => $user_id // Include user_id in response for debugging
];

// Get all time slots
$time_query = "SELECT * FROM res_time ORDER BY time";
$time_result = mysqli_query($conn, $time_query);

if ($time_result) {
    while ($time = mysqli_fetch_assoc($time_result)) {
        $time_id = $time['id'];
        $time_value = $time['time'];
        $color = '#07D090'; // Default to available (green)
        $status = 'available';
        $is_available = true;
        $is_selectable = true; // Only true for available slots
        $res_status = null;

        // Check for any existing reservation
        $res_query = "SELECT client_id, res_status FROM reservation 
                     WHERE reservation_time_id = ? 
                     AND reservation_date = ?
                     LIMIT 1";
        $stmt = $conn->prepare($res_query);
        $stmt->bind_param("is", $time_id, $date);
        $stmt->execute();
        $res_result = $stmt->get_result();

        if ($res_result->num_rows > 0) {
            $res = $res_result->fetch_assoc();
            $res_status = $res['res_status'];
            
            if (in_array($res_status, $occupied_statuses)) {
                $status = 'booked';
                $is_available = false;
                $is_selectable = false;

                // Check if it's the current user's reservation
                if ($user_id && $res['client_id'] == $user_id) {
                    $status = 'your_reservation';
                    $color = '#9647FF'; // Purple for user's reservation
                    $is_selectable = false; // User can't select their own reserved slot
                } else {
                    $color = '#E60000'; // Red for others' reservations
                }
            } elseif ($res_status === $cancel_status) {
                // Canceled reservations show as available
                $status = 'available';
                $is_available = true;
                $is_selectable = true;
                $color = '#07D090';
            }
        }

        $response['times'][] = $time_value;
        $response['status_reservations'][] = [
            'time_id' => $time_id,
            'time' => $time_value,
            'status' => $status,
            'color' => $color,
            'is_available' => $is_available,
            'is_selectable' => $is_selectable,
            'res_status' => $res_status
        ];
    }
} else {
    $response['error'] = 'Failed to fetch time slots: ' . mysqli_error($conn);
}

echo json_encode($response);
?>