<?php
// Include your database connection
require_once './../../connection/connection.php';

// Get the menu ID from the GET request
$menuId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($menuId > 0) {
    // Fetch menu item details from the database
    $sql = "SELECT * FROM menu WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $menuId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the item exists
    if ($result->num_rows > 0) {
        $menuItem = $result->fetch_assoc();
        // Return the data as a JSON response
        echo json_encode($menuItem);
    } else {
        echo json_encode(['error' => 'Menu item not found']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Invalid menu ID']);
}

$conn->close();
?>
</html>

