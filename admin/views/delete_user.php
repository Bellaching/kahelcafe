<?php
header('Content-Type: application/json');

// Database connection
include './../../connection/connection.php';

$response = ['success' => false];

$id = $_POST['id'] ?? 0;

if (empty($id)) {
    $response['message'] = 'Invalid user ID';
    echo json_encode($response);
    exit;
}

$stmt = $conn->prepare("DELETE FROM admin_list WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $response['success'] = true;
} else {
    $response['message'] = 'Error deleting user';
}

echo json_encode($response);
$stmt->close();
$conn->close();
?>