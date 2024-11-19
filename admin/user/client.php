<?php
include './../../connection/connection.php';


// Get the action
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'read') {
    $query = "SELECT id, CONCAT(firstname, ' ', lastname) AS fullname, email, contact_number, created_at FROM client";
    $result = $conn->query($query);
    $users = [];

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode($users);
    exit();  
}

if ($action === 'create') {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number'];
   

    // Prepare and execute the query
    $query = $conn->prepare("INSERT INTO client (firstname, lastname, email, contact_number, created_at) VALUES (?, ?, ?, ?, ?)");
    $query->bind_param('sssss', $firstname, $lastname, $email, $contact_number, $created_at);

    if ($query->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User creation failed: ' . $conn->error]);
    }
    $query->close();
    exit();
}


if ($action === 'update') {
    $id = $_POST['id'];

    $role = $_POST['role'];

    // Prepare and execute the query
    $query = $conn->prepare("UPDATE client SET  role = ? WHERE id = ?");
    $query->bind_param('si',  $role, $id);

    if ($query->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
    }
    $query->close();
    exit();
}


if ($action === 'delete') {
    $id = $_POST['id'];

    // Prepare and execute the query
    $query = $conn->prepare("DELETE FROM client WHERE id = ?");
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
