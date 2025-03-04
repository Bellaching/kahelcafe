$(document).ready(function() {
    const table = $('#userTable').DataTable({
        ajax: {
            url: './../user/reservation.php',
            type: 'POST',
            data: { action: 'read' },
            dataSrc: ''
        },
        columns: [
            { data: 'transaction_code' },
            { data: 'clientFullName' },
            { data: 'amount' },
            {
                data: 'res_status',
                render: function(data) {
                    const statusMap = {
                        "for confirmation": '<span style="color: #001BCC; background-color: #81CDFF; font-size: 0.7rem;" class="p-2 rounded-pill">For Confirmation</span>',
                        "payment": '<span class="text-dark bg-warning p-2 rounded">Payment</span>',
                        "booked": '<span class="text-white bg-success p-2 rounded">Booked</span>',
                        "rate us": '<span class="text-dark bg-secondary p-2 rounded">Rate Us</span>',
                        "cancel": '<span class="text-white bg-danger p-2 rounded">Cancelled</span>'
                    };
                    return statusMap[data] || data;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `
                        <button class="editBtn" data-id="${data.transaction_code}" data-res_status="${data.res_status}" data-client-name="${data.clientFullName}">
                            <i class="fas fa-edit text-primary border-0"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteOrder('${data.transaction_code}')">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    `;
                }
            }
        ]
    });

    // Click event for the Edit button
    $('#userTable').on('click', '.editBtn', function() {
        const transactionCode = $(this).data('id');
        const res_status = $(this).data('res_status');
        const clientName = $(this).data('client-name');

        // Set modal data
        $('#updateUserModal').modal('show');
        $('#transactionCode').val(transactionCode); // Hidden input for transaction code
        $('#status').val(res_status); // Set the status dropdown
        $('#client_full_name_display').text(clientName); // Display client name in the modal
    });

    // Delete order
    window.deleteOrder = function(id) {
        if (confirm('Are you sure you want to delete this order?')) {
            $.ajax({
                url: './../user/reservation.php',
                type: 'POST',
                data: { action: 'delete', id: id },
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

    // Save status button click event
    $('#saveStatusBtn').on('click', function() {
        const transactionCode = $('#transactionCode').val();
        const res_status = $('#status').val(); // Get the status from the select element
        const clientFullName = $('#client_full_name_display').text(); // Get the client name from the displayed text

        $.ajax({
            url: './../user/reservation.php',
            type: 'POST',
            data: {
                action: 'update',
                id: transactionCode,
                res_status: res_status,
                clientFullName: clientFullName
            },
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    $('#updateUserModal').modal('hide');
                    table.ajax.reload(); // Reload the table after update
                } else {
                    alert('Error updating status');
                }
            }
        });
    });
});