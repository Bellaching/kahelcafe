$(document).ready(function() {
    // Initialize DataTable
    const table = $('#userTable').DataTable({
        ajax: {
            url: './../user/reservation.php',
            type: 'POST',
            data: { action: 'read' },
            dataSrc: '',
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', xhr.responseText, error, thrown);
                alert('Failed to load reservation data. Please check console for details.');
            }
        },
        order: [[5, 'desc']], // Sort by date_created (column index 5) in descending order
        columns: [
            { data: 'transaction_code' },
            { data: 'clientFullName' },
            { 
                data: 'amount',
                render: function(data) {
                    return '₱' + parseFloat(data).toFixed(2);
                }
            },
            {
                data: 'res_status',
                render: function(data) {
                    const statusMap = {
                       
                        "for confirmation": '<span style="padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; background-color: #17a2b8; color: white;">For Confirmation</span>',
                        "cancel": '<span style="padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; background-color: #dc3545; color: white;">Cancelled</span>',
                        "payment": '<span style="padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; background-color: #ffc107; color: #212529;">Payment</span>',
                        "paid": '<span style="padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; background-color: #28a745; color: white;">Paid</span>',
                        "booked": '<span style="padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; background-color: #007bff; color: white;">Booked</span>',
                        "rate us": '<span style="padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; background-color: #6c757d; color: white;">Rate Us</span>',
                    };
                    return statusMap[data] || data;
                } 
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `
                          <button class="editBtn"
                        data-id="${data.transaction_code || data.reservation_time_id}" 
                                data-res_status="${data.res_status}" 
                                data-client-name="${data.clientFullName}"
                                data-amount="${data.amount}"
                                data-party-size="${data.party_size}"
                                data-created-at="${data.date_created}"
                                data-reservation-date="${data.reservation_date || ''}"
                                data-reservation-time="${data.reservation_time || ''}"
                                data-reservation-fee="${data.amount}"
                                data-receipt="${data.receipt || ''}"
                                
                                >
                             <i class="fa-regular fa-pen-to-square"></i>
                        </button>
                        <button class=" deleteBtn" style="padding: 5px 10px;" 
                        onclick="deleteOrder('${data.transaction_code}')">
                              <i class="fa-solid fa-trash"></i>
                        </button>
                    `;
                }
            },
            { 
                data: 'date_created',
                visible: false // Hide this column but still use it for sorting
            }
        ]
    });

  
    function refreshTable() {
        table.ajax.reload(null, false); 
    }

    
    const pollInterval = 30000;
    let pollingTimer = setInterval(refreshTable, pollInterval);

   
    $(window).blur(function() {
        clearInterval(pollingTimer);
    }).focus(function() {
        pollingTimer = setInterval(refreshTable, pollInterval);
        refreshTable(); // Also refresh immediately when tab gains focus
    });

    $('#userTable').on('click', '.editBtn', function() {
        const transactionCode = $(this).data('id');
        const res_status = $(this).data('res_status');
        const clientName = $(this).data('client-name');
        const amount = $(this).data('amount');
        const partySize = $(this).data('party-size');
        const createdAt = $(this).data('created-at');
        const reservationDate = $(this).data('reservation-date');
        const reservationTime = $(this).data('reservation-time');
        const reservationFee = $(this).data('reservation-fee');
        const receipt = $(this).data('receipt'); // Get receipt filename
    
        const subTotal = parseFloat(amount) - parseFloat(reservationFee || 0);
        const formattedCreatedAt = createdAt ? new Date(createdAt).toLocaleDateString() : '';
        const formattedReservationDate = reservationDate ? new Date(reservationDate).toLocaleDateString() : '';
    
        $('#updateUserModal').modal('show');
        $('#transactionCode').val(transactionCode);
        $('#status').val(res_status);
        $('#client_full_name_display').text(clientName);
        $('#transaction_code_display').text(transactionCode);
        $('#party_size_display').text(partySize);
        $('#total_price_display').text('₱' + parseFloat(amount).toFixed(2));
        $('#created_at_display').text(formattedCreatedAt);
        $('#reservation_date_display').text(formattedReservationDate);
        $('#reservation_time_display').text(reservationTime);
        $('#sub_total_display').text('₱' + subTotal.toFixed(2));
        $('#reservation_fee_display').text('₱' + parseFloat(reservationFee || 0).toFixed(2));
    
        // Display receipt image if exists
        const receiptDisplay = $('#receipt_display');
        receiptDisplay.empty(); // Clear previous content
        
        if (receipt) {
            const receiptPath = './../../uploads/' + receipt;
            receiptDisplay.html(`
                <a href="${receiptPath}" target="_blank">
                    <img src="${receiptPath}" alt="Payment Receipt" style="max-width: 200px; max-height: 200px;">
                </a>
            `);
        } else {
            receiptDisplay.text('No receipt uploaded');
        }
    });

    // Delete function
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
                        refreshTable(); // Refresh table after delete
                    } else {
                        alert('Failed to delete order.');
                    }
                }
            });
        } 
    };

    $('#saveStatusBtn').on('click', function() {
        const transactionCode = $('#transactionCode').val();
        const res_status = $('#status').val();

        $.ajax({
            url: './../user/reservation.php',
            type: 'POST',
            data: {
                action: 'update',
                id: transactionCode,
                res_status: res_status
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        $('#updateUserModal').modal('hide');
                        refreshTable(); // Refresh table after update
                    } else {
                        alert('Error updating status: ' + (result.message || 'Unknown error'));
                    }
                } catch (e) {
                    refreshTable(); // Refresh table if there's any response
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                alert('Failed to update status. Please check console for details.');
            }
        });
    });
});