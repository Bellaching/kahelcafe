<?php
require_once __DIR__ . '/../../connection/connection.php';
session_start();

// Debug output
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => 'Unauthorized', 'count' => 0]));
}

$user_id = (int)$_SESSION['user_id'];
error_log("Checking notifications for user_id: $user_id");

try {
    // Check for new notifications
    $query = "SELECT COUNT(*) as count FROM notification
              WHERE user_id = ? AND is_read = FALSE";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = (int)$result->fetch_assoc()['count'];
    
    // Get the latest notification if any
    $latest_notification = null;
    if ($count > 0) {
        $query = "SELECT n.*, r.transaction_code, r.res_status 
                  FROM notification n
                  JOIN reservation r ON n.reservation_id = r.id
                  WHERE n.user_id = ? AND n.is_read = FALSE
                  ORDER BY n.created_at DESC
                  LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $latest_notification = $result->fetch_assoc();
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => $count,
        'latest_notification' => $latest_notification,
        'debug' => [
            'session_user_id' => $user_id,
            'server_time' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'count' => 0
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>