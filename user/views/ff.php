// Handle checkout
if (isset($_POST['checkout'])) {
    $userNote = isset($_POST['note']) ? $_POST['note'] : '';

    $reservationType = isset($_POST['reservation_type']) ? $_POST['reservation_type'] : '';
    $transactionId = strtoupper(bin2hex(random_bytes(6)));
    $reservation_date = isset($_POST['reservation_date']) ? $_POST['reservation_date'] : null;
$reservation_time = isset($_POST['reservation_time']) ? $_POST['reservation_time'] : null;
$party_size = isset($_POST['party_size']) ? $_POST['party_size'] : 1;


    // Check for pending orders
    $pendingStatuses = ['for confirmation', 'payment', 'booked'];
    $query = "SELECT COUNT(*) FROM Orders WHERE user_id = ? AND status IN (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $clientId, ...$pendingStatuses);
    $stmt->execute();
    $stmt->bind_result($pendingOrderCount);
    $stmt->fetch();
    $stmt->close();

    if ($pendingOrderCount > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending order.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO Orders (user_id, client_full_name, total_price, transaction_id, reservation_type, status, reservation_fee) 
    VALUES (?, ?, ?, ?, ?, ?, ?)");
$reservation_fee = 50; 
$status = "for confirmation";
$stmt->bind_param("isssssi", $clientId, $clientFullName, $totalPrice, $transactionId, $reservationType, $status, $reservation_fee);

$stmt->execute();
$orderId = $stmt->insert_id;
$stmt->close();
   
    foreach ($_SESSION['cart'] as $item) {
        $stmt_items = $conn->prepare("INSERT INTO Order_Items  (order_id, item_name, size, temperature, quantity, note, price, reservation_date, reservation_time, party_size) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt_items) {
            die("Prepare failed: " . $conn->error);
        }
        
        foreach ($_SESSION['cart'] as $item) {
            $stmt_items->bind_param(
                "isssissss",  
                $orderId, 
                $item['name'],  
                $item['size'], 
                $item['temperature'], 
                $item['quantity'], 
                $userNote, 
                $item['price'],
                $reservation_date, 
                $reservation_time, 
                $party_size
            );
        
            $stmt_items->execute();
        }
        $stmt_items->close();
        
    }
    

    // Clear cart in session and database
    unset($_SESSION['cart']);
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $stmt->close();

    if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo json_encode(['success' => true, 'redirect' => "order-track.php?transaction_id=" . $transactionId]);
    } else {
        header("Location: order-track.php?transaction_id=" . $transactionId);
    }
    exit;
}