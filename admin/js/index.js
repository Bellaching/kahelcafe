$(document).ready(function() {
    // Initialize DataTable
    const table = initializeDataTable();
    setupEventHandlers(table);
    
    // Connect to SSE for real-time updates
    connectToSSE();
});

function initializeDataTable() {
    return $('#ordersTable').DataTable({
        ajax: {
            url: './../user/index.php',
            type: 'POST',
            data: { 
                action: 'read',
                sort: 'desc'
            },
            dataSrc: ''
        },
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
                    return getStatusHtml(data);
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `
                        <div class="d-flex">
                            <button class="editBtn btn btn-sm"
                                data-id="${row.transaction_id}" 
                                data-status="${row.status}" 
                                data-client-name="${row.client_full_name}" 
                                data-reservation-type="${row.reservation_type}" 
                                data-total-price="${row.total_price}"
                                data-created-at="${row.created_at}"
                                data-reservation-time="${row.reservation_time}"
                                data-order-id="${row.order_id}">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                            <button class="deleteBtn btn btn-sm" 
                                data-id="${row.transaction_id}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        responsive: true
    });
}

function getStatusHtml(status) {
    const statusMap = {
        "for confirmation": '<span class="badge bg-info">For Confirmation</span>',
        "cancelled": '<span class="badge bg-danger">Cancelled</span>',
        "payment": '<span class="badge bg-warning text-dark">Payment</span>',
        "Paid": '<span class="badge bg-success">Paid</span>',
        "booked": '<span class="badge bg-primary">Booked</span>',
        "rate us": '<span class="badge bg-secondary">Rate Us</span>',
    };
    return statusMap[status.toLowerCase()] || status;
}

function setupEventHandlers(table) {
    // Edit button handler
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
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        const items = result.items;
                        let itemsHtml = '';
                        
                        items.forEach(item => {
                            const price = parseFloat(item.price) || 0;
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
                        
                        // Display receipt if available
                        const receiptContent = $('#receiptContent');
                        const receiptContainer = $('#receiptContainer');
                        receiptContent.empty();
                        
                        if (result.receipt && result.receipt.trim() !== '') {
                            const receiptPath = './../../uploads/' + result.receipt;
                            
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
                    }
                } catch (e) {
                    console.error('Error parsing order items:', e);
                }
            }
        });
    }

    // Save status button handler
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
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        $('#updateUserModal').modal('hide');
                        
                        // Update only the specific row in the DataTable
                        const row = table.row(`[data-id="${orderId}"]`).node();
                        if (row) {
                            $(row).find('td:eq(6)').html(getStatusHtml(status));
                        }
                        
                        showAlert('Status updated successfully', 'success');
                    } else {
                        showAlert('Error updating status: ' + (result.message || 'Unknown error'), 'danger');
                    }
                } catch (e) {
                    showAlert('Error parsing response', 'danger');
                }
            },
            error: function(xhr, status, error) {
                showAlert('An error occurred: ' + error, 'danger');
            }
        });
    });

    // Delete button handler
    $('#ordersTable').on('click', '.deleteBtn', function() {
        const transactionId = $(this).data('id');
        if (confirm('Are you sure you want to delete this order?')) {
            deleteOrder(transactionId, table);
        }
    });
}

function deleteOrder(transactionId, table) {
    $.ajax({
        url: './../user/index.php',
        type: 'POST',
        data: { 
            action: 'delete', 
            id: transactionId 
        },
        success: function(response) {
            const result = JSON.parse(response);
            if (result.success) {
                // Remove the row from DataTable
                table.row(`[data-id="${transactionId}"]`).remove().draw();
                showAlert('Order deleted successfully', 'success');
            } else {
                showAlert('Failed to delete order: ' + (result.message || 'Unknown error'), 'danger');
            }
        },
        error: function(xhr, status, error) {
            showAlert('An error occurred: ' + error, 'danger');
        }
    });
}

function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    $('#alertContainer').html(alertHtml);
    setTimeout(() => $('.alert').alert('close'), 3000);
}

// SSE functions
let eventSource = null;

function connectToSSE() {
    if (eventSource) {
        eventSource.close();
    }

    eventSource = new EventSource('./../../admin/user/sse.php');

    eventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        if (data.notifications && data.notifications.length > 0) {
            updateNotifications(data.notifications);
            playNotificationSound();
        }
    };

    eventSource.onerror = function() {
        setTimeout(connectToSSE, 5000);
    };
}

function updateNotifications(notifications) {
    const notificationCount = notifications.length;
    $('.notification-count').text(notificationCount).toggle(notificationCount > 0);
    
    let notificationHtml = '';
    notifications.forEach(notification => {
        notificationHtml += `
            <div class="notification-item mb-3">
                <p>${notification.message}</p>
                <small class="text-muted">${notification.created_at}</small>
            </div>
        `;
    });
    $('#notificationModal .modal-body').html(notificationHtml || '<p>No new notifications</p>');
}

function playNotificationSound() {
    const audio = document.getElementById('notificationSound');
    if (audio) {
        audio.play().catch(error => console.error('Error playing sound:', error));
    }
}

// Clean up on page unload
$(window).on('beforeunload', function() {
    if (eventSource) {
        eventSource.close();
    }
});