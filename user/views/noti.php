<?php
// notification_modal.php

include __DIR__ . '/../../connection/connection.php';

// Get the current user's ID from session
$userId = $_SESSION['user_id'] ?? 0;

// Initialize session storage if not exists
if (!isset($_SESSION['read_notifications'])) {
    $_SESSION['read_notifications'] = [];
}

if (!isset($_SESSION['cleared_notifications'])) {
    $_SESSION['cleared_notifications'] = [];
}

// Function to get latest notifications for current user (excluding cleared ones)
function getLatestNotifications($conn, $userId, $limit = 10) {
    $notifications = [];
    $clearedIds = $_SESSION['cleared_notifications'] ?? [];
    $allowedStatuses = ['payment', 'booked', 'rate us', 'completed', 'cancelled'];
    
    // Get orders for current user (excluding cleared)
    $orderQuery = "SELECT order_id, client_full_name, status, last_updated 
                   FROM orders 
                   WHERE user_id = ? 
                   AND status IN ('" . implode("','", $allowedStatuses) . "')";
                   
    if (!empty($clearedIds['order'])) {
        $orderQuery .= " AND order_id NOT IN (" . implode(',', $clearedIds['order']) . ")";
    }
    
    $orderQuery .= " ORDER BY last_updated DESC LIMIT $limit";
    $orderStmt = $conn->prepare($orderQuery);
    $orderStmt->bind_param("i", $userId);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    
    // Check if query succeeded
    if ($orderResult === false) {
        error_log("Order query failed: " . $conn->error);
    } else {
        while ($row = $orderResult->fetch_assoc()) {
            $notifications[] = [
                'type' => 'order',
                'id' => $row['order_id'],
                'name' => $row['client_full_name'],
                'status' => $row['status'],
                'time' => $row['last_updated'],
                'is_read' => in_array($row['order_id'], $_SESSION['read_notifications']['order'] ?? [])
            ];
        }
    }
    
    // Get reservations for current user (excluding cleared)
    $reservationQuery = "SELECT id, clientFullName, res_status, date_created 
                         FROM reservation 
                         WHERE client_id = ?
                         AND res_status IN ('" . implode("','", $allowedStatuses) . "')";
                         
    if (!empty($clearedIds['reservation'])) {
        $reservationQuery .= " AND id NOT IN (" . implode(',', $clearedIds['reservation']) . ")";
    }
    
    $reservationQuery .= " ORDER BY date_created DESC LIMIT $limit";
    $reservationStmt = $conn->prepare($reservationQuery);
    $reservationStmt->bind_param("i", $userId);
    $reservationStmt->execute();
    $reservationResult = $reservationStmt->get_result();
    
    while ($row = $reservationResult->fetch_assoc()) {
        $notifications[] = [
            'type' => 'reservation',
            'id' => $row['id'],
            'name' => $row['clientFullName'],
            'status' => $row['res_status'],
            'time' => $row['date_created'],
            'is_read' => in_array($row['id'], $_SESSION['read_notifications']['reservation'] ?? [])
        ];
    }
    
    // Sort by newest first
    usort($notifications, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });
    
    return array_slice($notifications, 0, $limit);
}

// Handle AJAX actions
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'mark_read':
            if (!isset($_SESSION['read_notifications'][$_GET['type']])) {
                $_SESSION['read_notifications'][$_GET['type']] = [];
            }
            $_SESSION['read_notifications'][$_GET['type']][] = $_GET['id'];
            echo json_encode(['success' => true]);
            exit;
            
        case 'mark_all_read':
            $notifications = getLatestNotifications($conn, $userId, 100);
            $_SESSION['read_notifications'] = [
                'order' => array_merge(
                    $_SESSION['read_notifications']['order'] ?? [],
                    array_column(array_filter($notifications, function($n) { return $n['type'] === 'order'; }), 'id')
                ),
                'reservation' => array_merge(
                    $_SESSION['read_notifications']['reservation'] ?? [],
                    array_column(array_filter($notifications, function($n) { return $n['type'] === 'reservation'; }), 'id')
                )
            ];
            echo json_encode(['success' => true]);
            exit;
            
        case 'clear_all':
            $notifications = getLatestNotifications($conn, $userId, 100);
            $_SESSION['cleared_notifications'] = [
                'order' => array_merge(
                    $_SESSION['cleared_notifications']['order'] ?? [],
                    array_column(array_filter($notifications, function($n) { return $n['type'] === 'order'; }), 'id')
                ),
                'reservation' => array_merge(
                    $_SESSION['cleared_notifications']['reservation'] ?? [],
                    array_column(array_filter($notifications, function($n) { return $n['type'] === 'reservation'; }), 'id')
                )
            ];
            echo json_encode(['success' => true]);
            exit;
            
        case 'refresh':
            $notifications = getLatestNotifications($conn, $userId, 10);
            $unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));
            
            ob_start();
            if (empty($notifications)): ?>
                <div class="no-notifications">No new notifications</div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>" 
                         data-type="<?= $notification['type'] ?>" 
                         data-id="<?= $notification['id'] ?>"
                         data-url="<?= $notification['type'] === 'reservation' ? 'reservation_track.php?id=' . $notification['id'] : 'order-track.php?order_id=' . $notification['id'] ?>">
                        <div class="notification-message">
                            <?= htmlspecialchars($notification['name']) ?>
                            <span class="notification-status"><?= htmlspecialchars($notification['status']) ?></span>
                        </div>
                        <div class="notification-meta">
                            <span><?= ucfirst($notification['type']) ?> #<?= $notification['id'] ?></span>
                            <span><?= date('M j, g:i A', strtotime($notification['time'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif;
            
            $content = ob_get_clean();
            echo json_encode([
                'success' => true,
                'content' => $content,
                'unreadCount' => $unreadCount
            ]);
            exit;
    }
}

$notifications = getLatestNotifications($conn, $userId, 10);
$unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <style>
        /* Notification Button */
        #notificationButton {
            position: relative;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
        }
        
        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: red;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Modal */
        .notification-modal {
            position: fixed;
            top: 60px;
            right: 20px;
            width: 400px;
            max-height: 70vh;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            display: none;
            flex-direction: column;
        }
        
        .notification-header {
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-actions button {
            background: none;
            border: none;
            color: #1976d2;
            cursor: pointer;
            margin-left: 10px;
            font-size: 12px;
        }
        
        .notification-list {
            overflow-y: auto;
            max-height: 60vh;
        }
        
        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .notification-item.unread {
            background:#FCE7D3;
        }
        
        .notification-item:hover {
            background: #f5f5f5;
        }
        
        .notification-message {
            font-weight: 500;
        }
        
        .notification-meta {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
            display: flex;
            justify-content: space-between;
        }
        
        .notification-status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            background: #e0e0e0;
            font-size: 12px;
        }
        
        .no-notifications {
            padding: 20px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>

<!-- Notification Button -->
<button id="notificationButton">
    🔔 <span class="notification-badge"><?= $unreadCount ?></span>
</button>

<!-- Notification Modal -->
<div class="notification-modal" id="notificationModal">
    <div class="notification-header">
        <strong>Notifications</strong>
        <div class="notification-actions">
            <button id="markAllRead" style="color: #FF902B;" >Mark all as read</button>
            <button id="clearAll"  style="color: #FF902B;" >Clear all</button>
        </div>
    </div>
    <div class="notification-list" id="notificationList">
        <?php if (empty($notifications)): ?>
            <div class="no-notifications">No new notifications</div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>" 
                     data-type="<?= $notification['type'] ?>" 
                     data-id="<?= $notification['id'] ?>"
                     data-url="<?= $notification['type'] === 'reservation' ? 'reservation_track.php?id=' . $notification['id'] : 'order-track.php?order_id=' . $notification['id'] ?>">
                    <div class="notification-message">
                        <?= htmlspecialchars($notification['name']) ?>
                        <span class="notification-status"><?= htmlspecialchars($notification['status']) ?></span>
                    </div>
                    <div class="notification-meta">
                        <span><?= ucfirst($notification['type']) ?> #<?= $notification['id'] ?></span>
                        <span><?= date('M j, g:i A', strtotime($notification['time'])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    // Global refresh interval
    let refreshInterval = 3000; // 3 seconds
    let refreshTimer;
    let isModalOpen = false;
    
    // Toggle modal
    const notificationButton = document.getElementById('notificationButton');
    const modal = document.getElementById('notificationModal');
    
    notificationButton.addEventListener('click', (e) => {
        e.stopPropagation();
        isModalOpen = !isModalOpen;
        modal.style.display = isModalOpen ? 'flex' : 'none';
        
        if (isModalOpen) {
            // Refresh immediately when opening
            refreshNotifications();
            // Start auto-refresh
            startAutoRefresh();
        } else {
            // Stop auto-refresh when closing
            stopAutoRefresh();
        }
    });
    
    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (!modal.contains(e.target) && e.target !== notificationButton) {
            isModalOpen = false;
            modal.style.display = 'none';
            stopAutoRefresh();
        }
    });
    
    // Auto-refresh control
    function startAutoRefresh() {
        stopAutoRefresh();
        refreshTimer = setInterval(refreshNotifications, refreshInterval);
    }
    
    function stopAutoRefresh() {
        if (refreshTimer) {
            clearInterval(refreshTimer);
        }
    }
    
    // Refresh notifications
    async function refreshNotifications() {
        try {
            const response = await fetch('?action=refresh');
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('notificationList').innerHTML = data.content;
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    badge.textContent = data.unreadCount;
                }
                
                // Reattach click handlers
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.addEventListener('click', handleNotificationClick);
                });
            }
        } catch (error) {
            console.error('Refresh failed:', error);
        }
    }
    
    // Handle notification click
    async function handleNotificationClick() {
        const url = this.dataset.url;
        
        // Mark as read visually
        this.classList.remove('unread');
        
        // Update server
        try {
            await fetch(`?action=mark_read&type=${this.dataset.type}&id=${this.dataset.id}`);
        } catch (error) {
            console.error('Error marking as read:', error);
        }
        
        // Update badge count
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = Math.max(0, parseInt(badge.textContent) - 1);
        }
        
        // Redirect
        window.location.href = url;
    }
    
    // Mark all as read
    document.getElementById('markAllRead').addEventListener('click', async () => {
        document.querySelectorAll('.notification-item').forEach(item => {
            item.classList.remove('unread');
        });
        
        // Update badge
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = '0';
        }
        
        // Update server
        try {
            await fetch('?action=mark_all_read');
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    });
    
    // Clear all
    document.getElementById('clearAll').addEventListener('click', async () => {
        document.getElementById('notificationList').innerHTML = `
            <div class="no-notifications">No new notifications</div>
        `;
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = '0';
        }
        
        // Update server
        try {
            await fetch('?action=clear_all');
        } catch (error) {
            console.error('Error clearing all:', error);
        }
    });
    
    // Initial click handlers
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', handleNotificationClick);
    });
</script>

</body>
</html>