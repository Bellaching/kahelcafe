<?php
header('Content-Type: application/json');

// Database connection
include './../../connection/connection.php';

$response = ['data' => []]; // DataTables expects data in a 'data' property

try {
    $query = "SELECT id, username, email, role, DATE_FORMAT(date_created, '%Y-%m-%d %H:%i:%s') as date_created FROM admin_list";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    while ($row = $result->fetch_assoc()) {
        $response['data'][] = $row;
    }
    
    $result->close();
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>