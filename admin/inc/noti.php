<?php


include __DIR__ . '/../../connection/connection.php';


function getLatestNotifications($conn, $limit = 10) {
    $notifications = [];
    
    // Get orders (excluding cleared and specific statuses)
    $orderQuery = "SELECT order_id, client_full_name, status, last_updated 
                   FROM orders 
                   WHERE status NOT IN ('completed', 'booked', 'rate us')";
                   
    if (!empty($_SESSION['cleared_notifications']['order'])) {
        $orderQuery .= " AND order_id NOT IN (" . implode(',', $_SESSION['cleared_notifications']['order']) . ")";
    }
    
    $orderQuery .= " ORDER BY last_updated DESC LIMIT $limit";
    $orderResult = mysqli_query($conn, $orderQuery);
    
    while ($row = mysqli_fetch_assoc($orderResult)) {
        $notifications[] = [
            'type' => 'order',
            'id' => $row['order_id'],
            'name' => $row['client_full_name'],
            'status' => $row['status'],
            'time' => $row['last_updated'],
            'is_read' => in_array($row['order_id'], $_SESSION['read_notifications']['order'])
        ];
    }
    
    // Get reservations
    $reservationQuery = "SELECT id, clientFullName, res_status, date_created 
                         FROM reservation 
                         WHERE res_status NOT IN ('completed', 'booked', 'rate us')";
                         
    if (!empty($_SESSION['cleared_notifications']['reservation'])) {
        $reservationQuery .= " AND id NOT IN (" . implode(',', $_SESSION['cleared_notifications']['reservation']) . ")";
    }
    
    $reservationQuery .= " ORDER BY date_created DESC LIMIT $limit";
    $reservationResult = mysqli_query($conn, $reservationQuery);
    
    while ($row = mysqli_fetch_assoc($reservationResult)) {
        $notifications[] = [
            'type' => 'reservation',
            'id' => $row['id'],
            'name' => $row['clientFullName'],
            'status' => $row['res_status'],
            'time' => $row['date_created'],
            'is_read' => in_array($row['id'], $_SESSION['read_notifications']['reservation'])
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
            if (isset($_GET['type'], $_GET['id'])) {
                $_SESSION['read_notifications'][$_GET['type']][] = $_GET['id'];
            }
            echo json_encode(['success' => true]);
            exit;
            
        case 'mark_all_read':
            $latest = getLatestNotifications($conn, 100);
            foreach ($latest as $notification) {
                $_SESSION['read_notifications'][$notification['type']][] = $notification['id'];
            }
            echo json_encode(['success' => true]);
            exit;
            
        case 'clear_all':
            $latest = getLatestNotifications($conn, 100);
            foreach ($latest as $notification) {
                $_SESSION['cleared_notifications'][$notification['type']][] = $notification['id'];
            }
            echo json_encode(['success' => true]);
            exit;
            
        case 'refresh':
            $notifications = getLatestNotifications($conn, 10);
            $unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));
            
            ob_start();
            if (empty($notifications)): ?>
                <div class="no-notifications">No new notifications</div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>" 
                         data-type="<?= $notification['type'] ?>" 
                         data-id="<?= $notification['id'] ?>">
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
            
            echo json_encode([
                'success' => true,
                'content' => ob_get_clean(),
                'unreadCount' => $unreadCount
            ]);
            exit;
    }
}

$notifications = getLatestNotifications($conn, 10);
$unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <style>
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
            background: #e3f2fd;
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
<button id="notificationButton">
    🔔 <span class="notification-badge"><?= $unreadCount ?></span>
</button>

<div class="notification-modal" id="notificationModal">
    <div class="notification-header">
        <strong>Notifications</strong>
        <div class="notification-actions">
            <button id="markAllRead">Mark all read</button>
            <button id="clearAll">Clear all</button>
        </div>
    </div>
    <div class="notification-list" id="notificationList">
        <?php if (empty($notifications)): ?>
            <div class="no-notifications">No new notifications</div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>" 
                     data-type="<?= $notification['type'] ?>" 
                     data-id="<?= $notification['id'] ?>">
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
    const refreshInterval = 3000;
    let refreshTimer;
    let isModalOpen = false;
    
    const notificationButton = document.getElementById('notificationButton');
    const modal = document.getElementById('notificationModal');
    
    notificationButton.addEventListener('click', (e) => {
        e.stopPropagation();
        isModalOpen = !modal.style.display || modal.style.display === 'none';
        modal.style.display = isModalOpen ? 'flex' : 'none';
        
        if (isModalOpen) {
            refreshNotifications();
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });
    
    document.addEventListener('click', (e) => {
        if (!modal.contains(e.target) && e.target !== notificationButton) {
            modal.style.display = 'none';
            stopAutoRefresh();
        }
    });
    
    function startAutoRefresh() {
        stopAutoRefresh();
        refreshTimer = setInterval(refreshNotifications, refreshInterval);
    }
    
    function stopAutoRefresh() {
        clearInterval(refreshTimer);
    }
    
    async function refreshNotifications() {
        try {
            const response = await fetch('?action=refresh');
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('notificationList').innerHTML = data.content;
                document.querySelector('.notification-badge').textContent = data.unreadCount;
                attachClickHandlers();
            }
        } catch (error) {
            console.error('Refresh failed:', error);
        }
    }
    
    async function handleNotificationClick() {
        const type = this.dataset.type;
        const id = this.dataset.id;
        
        this.classList.remove('unread');
        await fetch(`?action=mark_read&type=${type}&id=${id}`);
        
        const badge = document.querySelector('.notification-badge');
        badge.textContent = Math.max(0, parseInt(badge.textContent) - 1);
        
        window.location.href = type === 'reservation' 
            ? `reservation.php?id=${id}` 
            : `index.php?order_id=${id}`;
    }
    
    document.getElementById('markAllRead').addEventListener('click', async () => {
        document.querySelectorAll('.notification-item').forEach(item => {
            item.classList.remove('unread');
        });
        document.querySelector('.notification-badge').textContent = '0';
        await fetch('?action=mark_all_read');
    });
    
    document.getElementById('clearAll').addEventListener('click', async () => {
        document.getElementById('notificationList').innerHTML = 
            '<div class="no-notifications">No new notifications</div>';
        document.querySelector('.notification-badge').textContent = '0';
        await fetch('?action=clear_all');
    });
    
    function attachClickHandlers() {
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', handleNotificationClick);
        });
    }
    
    attachClickHandlers();
</script>
</body>
</html>