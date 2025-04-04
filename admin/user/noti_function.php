<?php
class NotificationFunctions {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
        $this->initSession();
    }

    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function checkAuthorization() {
        if (!isset($_SESSION['user_id'])) {
            $this->sendJsonResponse(false, 'Unauthorized access');
        }
        return (int)$_SESSION['user_id'];
    }

    private function sendJsonResponse($success, $message = '', $data = []) {
        header('Content-Type: application/json');
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
        echo json_encode($response);
        exit;
    }

    public function handleRequest() {
        try {
            $action = $_GET['action'] ?? $_POST['action'] ?? '';
            
            if (empty($action)) {
                throw new Exception('No action specified');
            }

            if (!method_exists($this, $action)) {
                throw new Exception('Invalid action');
            }

            $this->$action();
        } catch (Exception $e) {
            $this->sendJsonResponse(false, $e->getMessage());
        }
    }

    private function markAsRead() {
        $user_id = $this->checkAuthorization();
        $query = "UPDATE notification SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $this->sendJsonResponse(true, 'Notifications marked as read');
        } else {
            $this->sendJsonResponse(false, 'Failed to mark notifications as read');
        }
        
        $stmt->close();
    }

    private function clearNotifications() {
        $user_id = $this->checkAuthorization();
        $query = "DELETE FROM notification WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $this->sendJsonResponse(true, 'Notifications cleared');
        } else {
            $this->sendJsonResponse(false, 'Failed to clear notifications');
        }
        
        $stmt->close();
    }

    private function getNotifications() {
        $user_id = $this->checkAuthorization();
        
        // Get notifications
        $query = "SELECT n.*, r.transaction_code, r.res_status 
                FROM notification n
                LEFT JOIN reservation r ON n.reservation_id = r.id
                WHERE n.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT 15";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        // Get unread count
        $countQuery = "SELECT COUNT(*) as count FROM notification WHERE user_id = ? AND is_read = FALSE";
        $countStmt = $this->conn->prepare($countQuery);
        $countStmt->bind_param("i", $user_id);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $count = (int)$countResult->fetch_assoc()['count'];
        
        $stmt->close();
        $countStmt->close();
        
        $this->sendJsonResponse(true, '', [
            'notifications' => $notifications,
            'count' => $count
        ]);
    }

    private function checkNewNotifications() {
        $user_id = $this->checkAuthorization();
        
        // Get unread count
        $query = "SELECT COUNT(*) as count FROM notification WHERE user_id = ? AND is_read = FALSE";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = (int)$result->fetch_assoc()['count'];
        
        // Get latest notification if any
        $latest_notification = null;
        if ($count > 0) {
            $latestQuery = "SELECT n.*, r.transaction_code, r.res_status 
                          FROM notification n
                          LEFT JOIN reservation r ON n.reservation_id = r.id
                          WHERE n.user_id = ? AND n.is_read = FALSE
                          ORDER BY n.created_at DESC
                          LIMIT 1";
            $latestStmt = $this->conn->prepare($latestQuery);
            $latestStmt->bind_param("i", $user_id);
            $latestStmt->execute();
            $latestResult = $latestStmt->get_result();
            $latest_notification = $latestResult->fetch_assoc();
            $latestStmt->close();
        }
        
        $stmt->close();
        
        $this->sendJsonResponse(true, '', [
            'count' => $count,
            'latest_notification' => $latest_notification
        ]);
    }

    private function getUnreadCount() {
        if (!isset($_SESSION['user_id'])) {
            $this->sendJsonResponse(true, '', ['count' => 0]);
        }

        $user_id = (int)$_SESSION['user_id'];
        $query = "SELECT COUNT(*) as count FROM notification WHERE user_id = ? AND is_read = FALSE";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();
        $this->sendJsonResponse(true, '', ['count' => $count]);
    }
}

// Initialize and handle request
require_once __DIR__ . '/../../connection/connection.php';
$notiFunctions = new NotificationFunctions($conn);
$notiFunctions->handleRequest();
?>