$(document).ready(function() {
    const table = initializeDataTable();
    setupEventHandlers(table);
});
 
function initializeDataTable() {
    return $('#userTable').DataTable({
        ajax: {
            url: './../user/index.php',
            type: 'POST',
            data: { action: 'read',
                sort: 'desc' // Add sort parameter
             },
            dataSrc: ''
        },
        columns: [
            { data: 'order_id' },
            { data: 'client_full_name' },
            {
                data: 'created_at',
                render: function(data) {
                    const date = new Date(data);
                    return date.toLocaleDateString('en-US');
                }  
            },
            { data: 'total_price' },
            { data: 'reservation_type' },
            {
                data: 'status',
                render: function(data) {
                    const statusMap = {
                       "for confirmation": '<span class="text-white bg-info p-2 rounded">For Confirmation</span>',
                        "cancelled": '<span class="text-white bg-danger p-2 rounded">Cancelled</span>',
                        "payment": '<span class="text-dark bg-warning p-2 rounded">payment</span>',
                        "PAID": '<span class="text-dark bg-warning p-2 rounded">Paid</span>',
                        "booked": '<span class="text-white bg-success p-2 rounded">Booked</span>',
                        "rate us": '<span class="text-dark bg-secondary p-2 rounded">Rate Us</span>',
                    };
                    return statusMap[data] || data;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `
                        <button class="editBtn" onclick="openUpdateModal(${row.order_id})" data-id="${data.transaction_id}" 
                        data-status="${data.status}" 
                        data-client-name="${data.client_full_name}" 
                        data-reservation-type="${data.reservation_type}" 
                        data-total-price="${data.total_price}"
                        data-created-at="${data.created_at}"
                        data-receipt="${data.receipt}">
                            <i class="fas fa-edit text-primary border-0"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteOrder(${row.transaction_id})">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    `;
                }
            }
        ]
    });
}

function setupEventHandlers(table) {
    $('#userTable').on('click', '.editBtn', function() {
        const orderId = $(this).data('id');
        const status = $(this).data('status');
        const clientName = $(this).data('client-name');
        const reservationType = $(this).data('reservation-type');
        const totalPrice = $(this).data('total-price');
        const createdAt = $(this).data('created-at');
        const receipt = $(this).data('receipt');

        // Set modal data
        $('#updateUserModal').modal('show');
        $('#orderId').val(orderId);
        $('#status').val(status);
        $('#client_full_name_display').text(clientName);
        $('#reservation_type').text(reservationType);
        $('#total_price1').text(`P ${totalPrice}`);
        $('#created_at').text(new Date(createdAt).toLocaleDateString('en-US'));
        $('#receipt').text(`P ${receipt}`);
    });

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
                    table.ajax.reload();  // Reload the table after update
    
                    // Check if the status has changed and reload the page once
                    if (result.status) {
                        window.location.reload();
                    }
                } else {
                    alert('Error updating status');
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while updating the status.');
            }
        });
    });

    $('#markAsReadBtn').on('click', function() {
        $.ajax({
            url: './../../admin/user/index.php',
            type: 'POST',
            data: { action: 'markAllAsRead' },
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    alert('All notifications marked as read.');
                    location.reload(); // Reload the page to reflect changes
                } else {
                    alert('Failed to mark notifications as read.');
                }
            }
        });
    });

    $('#notificationModal').on('show.bs.modal', function() {
        fetchNotifications();
    });
}

function fetchNotifications() {
    $.ajax({
        url: './../../admin/user/index.php',
        type: 'POST',
        data: { action: 'fetchNotifications' },
        success: function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                $('.notification-count').text(data.notificationCount);
                let notificationHtml = '';
                data.notifications.forEach(notification => {
                    notificationHtml += `
                        <div class="notification-item mb-3">
                            <p>${notification.message}</p>
                            <small class="text-muted">${notification.created_at}</small>
                        </div>
                    `;
                });
                $('#notificationModal .modal-body').html(notificationHtml);
            }
        },
        error: function(xhr, status, error) {
            alert('An error occurred while fetching notifications.');
        }
    });
}

function deleteOrder(transactionId) {
    if (confirm('Are you sure you want to delete this order?')) {
        $.ajax({
            url: './../../admin/user/index.php',
            type: 'POST',
            data: { action: 'delete', id: transactionId },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    alert('Order deleted successfully.');
                    table.ajax.reload();
                } else {
                    alert('Failed to delete order.');
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while deleting the order.');
            }
        });
    }
}


function playNotificationSound() {
    const audio = document.getElementById('notificationSound');
    if (audio) {
        audio.play().catch(error => console.error('Error playing sound:', error));
    }
}
function updateNotifications(notifications) {
    const notificationCount = notifications.length;
    const notificationModalBody = document.querySelector('#notificationModal .modal-body');

    // Update the notification count in the navbar
    const notificationCountElement = document.querySelector('.notification-count');
    if (notificationCount > 0) {
        notificationCountElement.textContent = notificationCount;
        notificationCountElement.style.display = 'inline-block';
    } else {
        notificationCountElement.style.display = 'none';
    }

    // Update the notification modal content
    let notificationHtml = '';
    notifications.forEach(notification => {
        notificationHtml += `
            <div class="notification-item mb-3">
                <a href="order-track.php?notification_id=${notification.id}" style="text-decoration: none; color: inherit;">
                    <p>${notification.message}</p>
                    <small class="text-muted">${notification.created_at}</small>
                </a>
            </div>
        `;
    });

    notificationModalBody.innerHTML = notificationHtml || '<p>No new notifications.</p>';
}

let eventSource = null;
function connectToSSE() {
    // Close existing connection if it exists
    if (eventSource) {
        eventSource.close();
        eventSource = null;
    }

    // Establish a new SSE connection
    eventSource = new EventSource('./../../admin/user/sse.php');

    eventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        console.log('Received SSE data:', data); // Debugging

        // Update notifications (if applicable)
        if (data.notifications && data.notifications.length > 0) {
            updateNotifications(data.notifications);
            playNotificationSound();
        }
    };

    eventSource.onerror = function() {
        console.error('SSE connection error. Reconnecting...');
        eventSource.close();
        setTimeout(connectToSSE, 5000); // Reconnect after 5 seconds
    };
}