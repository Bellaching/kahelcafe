<?php
session_start();
include './../../connection/connection.php';

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'login') {
    $username = $_POST['username'];
    $password = $_POST['password'];

  
    $query = $conn->prepare("SELECT id, username, password, role FROM admin_list WHERE username = ?");
    $query->bind_param('s', $username);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($password === $user['password']) { 
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            echo json_encode(['success' => false, 'message' => 'Invalid password']);
        } else {
            echo json_encode(['success' => true, 'role' => $user['role']]);
           
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }

    $query->close();
    $conn->close();
    exit();
}
?>
