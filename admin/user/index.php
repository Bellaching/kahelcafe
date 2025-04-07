<?php
// Start the session
session_start();

include './../../connection/connection.php';
 
// Get the action
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'read') {
    $sort = isset($_POST['sort']) && $_POST['sort'] === 'desc' ? 'DESC' : 'ASC';
    
    $query = "SELECT order_id, client_full_name, created_at, transaction_id, total_price, reservation_time, reservation_type, status 
              FROM orders 
              ORDER BY created_at $sort";
              
    $result = $conn->query($query);
    $orders = [];

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    echo json_encode($orders);
    exit();  
}

if ($action === 'fetchNotifications') {
    // Check if the session variable exists
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $query = "SELECT id, message, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode([
            'success' => true,
            'notificationCount' => count($notifications),
            'notifications' => $notifications
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'User not logged in'
        ]);
    }
    exit();
}

if ($action === 'getOrderItems') {
    $orderId = $_POST['order_id'] ?? null;

    if (!$orderId) {
        echo json_encode(['success' => false, 'message' => 'Order ID is missing']);
        exit();
    }

    $query = "
        SELECT oi.item_name, oi.price, oi.size, oi.temperature, oi.quantity, oi.receipt,
               o.client_full_name, o.transaction_id, o.reservation_type, o.created_at, o.total_price
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        WHERE oi.order_id = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $orderId);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $items = [];

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

       echo json_encode(['success' => true, 'items' => $items, 'receipt' => $items[0]['receipt'] ?? null]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch items: ' . $conn->error]);
    }

    $stmt->close();
    exit();
}

if ($action === 'update') {
    $id = $_POST['id']; // transaction_id
    $status = $_POST['status'];

    // Fetch the user_id associated with the order
    $userQuery = "SELECT user_id FROM orders WHERE transaction_id = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param('i', $id);
    $userStmt->execute();
    $userStmt->bind_result($userId);
    $userStmt->fetch();
    $userStmt->close();

    // Update the order status
    if ($stmt = $conn->prepare("UPDATE orders SET status = ? WHERE transaction_id = ?")) {
        $stmt->bind_param('si', $status, $id);

        if ($stmt->execute()) {
            // Insert a notification for the customer
            $message = "Your order status has been updated to: $status.";
            $notificationQuery = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
            $notificationStmt = $conn->prepare($notificationQuery);
            $notificationStmt->bind_param('is', $userId, $message);
            $notificationStmt->execute();
            $notificationStmt->close();

            // Send real-time update via SSE
            echo json_encode(['success' => true, 'status' => $status]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Prepared statement failed: ' . $conn->error]);
    }
    exit();
}

if ($action === 'markAllAsRead') {
    // Check if the session variable exists
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $updateQuery = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('i', $userId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark notifications as read.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
    }
    exit();
}

if ($action === 'delete') {
    $id = $_POST['id'];

    // Sanitize inputs and prepare the query
    if ($stmt = $conn->prepare("DELETE FROM orders WHERE transaction_id = ?")) {
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $conn->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Prepared statement failed: ' . $conn->error]);
    }
    exit();
}

$conn->close();
?>