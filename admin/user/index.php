
<?php
include './../../connection/connection.php';


// Get the action
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'read') {
    $query = "SELECT transaction_id, client_full_name, order_created, total_price, reservation_type FROM orders";
    $result = $conn->query($query);
    $users = [];

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode($users);
    exit();  
}




// if ($action === 'update') {
//     $id = $_POST['id'];

//     $role = $_POST['role'];

//     // Prepare and execute the query
//     $query = $conn->prepare("UPDATE orders SET  role = ? WHERE id = ?");
//     $query->bind_param('si',  $role, $id);

//     if ($query->execute()) {
//         echo json_encode(['success' => true]);
//     } else {
//         echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
//     }
//     $query->close();
//     exit();
// }


if ($action === 'delete') {
    $id = $_POST['id'];

    // Prepare and execute the query
    $query = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $query->bind_param('i', $id);

    if ($query->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $conn->error]);
    }
    $query->close();
    exit();
}

// Close the database connection
$conn->close();
?>