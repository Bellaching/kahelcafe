<?php
include "./../../connection/connection.php";

// Query to fetch the top 3 menu items with average ratings and order counts
$sql = "
    SELECT 
        menu.id, 
        menu.name, 
        menu.image, 
        menu.price, 
        COUNT(order_history.menu_id) AS order_count, 
        COALESCE(AVG(order_history.rating), 0) AS avg_rating
    FROM 
        menu
    LEFT JOIN 
        order_history 
    ON 
        menu.id = order_history.menu_id
    GROUP BY 
        menu.id, menu.name, menu.image, menu.price
    ORDER BY 
        order_count DESC
    LIMIT 3
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$topMenus = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $topMenus[] = $row;
    }
}

// Output the top menus as JSON
header('Content-Type: application/json');
echo json_encode($topMenus);
?>
