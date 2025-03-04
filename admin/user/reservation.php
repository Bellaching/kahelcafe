<?php
include './../../connection/connection.php';

// Get the action
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'read') {
    $query = "SELECT transaction_code, clientFullName, amount, res_status FROM reservation";
    $result = $conn->query($query);
    $reservation = [];

    while ($row = $result->fetch_assoc()) {
        $reservation[] = $row;
    }

    echo json_encode($reservation);
    exit();
}

if ($action === 'update') {
    $id = $_POST['id'];
    $res_status = $_POST['res_status'];
    $clientFullName = $_POST['clientFullName'];

    // Sanitize inputs and prepare the query
    if ($stmt = $conn->prepare("UPDATE reservation SET res_status = ?, clientFullName = ? WHERE transaction_code = ?")) {
        $stmt->bind_param('sss', $res_status, $clientFullName, $id);

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
    if ($stmt = $conn->prepare("DELETE FROM reservation WHERE transaction_code = ?")) {
        $stmt->bind_param('s', $id);

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