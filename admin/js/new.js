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
                        "rate us": '<span class="text-white bg-info p-2 rounded">Rate Us</span>'
                    };
                    return statusMap[data] || '<span class="badge badge-secondary text-black">Unknown</span>';
                }
            },
            {
                data: null,
                render: function(data) {
                    return `
                        <button class="editBtn" data-id="${data.transaction_id}" 
                        data-status="${data.status}" 
                        data-client-name="${data.client_full_name}" 
                        data-reservation-type="${data.reservation_type}" 
                        data-total-price="${data.total_price}"
                        data-created-at="${data.created_at}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="deleteBtn" data-id="${data.transaction_id}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    `;
                }
            }
        ]
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

    // Click event for the Delete button
    $('#userTable').on('click', '.deleteBtn', function() {
        const orderId = $(this).data('id');

        // Show delete modal
        $('#deleteUserModal').modal('show');

        $('#confirmDeleteBtn').on('click', function() {
            $.ajax({
                url: './../user/index.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    id: orderId
                },
                success: function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        $('#deleteUserModal').modal('hide');
                        table.ajax.reload();  // Reload the table after delete
                    } else {
                        alert('Error deleting order');
                    }
                }
            });
        });
    });

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
