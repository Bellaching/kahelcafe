$(document).ready(function() {
    const table = $('#userTable').DataTable({
        ajax: {
            url: './../user/index.php',
            type: 'POST',
            data: { action: 'read' },
            dataSrc: ''
        },
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "pageLength": 10,
        "language": {
            "paginate": {
                "previous": "Previous",
                "next": "Next"
            }
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
                        "for confirmation": '<span style="color: #001BCC; background-color: #81CDFF; font-size: 0.7rem;" class="p-2 rounded-pill">For Confirmation</span>',
                        "cancelled": '<span class="text-white bg-danger p-2 rounded">Cancelled</span>',
                        "payment": '<span class="text-dark bg-warning p-2 rounded">Payment</span>',
                        "booked": '<span class="text-white bg-success p-2 rounded">Booked</span>',
                        "rate us": '<span class="text-white bg-info p-2 rounded">Rate Us</span>',
                    };
                    return statusMap[data] || data;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#updateUserModal" onclick="viewOrderItems(${row.order_id})">
                            <i class="fas fa-edit"></i> View Items
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteOrder(${row.transaction_id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    `;
                }
            }
        ]
    });

    // View Order Items when clicking on "View Items" button
    window.viewOrderItems = function(orderId) {
        $.ajax({
            url: './../user/index.php',
            type: 'POST',
            data: { action: 'getOrderItems', order_id: orderId },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    // Populate items in the modal table
                    const itemsTable = $('#userTableUpdate').DataTable();
                    itemsTable.clear();
                    itemsTable.rows.add(data.items);
                    itemsTable.draw();

                    // Populate order summary details
                    const order = data.items[0]; // Assuming the first item is enough for the summary
                    $('#client_full_name_display').text(order.client_full_name);
                    $('#transaction_id').text(order.transaction_id);
                    $('#reservation_type').text(order.reservation_type);
                    $('#total_price1').text('P' + order.total_price);
                    $('#created_at').text(order.created_at);
                } else {
                    alert('Failed to load order items.');
                }
            }
        });
    };

    // Delete order
    window.deleteOrder = function(transactionId) {
        if (confirm('Are you sure you want to delete this order?')) {
            $.ajax({
                url: './../user/index.php',
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
                }
            });
        }
    };

    // Update Order Status
    $('#saveStatusBtn').on('click', function() {
        const formData = $('#updateStatusForm').serialize();
        $.ajax({
            url: './../user/index.php',
            type: 'POST',
            data: { action: 'update', ...formData },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    alert('Order status updated successfully.');
                    $('#updateUserModal').modal('hide');
                    table.ajax.reload();
                } else {
                    alert('Failed to update order status.');
                }
            }
        });
    });
});
