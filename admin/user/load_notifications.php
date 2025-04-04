<?php
// Start output buffering
ob_start();

// Ensure session is properly started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'read_and_close'  => false,
    ]);
}

require_once __DIR__ . '/../../connection/connection.php';

// Debug session
error_log("Session status: " . session_status());
error_log("Session ID: " . session_id());
error_log("Full session: " . print_r($_SESSION, true));

// Set JSON header
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    die(json_encode([
        'success' => false,
        'error' => 'Unauthorized',
        'debug' => [
            'session_status' => session_status(),
            'session_id' => session_id(),
            'session_data' => $_SESSION
        ]
    ]));
}

// Get user_id without type casting first
$user_id = $_SESSION['user_id'];
error_log("Raw user_id from session: " . $user_id);

// Then cast to integer if needed
$user_id = (int)$user_id;
error_log("Integer user_id: " . $user_id);

try {
    $query = "SELECT n.*, r.transaction_code, r.res_status 
              FROM notification n
              JOIN reservation r ON n.reservation_id = r.id
              WHERE n.user_id = ?
              ORDER BY n.created_at DESC
              LIMIT 15";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'count' => count($notifications),
        'debug' => [
            'session_user_id' => $_SESSION['user_id'],
            'used_user_id' => $user_id,
            'num_rows' => count($notifications)
        ]
    ]);

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'debug' => [
            'exception' => $e->getMessage(),
            'user_id_used' => $user_id,
            'session_user_id' => $_SESSION['user_id'] ?? null
        ]
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
    ob_end_flush();
}