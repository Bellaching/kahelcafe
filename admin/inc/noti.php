<?php

include __DIR__ . '/../../connection/connection.php';

// Initialize session storage if not exists
if (!isset($_SESSION['read_notifications'])) {
    $_SESSION['read_notifications'] = [
        'order' => [],
        'reservation' => []
    ];
}

if (!isset($_SESSION['cleared_notifications'])) {
    $_SESSION['cleared_notifications'] = [
        'order' => [],
        'reservation' => []
    ];
}

// Function to get latest notifications (excluding cleared ones)
function getLatestNotifications($conn, $limit = 10) {
    $notifications = [];
    
    // Get orders (excluding cleared and specific statuses)
    $orderQuery = "SELECT order_id, client_full_name, status, last_updated 
                   FROM orders 
                   WHERE status NOT IN ('completed', 'booked', 'rate us')";
                   
    if (!empty($_SESSION['cleared_notifications']['order'])) {
        $orderQuery .= " AND order_id NOT IN (" . implode(',', array_map('intval', $_SESSION['cleared_notifications']['order'])) . ")";
    }
    
    $orderQuery .= " ORDER BY last_updated DESC LIMIT $limit";
    $orderResult = mysqli_query($conn, $orderQuery);
    
    if ($orderResult) {
        while ($row = mysqli_fetch_assoc($orderResult)) {
            $notifications[] = [
                'type' => 'order',
                'id' => $row['order_id'],
                'name' => $row['client_full_name'],
                'status' => $row['status'],
                'time' => $row['last_updated'],
                'is_read' => in_array($row['order_id'], $_SESSION['read_notifications']['order'] ?? [])
            ];
        }
    } else {
        error_log("Order query failed: " . mysqli_error($conn));
    }
    
    // Get reservations
    $reservationQuery = "SELECT id, clientFullName, res_status, date_created 
                         FROM reservation 
                         WHERE res_status NOT IN ('completed', 'booked', 'rate us')";
                         
    if (!empty($_SESSION['cleared_notifications']['reservation'])) {
        $reservationQuery .= " AND id NOT IN (" . implode(',', array_map('intval', $_SESSION['cleared_notifications']['reservation'])) . ")";
    }
    
    $reservationQuery .= " ORDER BY date_created DESC LIMIT $limit";
    $reservationResult = mysqli_query($conn, $reservationQuery);
    
    if ($reservationResult) {
        while ($row = mysqli_fetch_assoc($reservationResult)) {
            $notifications[] = [
                'type' => 'reservation',
                'id' => $row['id'],
                'name' => $row['clientFullName'],
                'status' => $row['res_status'],
                'time' => $row['date_created'],
                'is_read' => in_array($row['id'], $_SESSION['read_notifications']['reservation'] ?? [])
            ];
        }
    } else {
        error_log("Reservation query failed: " . mysqli_error($conn));
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
            if (isset($_GET['type']) && isset($_GET['id'])) {
                $type = $_GET['type'];
                $id = (int)$_GET['id'];
                
                if (!in_array($id, $_SESSION['read_notifications'][$type] ?? [])) {
                    $_SESSION['read_notifications'][$type][] = $id;
                }
            }
            echo json_encode(['success' => true]);
            exit;
            
        case 'mark_all_read':
            $notifications = getLatestNotifications($conn, 100);
            foreach ($notifications as $notification) {
                $type = $notification['type'];
                $id = $notification['id'];
                
                if (!in_array($id, $_SESSION['read_notifications'][$type] ?? [])) {
                    $_SESSION['read_notifications'][$type][] = $id;
                }
            }
            echo json_encode(['success' => true]);
            exit;
            
        case 'clear_all':
            $notifications = getLatestNotifications($conn, 100);
            foreach ($notifications as $notification) {
                $type = $notification['type'];
                $id = $notification['id'];
                
                if (!in_array($id, $_SESSION['cleared_notifications'][$type] ?? [])) {
                    $_SESSION['cleared_notifications'][$type][] = $id;
                }
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
            
            $content = ob_get_clean();
            echo json_encode([
                'success' => true,
                'content' => $content,
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
      width: 90%;
      max-width: 400px;
      max-height: 70vh;
      background: white;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
<body class="bg-light">

  <div class="container mt-4">
    <!-- Notification Button -->
    <div class="d-flex justify-content-end">
      <button id="notificationButton">
        🔔 <span class="notification-badge"><?= $unreadCount ?></span>
      </button>
    </div>

    <!-- Notification Modal -->
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
        const type = this.dataset.type;
        const id = this.dataset.id;
        
        // Mark as read visually
        this.classList.remove('unread');
        
        // Update server
        await fetch(`?action=mark_read&type=${type}&id=${id}`);
        
        // Update badge count
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = Math.max(0, parseInt(badge.textContent) - 1);
        }
        
        // Redirect
        if (type === 'reservation') {
            window.location.href = `reservation.php?id=${id}`;
        } else {
            window.location.href = `index.php?order_id=${id}`;
        }
    }
    
    // Mark all as read
    document.getElementById('markAllRead').addEventListener('click', async (e) => {
        e.stopPropagation();
        document.querySelectorAll('.notification-item').forEach(item => {
            item.classList.remove('unread');
        });
        
        // Update badge
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = '0';
        }
        
        // Update server
        await fetch('?action=mark_all_read');
    });
    
    // Clear all
    document.getElementById('clearAll').addEventListener('click', async (e) => {
        e.stopPropagation();
        document.getElementById('notificationList').innerHTML = `
            <div class="no-notifications">No new notifications</div>
        `;
        
        // Update badge
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = '0';
        }
        
        // Update server
        await fetch('?action=clear_all');
    });
    
    // Initial click handlers
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', handleNotificationClick);
    });
</script>

</body>
</html>