<?php
// Set the session ID from the query parameter (if provided)
$sessionId = $_GET['session_id'] ?? null;
if ($sessionId) {
    session_id($sessionId); // Set the session ID
}
session_start(); // Start the session

header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");
header("Connection: keep-alive");

include './../../connection/connection.php';

// Debugging: Log session data
error_log("Session ID: " . session_id());
error_log("User ID: " . ($_SESSION['user_id'] ?? 'Not set'));

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo "data: " . json_encode(["error" => "User not logged in"]) . "\n\n";
    ob_flush();
    flush();
    exit();
}

$lastNotificationId = 0; // Track the last notification ID sent to the client

while (true) {
    // Fetch new notifications for the logged-in user
    $query = "SELECT id, message, created_at FROM notifications WHERE user_id = ? AND id > ? AND is_read = 0 ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $userId, $lastNotificationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!empty($notifications)) {
        $lastNotificationId = $notifications[0]['id']; // Update the last notification ID
        echo "data: " . json_encode(["notifications" => $notifications]) . "\n\n";
        ob_flush();
        flush();
    }

    sleep(5); // Check for new notifications every 5 seconds
}
?>