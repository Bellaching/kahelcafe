<?php 
include './../../connection/connection.php';
include './../inc/topNav.php';
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management</title>
    <link href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }

        .modal-body {
            display: flex; 
            flex-direction: column;
        }

        .update-down {
            display: flex;
            flex-direction: column;
        }

        .order-sum {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
        }

        .order-s {
            color: #FF902B;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .p1 {
            color: #FF902B;
            font-weight: bold;
        }

        .p {
            color: #616161;
        }

        .l {
            color: #000000;
            font-weight: 500;
        }

        .upsta {
            color: white;
            background-color: #FF902B;
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 500;
        }

        .theadmodal {
            background-color: #FF902B;
            color:#FF902B;
        }

        .account-text {
            font-size: 2rem;
            font-weight: bold;
        }

        .management-underline {
            text-decoration: underline;
            text-decoration-color: #FF902B;
            text-underline-offset: 8px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .action-btns .btn {
            padding: 5px 10px;
            margin: 0 3px;
        }

        .items-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .items-table th {
            background-color: #f8f9fa;
            padding: 10px;
            text-align: left;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }

        .receipt-container {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .receipt-title {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .receipt-image {
            max-width: 100%;
            max-height: 300px;
            margin-top: 10px;
        }

        .modal-content {
            background-color: white;
            color: white;
        }
        
      

        .order-details-section {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
            background-color: #f5f5f5;
        }

        @media (max-width: 768px) {
            .account-text {
                font-size: 1.5rem;
            }
            
            .order-sum {
                flex-direction: column;
            }
            
            .modal-dialog {
                margin: 0.5rem;
            }

            .modal-content {
                padding: 1rem;
            }

            .update-down {
                flex-direction: column;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .modal-body {
                padding: 1rem;
            }

            .modal-footer {
                flex-direction: column;
                align-items: center;
            }

            .modal-footer .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .action-btns .btn {
                padding: 3px 6px;
                margin: 0 2px;
                font-size: 0.8rem;
            }

            .items-table th, 
            .items-table td {
                padding: 8px 5px;
                font-size: 0.9rem;
            }
        }
        
        /* Notification styles */
        .update-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
        }
    </style>
</head>

<body>

<!-- Update notification -->
<div class="update-notification" id="updateNotification">
    New orders available. Updating...
</div>

<div class="container-fluid mb-3">
    <div class="row mt-5 ms-lg-5 ms-md-3 ms-sm-0">
        <div class="col-12 col-md-10 col-lg-8">
            <p class="account-text">
                Order <span class="management-underline">Management</span>
            </p>
        </div>
    </div>
</div>

<div class="d-flex justify-content-center w-100">
    <div class="container-fluid shadow p-3 mx-lg-5 mx-md-3 mx-sm-1 bg-body-tertiary rounded">
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

<!-- Modal for Update Status -->
<div class="modal fade container-fluid" id="updateUserModal" tabindex="-1" role="dialog" aria-labelledby="updateUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content container-fluid">
            <div class="modal-header">
                <h5 class="modal-title">Update Order Status</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body container-fluid bg-white">
                <div class="order-details-section bg-white">
                    <h5 class="order-s mb-3">Order Items</h5>
                    <div class="table-responsive bg-white">
                        <table class="table items-table bg-white" id="orderItemsTable">
                            <thead class="theadmodal bg-white">
                                <tr class="bg-white"></tr>
                                    <th>Item Name</th>
                                    <th>Price</th>
                                    <th>Size</th>
                                    <th>Temperature</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Items will be loaded here dynamically -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div id="receiptContainer" class="receipt-container">
                        <div class="receipt-title">Payment Receipt:</div>
                        <div id="receiptContent"></div>
                    </div>
                </div>

                <div class="form-group update-down d-flex flex-column">
                    <h5 class="order-s">Order Summary</h5>
                    <div class="form-group order-sum d-flex flex-row flex-wrap">
                        <div class="d-flex flex-column gap-3 m-3 col-md-5">
                            <div class="d-flex flex-row mb-3 align-items-baseline">
                                <label class="l mr-5">Client Name</label>
                                <p id="client_full_name_display" class="p mb-0 ml-auto text-right"></p>
                            </div>

                            <div class="d-flex flex-row mb-3 align-items-baseline">
                                <label class="l mr-5">Transaction no.</label>
                                <p id="transaction_id" class="p mb-0 ml-auto text-right"></p>
                            </div>

                            <div class="d-flex flex-row mb-3 align-items-baseline">
                                <label class="l mr-5">Reservation Type</label>
                                <p id="reservation_type" class="p mb-0 ml-auto text-right"></p>
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-3 m-3 col-md-5">
                            <div class="d-flex flex-row mb-3 align-items-baseline">
                                <label class="l mr-5">Date</label>
                                <p id="created_at" class="p mb-0 ml-auto text-right"></p>
                            </div>

                            <div class="d-flex flex-row mb-3 align-items-baseline">
                                <label class="l mr-5">Reservation Time</label>
                                <p id="reservation_time" class="p mb-0 ml-auto text-right"></p>
                            </div>

                            <div class="d-flex flex-row mb-1 align-items-baseline mt-2">
                                <label class="l mr-5 fs-5 bold">Total Price</label>
                                <p id="total_price1" class="p1 mb-0 ml-auto bold text-right"></p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group p-3 mb-3 mx-lg-5 mx-md-3 mx-sm-1">
                        <label for="status" class="fw-bold">Order Status</label>
                        <select class="form-control container-fluid" id="status" name="status">
                            <option value="for confirmation">For Confirmation</option>
                            <option value="payment">Payment</option>
                            <option value="PAID">Paid</option>
                            <option value="booked">Booked</option>
                            <option value="rate us">Rate Us</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <input type="hidden" id="orderId" name="id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn upsta" id="saveStatusBtn">Update Status</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Delete Confirmation -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: white; color: white;">
                <h5 class="modal-title" id="deleteUserModalLabel">Delete Confirmation</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this order? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>

<script>
$(document).ready(function() {
    // Variable to track the last update timestamp
    let lastUpdateTimestamp = null;
    let isFirstLoad = true;
    const table = initializeDataTable();
    setupEventHandlers(table);
    
    // Function to show update notification
    function showUpdateNotification() {
        const notification = $('#updateNotification');
        notification.fadeIn();
        setTimeout(() => {
            notification.fadeOut();
        }, 3000);
    }
    
    // Function to check for updates
    function checkForUpdates() {
        $.ajax({
            url: './../user/index.php',
            type: 'POST',
            data: { 
                action: 'check_updates',
                last_timestamp: lastUpdateTimestamp
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        if (result.has_updates) {
                            // Update the timestamp
                            lastUpdateTimestamp = result.current_timestamp;
                            
                            // Show notification if not first load
                            if (!isFirstLoad) {
                                showUpdateNotification();
                            }
                            
                            // Reload the table data
                            table.ajax.reload(null, false);
                            
                            isFirstLoad = false;
                        }
                    }
                } catch (e) {
                    console.error('Error parsing update check response:', e);
                }
            },
            error: function(xhr, status, error) {
                console.error('Update check error:', error);
            },
            complete: function() {
                // Schedule the next check
                setTimeout(checkForUpdates, 3000); // Check every 3 seconds
            }
        });
    }
    
    // Initialize the timestamp and start checking for updates
    initializeTimestamp();
    
    function initializeTimestamp() {
        $.ajax({
            url: './../user/index.php',
            type: 'POST',
            data: { 
                action: 'get_latest_timestamp'
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        lastUpdateTimestamp = result.timestamp;
                        // Start checking for updates
                        setTimeout(checkForUpdates, 3000);
                    }
                } catch (e) {
                    console.error('Error parsing timestamp response:', e);
                    // Retry after delay
                    setTimeout(initializeTimestamp, 5000);
                }
            },
            error: function(xhr, status, error) {
                console.error('Timestamp initialization error:', error);
                // Retry after delay
                setTimeout(initializeTimestamp, 5000);
            }
        });
    }
});

let table;

function initializeDataTable() {
    table = $('#ordersTable').DataTable({
        ajax: {
            url: './../user/index.php',
            type: 'POST',
            data: { 
                action: 'read',
                sort: 'desc'
            },
            dataSrc: ''
        },
        order: [[2, 'desc']], // Sort by order date (column 2) descending
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
                    return '₱' + parseFloat(data).toFixed(2);
                }
            },
            { data: 'reservation_type' },
            {
                data: 'status',
                render: function(data) {
                    const statusMap = {
                        "for confirmation": '<span class="status-badge bg-info">For Confirmation</span>',
                        "cancelled": '<span class="status-badge bg-danger">Cancelled</span>',
                        "payment": '<span class="status-badge bg-warning text-dark">Payment</span>',
                        "PAID": '<span class="status-badge bg-success">Paid</span>',
                        "booked": '<span class="status-badge bg-primary">Booked</span>',
                        "rate us": '<span class="status-badge bg-secondary">Rate Us</span>',
                    };
                    return statusMap[data.toLowerCase()] || data;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `
                        <div class="action-btns">
                            <button class="btn btn-sm btn-warning editBtn" data-id="${row.transaction_id}" 
                                data-status="${row.status}" 
                                data-client-name="${row.client_full_name}" 
                                data-reservation-type="${row.reservation_type}" 
                                data-total-price="${row.total_price}"
                                data-created-at="${row.created_at}"
                                data-reservation-time="${row.reservation_time}"
                                data-order-id="${row.order_id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="${row.transaction_id}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });
    return table;
}

function setupEventHandlers(table) {
    // Edit button handler - now loads order items and receipt
    $('#ordersTable').on('click', '.editBtn', function() {
        const orderId = $(this).data('id');
        const status = $(this).data('status');
        const clientName = $(this).data('client-name');
        const reservationType = $(this).data('reservation-type');
        const totalPrice = $(this).data('total-price');
        const createdAt = $(this).data('created-at');
        const reservationTime = $(this).data('reservation-time');
        const orderIdForItems = $(this).data('order-id');

        // Set basic order info
        $('#updateUserModal').modal('show');
        $('#orderId').val(orderId);
        $('#status').val(status);
        $('#client_full_name_display').text(clientName);
        $('#reservation_type').text(reservationType);
        $('#total_price1').text('₱' + parseFloat(totalPrice).toFixed(2));
        $('#created_at').text(new Date(createdAt).toLocaleDateString('en-US'));
        $('#transaction_id').text(orderId);
        $('#reservation_time').text(reservationTime || 'N/A');

        // Load order items and receipt
        loadOrderItems(orderIdForItems);
    });

    function loadOrderItems(orderId) {
        $.ajax({
            url: './../user/index.php',
            type: 'POST',
            data: { 
                action: 'getOrderItems',
                order_id: orderId
            },
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    const items = result.items;
                    let itemsHtml = '';
                    let totalPrice = 0;
                    
                    items.forEach(item => {
                        const subtotal = parseFloat(item.price) * parseInt(item.quantity);
                        totalPrice += subtotal;
                        
                        itemsHtml += `
                            <tr>
                                <td>${item.item_name}</td>
                                <td>₱${parseFloat(item.price).toFixed(2)}</td>
                                <td>${item.size || 'N/A'}</td>
                                <td>${item.temperature || 'N/A'}</td>
                                <td>${item.quantity}</td>
                                <td>₱${subtotal.toFixed(2)}</td>
                            </tr>
                        `;
                    });
                    
                    $('#orderItemsTable tbody').html(itemsHtml);
                    
                    // Display receipt if available
                    const receiptContent = $('#receiptContent');
                    receiptContent.empty();
                    
                    if (result.receipt) {
                        if (result.receipt.startsWith('http') || result.receipt.startsWith('data:image')) {
                            // It's an image URL or base64
                            receiptContent.html(`<img src="${result.receipt}" class="receipt-image" alt="Payment Receipt">`);
                        } else {
                            // It's text or other format
                            receiptContent.text(result.receipt);
                        }
                        $('#receiptContainer').show();
                    } else {
                        $('#receiptContainer').hide();
                    }
                } else {
                    alert('Failed to load order items: ' + (result.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('Error loading order items: ' + error);
            }
        });
    }

    $('#saveStatusBtn').on('click', function() {
        const orderId = $('#orderId').val();
        const status = $('#status').val();
    
        $.ajax({
            url: './../user/index.php',
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
                    table.ajax.reload(null, false); // Reload table without resetting paging
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

    // Delete button handler
    $('#ordersTable').on('click', '.delete-btn', function() {
        const transactionId = $(this).data('id');
        $('#deleteUserModal').modal('show');
        
        $('#confirmDeleteBtn').off('click').on('click', function() {
            deleteOrder(transactionId, table);
        });
    });
}

function deleteOrder(transactionId, table) {
    $.ajax({
        url: './../../admin/user/index.php',
        type: 'POST',
        data: { action: 'delete', id: transactionId },
        success: function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                $('#deleteUserModal').modal('hide');
                alert('Order deleted successfully.');
                table.ajax.reload(null, false); // Reload table without resetting paging
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