<?php
require_once 'noti_functions.php';
header('Content-Type: application/json');
echo json_encode($notiSystem->checkNewNotifications());
?>