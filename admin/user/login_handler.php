<?php
session_start();
include './../../connection/connection.php';

header('Content-Type: application/json');

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required']);
        exit();
    }

    $query = $conn->prepare("SELECT id, username, password, role FROM admin_list WHERE username = ?");
    $query->bind_param('s', $username);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($password === $user['password']) { 
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            echo json_encode(['success' => true, 'role' => $user['role']]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
}

// Default response for invalid actions
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>