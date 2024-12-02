
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
            { data: 'transaction_id' },
            { data: 'client_full_name' },
            {data: 'order_created',
                render: function(data) {
                    const date = new Date(data); // Convert to Date object
                    return date.toLocaleDateString('en-US'); // Format the date
                }
            },
            { data: 'total_price' },
            { data: 'reservation_type' },
            // { data: 'status' },
           
            {
                data: null,
                render: function(data) {
                    return `
                        <button class="editBtn" 
                                data-id="${data.id}" 
                                data-username="${data.username}" 
                                data-email="${data.email}" 
                                data-password="${data.password}" 
                                data-role="${data.role}"> <!-- Add role here -->
                            <i class="fa-regular fa-pen-to-square"></i>
                        </button>
                        <button class="deleteBtn" data-id="${data.id}"><i class="fa-solid fa-trash"></i></button>
                    `;
                }
            }
            
        ]
    });

    

    // // Show update modal and fill the fields
    // $(document).on('click', '.editBtn', function() {
    //     const id = $(this).data('id');
    //     const username = $(this).data('username');
    //     const email = $(this).data('email');
       
    //     const role = $(this).data('role');

    //     $('#id').val(id);
    //     $('#updateUsername').val(username);
    //     $('#updateEmail').val(email);
       
    //     $('#updateRoleSelect').val(role);
    //     $('#updateUserModal').show(); // Show the modal
    // });

    // Close modal
    $('#closeModalBtn').on('click', function() {
        $('#updateUserModal').hide(); // Hide the modal
    });

    // Edit user
    // $('#updateUserForm').on('submit', function(e) {
    //     e.preventDefault();
    //     $.ajax({
    //         type: 'POST',
    //         url: './../user/accountManagement.php',
    //         data: $(this).serialize() + '&action=update',
    //         dataType: 'json',
    //         success: function(response) {
    //             if (response.success) {
    //                 alert('User updated successfully');
    //                 $(".modal-backdrop").remove(); 
    //                 table.ajax.reload(); // Refresh the user table
    //                 $('#updateUserModal').hide(); // Hide modal after update
    //             } else {
    //                 alert('Error: ' + response.message);
    //             }
    //         },
    //         error: function() {
    //             alert('Error with AJAX request');
    //         }
    //     });
    // });

    // Delete user
    $(document).on('click', '.deleteBtn', function() {
        const id = $(this).data('id');
        $('#deleteUserModal').show();

        $('#confirmDeleteBtn').off('click').on('click', function() {
            $.ajax({
                url: './../user/index.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                success: function() {
                    alert('User deleted successfully');
                    table.ajax.reload();
                    $(".modal-backdrop").remove(); 
                    $('#deleteUserModal').hide();
                }
            });
        });

        $('#cancelDeleteBtn').off('click').on('click', function() {
            $('#deleteUserModal').hide();
        });
    });
});