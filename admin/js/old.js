$(document).ready(function() {
    $(document).ready(function() {
        const table = $('#userTable').DataTable({
            ajax: {
                url: './../user/index.php',
                type: 'POST',
                data: { action: 'read' },
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
                            "for confirmation": '<span style="color: #001BCC; background-color: #81CDFF; font-size: 0.7rem;" class="p-2 rounded-pill">For Confirmation</span>',
                            "cancelled": '<span class="text-white bg-danger p-2 rounded">Cancelled</span>',
                            "payment": '<span class="text-dark bg-warning p-2 rounded">Payment</span>',
                            "booked": '<span class="text-white bg-success p-2 rounded">Booked</span>',
                            "rate us": '<span class="text-dark bg-secondary p-2 rounded">Rate Us</span>',
                        };
                        return statusMap[data] || data;
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return `<button class="btn btn-info" onclick="openUpdateModal(${row.order_id})">Update</button>
                         <button class="btn btn-danger btn-sm" onclick="deleteOrder(${row.transaction_id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        `;
                    }
                }
            ]
        });
    
        // Open the modal and fetch order items
        window.openUpdateModal = function(orderId) {
            // Fetch order details
            $.ajax({
                url: './../user/index.php',
                type: 'POST',
                data: {
                    action: 'getOrderItems',
                    order_id: orderId
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Populate the order items table
                        let orderItemsHtml = '';
                        data.items.forEach(item => {
                            orderItemsHtml += `
                                <tr>
                                    <td>${item.item_name}</td>
                                    <td>${item.price}</td>
                                    <td>${item.size}</td>
                                    <td>${item.temperature}</td>
                                    <td>${item.quantity}</td>
                                </tr>
                            `;
                        });
                        $('#userTableUpdate tbody').html(orderItemsHtml);
    
                        // Set order summary data
                        $('#client_full_name_display').text(data.items[0].client_full_name);
                        $('#transaction_id').text(data.items[0].transaction_id);
                        $('#reservation_type').text(data.items[0].reservation_type);
                        $('#created_at').text(data.items[0].created_at);
                        $('#total_price1').text('P' + data.items[0].total_price);
                    } else {
                        alert('Failed to fetch order items');
                    }
                }
            });
    
            // Open the modal
            $('#updateUserModal').modal('show');
        };
    });
    

    // Click event for the Edit button
    $('#userTable').on('click', '.editBtn', function() {
        const orderId = $(this).data('id');
        const status = $(this).data('status');
        const clientName = $(this).data('client-name');
        const reservationType = $(this).data('reservation-type');
        const totalPrice = $(this).data('total-price');
        const createdAt = $(this).data('created-at');

        // Set modal data
        $('#updateUserModal').modal('show');
        $('#orderId').val(orderId);
        $('#status').val(status);
        $('#client_full_name_display').text(clientName);
        $('#reservation_type').text(reservationType);
        $('#total_price1').text(`P ${totalPrice}`);
        $('#created_at').text(new Date(createdAt).toLocaleDateString('en-US'));

      
    });

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
    

    // Save order status update
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
                } else {
                    alert('Error updating status');
                }
            }
        });
    });
});
