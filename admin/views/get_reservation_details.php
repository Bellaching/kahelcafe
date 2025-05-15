<?php
header('Content-Type: application/json');
include './../../connection/connection.php';

// Check if transaction code is provided
if (!isset($_POST['transaction_code'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Transaction code is required'
    ]);
    exit;
}

$transactionCode = $_POST['transaction_code'];

try {
    // Prepare SQL query to get reservation details
    $sql = "SELECT 
                r.transaction_code,
                r.client_full_name,
                r.party_size,
                r.reservation_date,
                r.reservation_time,
                r.reservation_fee,
                r.total_price,
                r.status,
                r.payment_receipt,
                r.contact_number,
                r.email,
                r.special_requests
            FROM reservations r
            WHERE r.transaction_code = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $transactionCode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $reservation = $result->fetch_assoc();
        
        // Format date for display if needed
        $reservation['reservation_date'] = date('F j, Y', strtotime($reservation['reservation_date']));
        
        // Format time for display if needed
        $reservation['reservation_time'] = date('h:i A', strtotime($reservation['reservation_time']));
        
        echo json_encode([
            'success' => true,
            'data' => $reservation
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No reservation found with that transaction code'
        ]);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching reservation details: ' . $e->getMessage()
    ]);
}
?>