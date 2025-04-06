<?php
header('Content-Type: application/json');

// Database connection
include './../../connection/connection.php';

$email = $_POST['email'] ?? '';

if (empty($email)) {
    echo json_encode(['error' => 'Email is required']);
    exit;
}

// Check if email exists
$stmt = $conn->prepare("SELECT id FROM admin_list WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

echo json_encode(['exists' => $stmt->num_rows > 0]);

$stmt->close();
$conn->close();
?>