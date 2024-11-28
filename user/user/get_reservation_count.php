<?php
include '../../connection/connection.php';

if (isset($_GET['date'])) {
    $date = $_GET['date'];
    
    // Query to get the reservation count for the given date
    $sql = "SELECT client_id FROM reservation WHERE reservation_date = '$date'";

    $result = $conn->query($sql);
    
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }

    // Query to get the count of rows in the res_time table
    $res_time_count_query = "SELECT COUNT(*) AS res_time_count FROM res_time";
    $res_time_result = $conn->query($res_time_count_query);
    $res_time_count = 0;
    if ($res_time_result) {
        $row = $res_time_result->fetch_assoc();
        $res_time_count = $row['res_time_count'];
    }

    // Return the reservations and the res_time_count as a JSON object
    echo json_encode([
        'reservations' => $reservations,
        'res_time_count' => $res_time_count
    ]);

    
    
    // Close the connection
    $conn->close();

    exit();  
}




?>