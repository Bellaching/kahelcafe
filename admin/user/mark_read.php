<?php
require_once 'noti_functions.php';
$notiSystem->markAsRead();
header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>