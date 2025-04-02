<?php
include './../../connection/connection.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Not logged in']));
}

if (!isset($_GET['reservation_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Reservation ID not provided']));
}

$reservationId = (int)$_GET['reservation_id'];

// Get current status
$query = "SELECT res_status FROM reservation WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$result = $stmt->get_result();
$reservation = $result->fetch_assoc();

if (!$reservation) {
    die(json_encode(['status' => 'error', 'message' => 'Reservation not found']));
}

echo json_encode(['status' => $reservation['res_status']]);