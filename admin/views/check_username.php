<?php
header('Content-Type: application/json');

// Database connection
include './../../connection/connection.php';

$username = $_POST['username'] ?? '';

if (empty($username)) {
    echo json_encode(['error' => 'Username is required']);
    exit;
}

// Check if username exists
$stmt = $conn->prepare("SELECT id FROM admin_list WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

echo json_encode(['exists' => $stmt->num_rows > 0]);

$stmt->close();
$conn->close();
?>