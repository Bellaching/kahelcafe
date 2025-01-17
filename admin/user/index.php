<?php
include './../../connection/connection.php';

// Get the action
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'read') {
    $query = "SELECT order_id, client_full_name, created_at, transaction_id ,total_price, reservation_type, status FROM orders";
    $result = $conn->query($query);
    $orders = [];

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    echo json_encode($orders);
    exit();  
}

if ($action === 'getOrderItems') {
    $orderId = $_POST['order_id'];

    $query = "
        SELECT oi.item_name, oi.price, oi.size, oi.temperature, oi.quantity, 
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

        echo json_encode(['success' => true, 'items' => $items]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch items: ' . $conn->error]);
    }

    $stmt->close();
    exit();
}


if ($action === 'update') {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $client_full_name = $_POST['client_full_name'];
   
    // Sanitize inputs and prepare the query
    if ($stmt = $conn->prepare("UPDATE orders SET status = ? WHERE transaction_id = ?")) {
        $stmt->bind_param('si', $status, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Prepared statement failed: ' . $conn->error]);
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

// Close the database connection
$conn->close();
?>
