<?php
include './../../connection/connection.php';
 
// Get the action
$action = isset($_POST['action']) ? $_POST['action'] : '';


if ($action === 'check_updates') {
    $lastTimestamp = $_POST['last_timestamp'] ?? null;
    
    // Get the latest timestamp from orders
    $query = "SELECT MAX(updated_at) as latest_timestamp FROM orders";
    $result = $conn->query($query);
    
    if (!$result) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $conn->error
        ]);
        exit();
    }
    
    $row = $result->fetch_assoc();
    $currentTimestamp = $row['latest_timestamp'];
    
    // If we don't have a last timestamp, assume there are updates
    $hasUpdates = ($lastTimestamp === null) ? true : ($currentTimestamp > $lastTimestamp);
    
    echo json_encode([
        'success' => true,
        'has_updates' => $hasUpdates,
        'current_timestamp' => $currentTimestamp,
        'message' => $hasUpdates ? 'Updates available' : 'No updates'
    ]);
    exit();
}
if ($action === 'get_latest_timestamp') {
    $query = "SELECT MAX(updated_at) as latest_timestamp FROM orders";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'timestamp' => $row['latest_timestamp']
    ]);
    exit();
}

if ($action === 'read') {
    $sort = isset($_POST['sort']) && $_POST['sort'] === 'desc' ? 'DESC' : 'ASC';
    
    $query = "SELECT order_id, client_full_name, created_at, transaction_id, total_price, reservation_time, reservation_type, status, reservation_fee
              FROM orders 
              ORDER BY created_at $sort";
              
    $result = $conn->query($query);
    $orders = [];

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    echo json_encode($orders);
    exit();  
}

if ($action === 'getOrderItems') {
    $orderId = $_POST['order_id'] ?? null;

    if (!$orderId) {
        echo json_encode(['success' => false, 'message' => 'Order ID is missing']);
        exit();
    }

    $query = "
        SELECT oi.item_name, oi.price, oi.size, oi.temperature, oi.quantity, 
               (SELECT receipt FROM order_items WHERE order_id = o.order_id AND receipt IS NOT NULL LIMIT 1) as receipt,
               o.client_full_name, o.transaction_id, o.reservation_type, o.reservation_fee, o.created_at, o.total_price
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        WHERE oi.order_id = ?
        GROUP BY oi.id
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $orderId);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $items = [];

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        echo json_encode(['success' => true, 'items' => $items, 'receipt' => $items[0]['receipt'] ?? null]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch items: ' . $conn->error]);
    }

    $stmt->close();
    exit();
}

if ($action === 'update') {
    $id = (int)$_POST['id'];
    $status = $conn->real_escape_string($_POST['status']);
    
    // Update with current timestamp
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?");
    $stmt->bind_param('si', $status, $id);

    if ($stmt->execute()) {
        // Get user_id for notification
        $userQuery = "SELECT user_id FROM orders WHERE order_id = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->bind_param('i', $id);
        $userStmt->execute();
        $userStmt->bind_result($userId);
        $userStmt->fetch();
        $userStmt->close();

        echo json_encode(['success' => true, 'status' => $status]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
    }

    $stmt->close();
    exit();
}

if ($action === 'delete') {
    $id = (int)$_POST['id']; // Ensure ID is integer

    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $conn->error]);
    }

    $stmt->close();
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Orders Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
    body{
        display: flex;
        flex-direction: column;
    }
    .text-truncate {
      position: relative;
    }
    .text-truncate:hover::after {
      content: attr(data-fulltext);
      position: absolute;
      left: 0;
      top: 100%;
      z-index: 1000;
      background: #333;
      color: white;
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 14px;
      white-space: nowrap;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .editBtn,
    .deleteBtn {
        background: none;
        border: none;
    }
    .editBtn {
        color: #624DE3;
    }
    .deleteBtn {
        color: #A30D11;
    }
    .account-text {
        font-size: 2rem;
        font-weight: bold;
    }
    </style>
</head>

<body>
<?php include './../inc/topNav.php'; ?>
<div class="container-fluid px-3">
  <div class="position-fixed" style="bottom: 20px; right: 20px; background-color: #4CAF50; color: white; padding: 15px; border-radius: 5px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); z-index: 1000; display: none;" id="updateNotification">
    New Update Order
  </div>

  <div class="row">
    <div class="col-12 col-md-10 mx-auto">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-5 text-center text-md-start gap-3">
        <div>
          <p class="account-text m-0">
            Order <span style="text-decoration: underline; text-decoration-color: #FF902B; text-underline-offset: 8px;">Management</span>
          </p>
        </div>
        <div>
          <?php include "./../../admin/views/qr_reader.php" ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="px-5">
<div class="d-flex justify-content-center w-100 mb-5 ">
    <div class="container-fluid shadow p-3" style="margin: 0 1.5rem; background-color: #f8f9fa; border-radius: 5px;">
        <div class="table-responsive">
            <table id="ordersTable" class="display w-100">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Order Date</th>
                        <th>Reservation Time</th>
                        <th>Amount</th>
                        <th>Reservation Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Table data will be filled dynamically -->
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<!-- Modal for Update Status -->
<div class="modal fade" id="updateUserModal" tabindex="-1" role="dialog" aria-labelledby="updateUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #FF902B; color: white;">
                <h5 class="modal-title">Update Order Status</h5>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-xl-8 col-lg-7 pr-lg-3">
                            <div class="card border-0">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0" style="color: #FF902B; font-weight: bold;">Order Items</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-borderless mb-0" id="orderItemsTable">
                                            <thead>
                                                <tr>
                                                    <th class="text-nowrap">Item Name</th>
                                                    <th class="text-nowrap">Price</th>
                                                    <th class="text-nowrap">Size</th>
                                                    <th class="text-nowrap">Temperature</th>
                                                    <th class="text-nowrap">Qty</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Items will be loaded here dynamically -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <div id="receiptContainer" class="mt-3 p-3 border rounded bg-light" style="display: none;">
                                        <div class="font-weight-bold mb-2">Payment Receipt:</div>
                                        <div id="receiptContent" class="text-center">
                                            <!-- Image will be inserted here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-5 pl-lg-3 mt-lg-0 mt-3">
                            <div class="card border-0">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0" style="color: #FF902B; font-weight: bold;">Order Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between mb-3">
                                                <span class="font-weight-medium">Client Name</span>
                                                <span id="client_full_name_display" class="text-muted text-truncate ml-2" style="max-width: 150px;"></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span class="font-weight-medium">Transaction no.</span>
                                                <span id="transaction_id" class="text-muted text-truncate ml-2" style="max-width: 150px;"></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span class="font-weight-medium">Reservation Type</span>
                                                <span id="reservation_type" class="text-muted text-truncate ml-2" style="max-width: 150px;"></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span class="font-weight-medium">Date</span>
                                                <span id="created_at" class="text-muted text-truncate ml-2" style="max-width: 150px;"></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span class="font-weight-medium">Reservation Time</span>
                                                <span id="reservation_time" class="text-muted text-truncate ml-2" style="max-width: 150px;"></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span class="font-weight-medium">Reservation Charges</span>
                                                <span id="subTotal" class="text-muted">₱50.00</span>
                                            </div>
                                        </div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <span class="font-weight-bold" style="font-size: 1.1rem;">Total Price</span>
                                        <span id="total_price1" class="font-weight-bold" style="color: #FF902B;"></span>
                                    </div>
                                    <div class="form-group mt-4 mb-2">
                                        <label for="status" class="font-weight-bold">Order Status</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="for confirmation">For Confirmation</option>
                                            <option value="payment">Payment</option>
                                            <option value="Paid">Paid</option>
                                            <option value="booked">Booked</option>
                                            <option value="rate us">Rate Us</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="orderId" name="id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn" id="saveStatusBtn" style="color: white; background-color: #FF902B; border: none; padding: 10px 25px; border-radius: 50px; font-weight: 500;">Update Status</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Delete Confirmation -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #FF902B; color: white;">
                <h5 class="modal-title" id="deleteUserModalLabel">Delete Confirmation</h5>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this order? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    let lastUpdateTimestamp = null;
    let isFirstLoad = true;
    const table = initializeDataTable();
    setupEventHandlers(table);
    
    function showUpdateNotification() {
        console.log('Showing update notification');
        const notification = $('#updateNotification');
        notification.fadeIn();
        setTimeout(() => {
            notification.fadeOut();
        }, 3000);
    }
    
    function checkForUpdates() {
        console.log('Checking for updates. Last timestamp:', lastUpdateTimestamp);
        
        $.ajax({
            url: '',
            type: 'POST',
            data: { 
                action: 'check_updates',
                last_timestamp: lastUpdateTimestamp
            },
            success: function(response) {
                console.log('Update check response:', response);
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        console.log('Current timestamp:', result.current_timestamp);
                        if (result.has_updates) {
                            console.log('Updates detected!');
                            lastUpdateTimestamp = result.current_timestamp;
                            if (!isFirstLoad) {
                                showUpdateNotification();
                            }
                            table.ajax.reload(null, false);
                            isFirstLoad = false;
                        } else {
                            console.log('No updates found');
                        }
                    }
                } catch (e) {
                    console.error('Error parsing update check response:', e, 'Response:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Update check error:', error);
            },
            complete: function() {
                setTimeout(checkForUpdates, 3000);
            }
        });
    }
    
    function initializeTimestamp() {
        console.log('Initializing timestamp...');
        $.ajax({
            url: '',
            type: 'POST',
            data: { 
                action: 'get_latest_timestamp'
            },
            success: function(response) {
                console.log('Timestamp init response:', response);
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        lastUpdateTimestamp = result.timestamp;
                        console.log('Initial timestamp set to:', lastUpdateTimestamp);
                        setTimeout(checkForUpdates, 3000);
                    }
                } catch (e) {
                    console.error('Error parsing timestamp response:', e, 'Response:', response);
                    setTimeout(initializeTimestamp, 5000);
                }
            },
            error: function(xhr, status, error) {
                console.error('Timestamp initialization error:', error);
                setTimeout(initializeTimestamp, 5000);
            }
        });
    }
    
    initializeTimestamp();
});

let table;

function initializeDataTable() {
    table = $('#ordersTable').DataTable({
        ajax: {
            url: '',
            type: 'POST',
            data: { 
                action: 'read',
                sort: 'desc'
            },
            dataSrc: ''
        },
        order: [[2, 'desc']],
        columns: [
            { data: 'order_id' },
            { data: 'client_full_name' },
            {
                data: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleDateString('en-US');
                }  
            },
            { 
                data: 'reservation_time',
                render: function(data) {
                    return data || 'N/A';
                }
            },
            { 
                data: 'total_price',
                render: function(data) {
                    const price = parseFloat(data) || 0;
                    return '₱' + price.toFixed(2);
                }
            },
            { data: 'reservation_type' },
            {
                data: 'status',
                render: function(data) {
                    const statusMap = {
                        "for confirmation": '<span style="padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; background-color: #17a2b8; color: white;">For Confirmation</span>',
                        "cancelled": '<span style="padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; background-color: #dc3545; color: white;">Cancelled</span>',
                        "payment": '<span style="padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; background-color: #ffc107; color: #212529;">Payment</span>',
                        "paid": '<span style="padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; background-color: #28a745; color: white;">Paid</span>',
                        "booked": '<span style="padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; background-color: #007bff; color: white;">Booked</span>',
                        "rate us": '<span style="padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; background-color: #6c757d; color: white;">Rate Us</span>',
                    };
                    return statusMap[data.toLowerCase()] || data;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `
                        <div class="d-flex">
                            <button class="editBtn"
                                data-id="${row.order_id}" 
                                data-status="${row.status}" 
                                data-client-name="${row.client_full_name}" 
                                data-reservation-type="${row.reservation_type}" 
                                data-total-price="${row.total_price}"
                                data-created-at="${row.created_at}"
                                data-reservation-time="${row.reservation_time}"
                                data-transaction-id="${row.transaction_id}">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                            <button class="deleteBtn" style="padding: 5px 10px;" 
                                data-id="${row.order_id}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        responsive: true
    });
    return table;
}

function setupEventHandlers(table) {
    $('#ordersTable').on('click', '.editBtn', function() {
        const orderId = $(this).data('id');
        const status = $(this).data('status');
        const clientName = $(this).data('client-name');
        const reservationType = $(this).data('reservation-type');
        const totalPrice = $(this).data('total-price');
        const createdAt = $(this).data('created-at');
        const reservationTime = $(this).data('reservation-time');
        const transactionId = $(this).data('transaction-id');

        $('#updateUserModal').modal('show');
        $('#orderId').val(orderId);
        $('#status').val(status);
        $('#client_full_name_display').text(clientName);
        $('#reservation_type').text(reservationType);
        $('#total_price1').text('₱' + parseFloat(totalPrice).toFixed(2));
        $('#created_at').text(new Date(createdAt).toLocaleDateString('en-US'));
        $('#transaction_id').text(transactionId);
        $('#reservation_time').text(reservationTime || 'N/A');

        loadOrderItems(orderId);
    });

    function loadOrderItems(orderId) {
        $.ajax({
            url: '',
            type: 'POST',
            data: { 
                action: 'getOrderItems',
                order_id: orderId
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        const items = result.items;
                        let itemsHtml = '';
                        let itemsTotal = 0;
                        const additionalCharges = 50;
                        
                        items.forEach(item => {
                            const price = parseFloat(item.price) || 0;
                            const itemTotal = price * parseInt(item.quantity);
                            itemsTotal += itemTotal;
                            
                            itemsHtml += `
                                <tr>
                                    <td>${item.item_name}</td>
                                    <td>₱${price.toFixed(2)}</td>
                                    <td>${item.size || 'N/A'}</td>
                                    <td>${item.temperature || 'N/A'}</td>
                                    <td>${item.quantity}</td>
                                </tr>
                            `;
                        });
                        
                        $('#orderItemsTable tbody').html(itemsHtml);
                        $('#itemsTotal').text('₱' + itemsTotal.toFixed(2));
                        $('#subTotal').text('₱' + additionalCharges.toFixed(2));
                        const finalTotal = itemsTotal + additionalCharges;
                        $('#total_price1').text('₱' + finalTotal.toFixed(2));
                        
                        const receiptContent = $('#receiptContent');
                        const receiptContainer = $('#receiptContainer');
                        receiptContent.empty();
                        
                        if (result.receipt && result.receipt.trim() !== '') {
                            const receiptPath = './../../uploads/receipts/' + result.receipt;
                            
                            receiptContent.html(`
                                <img src="${receiptPath}" 
                                     style="max-width: 100%; max-height: 300px; margin-top: 10px;" 
                                     alt="Payment Receipt"
                                     onerror="this.style.display='none'">
                                <div style="margin-top: 5px;">
                                    <a href="${receiptPath}" target="_blank">View Full Image</a>
                                </div>
                            `);
                            receiptContainer.show();
                        } else {
                            receiptContainer.hide();
                        }
                    } else {
                        console.error('Failed to load order items:', result.message || 'Unknown error');
                        $('#receiptContainer').hide();
                    }
                } catch (e) {
                    console.error('Error parsing order items response:', e);
                    $('#receiptContainer').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading order items:', error);
                $('#receiptContainer').hide();
            }
        });
    }
    
    $('#saveStatusBtn').on('click', function() {
        const orderId = $('#orderId').val();
        const status = $('#status').val();
    
        $.ajax({
            url: '',
            type: 'POST',
            data: {
                action: 'update',
                id: orderId,
                status: status
            },
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    $('#updateUserModal').modal('hide');
                    table.ajax.reload(null, false);
                    alert('Status updated successfully');
                } else {
                    alert('Error updating status: ' + (result.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while updating the status: ' + error);
            }
        });
    });

    $('#ordersTable').on('click', '.deleteBtn', function() {
        const orderId = $(this).data('id');
        $('#deleteUserModal').modal('show');
        
        $('#confirmDeleteBtn').off('click').on('click', function() {
            deleteOrder(orderId, table);
        });
    });
}

function deleteOrder(orderId, table) {
    $.ajax({
        url: '',
        type: 'POST',
        data: { action: 'delete', id: orderId },
        success: function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                $('#deleteUserModal').modal('hide');
                alert('Order deleted successfully.');
                table.ajax.reload(null, false);
            } else {
                alert('Failed to delete order: ' + (data.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            alert('An error occurred while deleting the order: ' + error);
        }
    });
}
</script>

</body>
</html>