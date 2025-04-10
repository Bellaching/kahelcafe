<?php
include '../../connection/connection.php';
session_start();

header('Content-Type: application/json');

// Get current user ID from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Statuses that make a time slot unavailable
$occupied_statuses = ['for confirmation', 'payment', 'paid', 'booked', 'rate us'];
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
        $order_status = null;

        // Check for any existing order in the orders table
        $order_query = "SELECT user_id, status FROM orders 
                       WHERE reservation_time_id = ? 
                       AND reservation_date = ?
                       LIMIT 1";
        $stmt = $conn->prepare($order_query);
        $stmt->bind_param("is", $time_id, $date);
        $stmt->execute();
        $order_result = $stmt->get_result();

        if ($order_result->num_rows > 0) {
            $order = $order_result->fetch_assoc();
            $order_status = $order['status'];
            
            if (in_array($order_status, $occupied_statuses)) {
                $status = 'booked';
                $is_available = false;
                $is_selectable = false;

                // Check if it's the current user's order
                if ($user_id && $order['user_id'] == $user_id) {
                    $status = 'your_reservation';
                    $color = '#9647FF'; // Purple for user's reservation
                    $is_selectable = false; // User can't select their own reserved slot
                } else {
                    $color = '#E60000'; // Red for others' reservations
                }
            } elseif ($order_status === $cancel_status) {
                // Canceled orders show as available
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
            'order_status' => $order_status
        ];
    }
} else {
    $response['error'] = 'Failed to fetch time slots: ' . mysqli_error($conn);
}

echo json_encode($response);
?>