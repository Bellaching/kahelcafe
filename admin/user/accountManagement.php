
<?php
include './../../connection/connection.php';
 

// Get the action
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'read') {
    $query = "SELECT id, username, email, role, date_created FROM admin_list";
    $result = $conn->query($query);
    $users = [];

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode($users);
    exit();  
}


if ($action === 'create') {
  $username = $_POST['username'];
$email = $_POST['email'];
$password = $_POST['password'];
$role = $_POST['role'];

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Prepare and execute the query
$query = $conn->prepare("INSERT INTO admin_list (username, email, password, role) VALUES (?, ?, ?, ?)");
$query->bind_param('ssss', $username, $email, $hashedPassword, $role);

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
    $query = $conn->prepare("UPDATE admin_list SET  role = ? WHERE id = ?");
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
    $query = $conn->prepare("DELETE FROM admin_list WHERE id = ?");
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