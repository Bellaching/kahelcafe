<?php
include './../../connection/connection.php';

// Set header for JSON response first
header('Content-Type: application/json');

try {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'read') {
        $query = "SELECT 
                    r.transaction_code, 
                    r.clientFullName, 
                    r.amount, 
                    r.res_status, 
                    r.party_size, 
                    r.date_created,
                    r.reservation_date,
                    r.amount,
                    r.reservation_time
                  FROM reservation r
                  ORDER BY r.date_created DESC"; // Added ORDER BY to ensure latest first
        
        $result = $conn->query($query);
        
        if ($result === false) {
            throw new Exception('Database query failed: ' . $conn->error);
        }
    
        $reservations = [];
        while ($row = $result->fetch_assoc()) {
            // Format the time display
            if (!empty($row['display_time'])) {
                $row['display_time'] = trim($row['display_time']);
            } elseif (!empty($row['reservation_time'])) {
                $row['display_time'] = date('h:i A', strtotime($row['reservation_time']));
            } else {
                $row['display_time'] = 'Not specified';
            }
            $reservations[] = $row;
        }
    
        echo json_encode($reservations);
        exit();
    }

    if ($action === 'delete') {
        if (!isset($_POST['id'])) {
            throw new Exception('Missing required ID parameter');
        }

        $id = $conn->real_escape_string($_POST['id']);

        $stmt = $conn->prepare("DELETE FROM reservation WHERE transaction_code = ?");
        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        $stmt->bind_param('s', $id);
        $success = $stmt->execute();
        $stmt->close();

        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Reservation deleted successfully' : 'Delete failed'
        ]);
        exit();
    }

    if ($action === 'update') {
        if (!isset($_POST['id']) || !isset($_POST['res_status'])) {
            throw new Exception('Missing required parameters');
        }

        $id = $conn->real_escape_string($_POST['id']);
        $res_status = $conn->real_escape_string($_POST['res_status']);

        $stmt = $conn->prepare("UPDATE reservation SET res_status = ? WHERE transaction_code = ?");
        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        $stmt->bind_param('ss', $res_status, $id);
        $success = $stmt->execute();
        $stmt->close();

        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Status updated successfully' : 'Update failed'
        ]);
        exit();
    }

    throw new Exception('Invalid action specified');
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>