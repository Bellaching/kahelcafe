<?php
header('Content-Type: application/json');

// Database connection
include './../../connection/connection.php';

$response = ['success' => false];

$id = $_POST['id'] ?? 0;
$role = $_POST['role'] ?? '';

if (empty($id) || !in_array($role, ['owner', 'staff'])) {
    $response['message'] = 'Invalid input';
    echo json_encode($response);
    exit;
}

$stmt = $conn->prepare("UPDATE admin_list SET role = ? WHERE id = ?");
$stmt->bind_param("si", $role, $id);

if ($stmt->execute()) {
    $response['success'] = true;
} else {
    $response['message'] = 'Error updating user';
}

echo json_encode($response);
$stmt->close();
$conn->close(); 
?>