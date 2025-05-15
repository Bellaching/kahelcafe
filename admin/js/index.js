$(document).ready(function () {
    const table = initializeDataTable();
    setupEventHandlers(table);
    connectToSSE();
});

function initializeDataTable() {
    return $('#ordersTable').DataTable({
        ajax: {
            url: './../user/index.php', 
            type: 'POST',
            data: {
                action: 'read',
                sort: 'desc' // Ensure server-side sorting is descending
            },
            dataSrc: ''
        },
        columns: [
            { data: 'order_id' },
            { data: 'client_full_name' },
            {
                data: 'created_at',
                render: data => new Date(data).toLocaleDateString('en-US'),
                type: 'date' // Enable proper date sorting
            },
            {
                data: 'reservation_time',
                render: data => data || 'N/A'
            },
            {
                data: 'total_price',
                render: data => '₱' + (parseFloat(data) || 0).toFixed(2)
            },
            { data: 'reservation_type' },
            {
                data: 'status',
                render: data => getStatusHtml(data)
            },
            {
                data: null,
                render: (data, type, row) => `
                    <div class="d-flex">
                        <button class="editBtn btn btn-sm" data-transaction-id="${row.transaction_id}" 
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
                            data-transaction-id="${row.transaction_id}">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                `
            }
        ],
        responsive: true,
        createdRow: (row, data) => {
            $(row).attr('data-transaction-id', data.transaction_id);
        },
        order: [[2, 'desc']] // Sort by created_at (column index 2) in descending order by default
    });
}

function getStatusHtml(status) {
    const map = {
        "for confirmation": '<span class="badge bg-info">For Confirmation</span>',
        "cancelled": '<span class="badge bg-danger">Cancelled</span>',
        "payment": '<span class="badge bg-warning text-dark">Payment</span>',
        "paid": '<span class="badge bg-success">Paid</span>',
        "booked": '<span class="badge bg-primary">Booked</span>',
        "rate us": '<span class="badge bg-secondary">Rate Us</span>',
    };
    return map[status.toLowerCase()] || status;
}

function setupEventHandlers(table) {
    $('#ordersTable').on('click', '.editBtn', function () {
        const btn = $(this);
        $('#updateUserModal').modal('show');
        $('#orderId').val(btn.data('transaction-id'));
        $('#status').val(btn.data('status'));
        $('#client_full_name_display').text(btn.data('client-name'));
        $('#reservation_type').text(btn.data('reservation-type'));
        $('#total_price1').text('₱' + parseFloat(btn.data('total-price')).toFixed(2));
        $('#created_at').text(new Date(btn.data('created-at')).toLocaleDateString('en-US'));
        $('#transaction_id').text(btn.data('transaction-id'));
        $('#reservation_time').text(btn.data('reservation-time') || 'N/A');
        loadOrderItems(btn.data('order-id'));
    });

    function loadOrderItems(orderId) {
        $.post('./../user/index.php', {
            action: 'getOrderItems',
            order_id: orderId
        }, function (response) {
            try {
                const res = JSON.parse(response);
                if (res.success) {
                    let html = '';
                    res.items.forEach(item => {
                        html += `
                            <tr>
                                <td>${item.item_name}</td>
                                <td>₱${(parseFloat(item.price) || 0).toFixed(2)}</td>
                                <td>${item.size || 'N/A'}</td>
                                <td>${item.temperature || 'N/A'}</td>
                                <td>${item.quantity}</td>
                            </tr>
                        `;
                    });
                    $('#orderItemsTable tbody').html(html);
                    const receiptContainer = $('#receiptContainer');
                    const receiptContent = $('#receiptContent').empty();
                    if (res.receipt?.trim()) {
                        const path = './../../uploads/' + res.receipt;
                        receiptContent.html(`
                            <img src="${path}" style="max-width:100%; max-height:300px; margin-top:10px;" alt="Payment Receipt" onerror="this.style.display='none'">
                            <div style="margin-top: 5px;"><a href="${path}" target="_blank">View Full Image</a></div>
                        `);
                        receiptContainer.show();
                    } else {
                        receiptContainer.hide();
                    }
                }
            } catch (e) {
                console.error('Error parsing order items:', e);
            }
        });
    }

    $('#saveStatusBtn').on('click', function () {
        const transactionId = $('#orderId').val();
        const status = $('#status').val();

        $.post('./../user/index.php', {
            action: 'update',
            id: transactionId,
            status
        }, function (response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    $('#updateUserModal').modal('hide');

                    table.rows().every(function () {
                        const data = this.data();
                        if (data.transaction_id == transactionId) {
                            data.status = status;
                            this.data(data);
                            this.invalidate(); // Ensure DataTable updates the rendered row
                            this.draw(false);
                            return false;
                        }
                    });

                    showAlert('Status updated successfully', 'success');
                } else {
                    showAlert('Error updating status: ' + (result.message || 'Unknown error'), 'danger');
                }
            } catch {
                showAlert('Error parsing response', 'danger');
            }
        }).fail((xhr, status, error) => {
            showAlert('An error occurred: ' + error, 'danger');
        });
    });

    $('#ordersTable').on('click', '.deleteBtn', function () {
        const transactionId = $(this).data('transaction-id');
        if (confirm('Are you sure you want to delete this order?')) {
            deleteOrder(transactionId, table);
        }
    });
}

function deleteOrder(transactionId, table) {
    $.post('./../user/index.php', {
        action: 'delete',
        id: transactionId
    }, function (response) {
        const result = JSON.parse(response);
        if (result.success) {
            table.rows().every(function () {
                if (this.data().transaction_id == transactionId) {
                    this.remove().draw(false);
                    return false;
                }
            });
            showAlert('Order deleted successfully', 'success');
        } else {
            showAlert('Failed to delete order: ' + (result.message || 'Unknown error'), 'danger');
        }
    }).fail((xhr, status, error) => {
        showAlert('An error occurred: ' + error, 'danger');
    });
}

function showAlert(message, type) {
    const html = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    $('#alertContainer').html(html);
    setTimeout(() => $('.alert').alert('close'), 3000);
}

// --- SSE ---
let eventSource = null;

function connectToSSE() {
    if (eventSource) eventSource.close();
    eventSource = new EventSource('./../../admin/user/sse.php');

    eventSource.onmessage = e => {
        const data = JSON.parse(e.data);
        if (data.notifications?.length) {
            updateNotifications(data.notifications);
            playNotificationSound();
        }
    };

    eventSource.onerror = () => setTimeout(connectToSSE, 5000);
}

function updateNotifications(notifications) {
    $('.notification-count').text(notifications.length).toggle(notifications.length > 0);

    const html = notifications.map(n => `
        <div class="notification-item mb-3">
            <p>${n.message}</p>
            <small class="text-muted">${n.created_at}</small>
        </div>
    `).join('');
    $('#notificationModal .modal-body').html(html || '<p>No new notifications</p>');
}

function playNotificationSound() {
    const audio = document.getElementById('notificationSound');
    if (audio) audio.play().catch(e => console.error('Error playing sound:', e));
}

$(window).on('beforeunload', () => {
    if (eventSource) eventSource.close();
});