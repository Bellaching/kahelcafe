<?php
include "./../../connection/connection.php";

$menuId = $_GET['id'];
$sql = "SELECT * FROM menu WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $menuId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $menuData = $result->fetch_assoc();
    echo json_encode($menuData);
} else {
    echo json_encode(["error" => "Item not found"]);
}
?>
