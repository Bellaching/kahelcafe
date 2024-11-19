$(document).ready(function() {
    const table = $('#userTable').DataTable({
        ajax: {
            url: './../user/client.php',
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
            { data: 'id' },
            { data: 'fullname' }, // Change this line
            { data: 'email' },
            { data: 'contact_number' },
          
            
            
            {
                data: 'created_at',
               
            },
           
            {
                data: null,
                render: function(data) {
                    return `
                        <button class="editBtn" 
                                data-id="${data.id}" 
                                data-username="${data.username}" 
                                data-email="${data.email}" 
                                data-password="${data.password}" 
                              
                            <i class="fa-regular fa-pen-to-square"></i>
                        </button>
                        <button class="deleteBtn" data-id="${data.id}"><i class="fa-solid fa-trash"></i></button>
                    `;
                }
            }
            
        ]
    });

    // Add user
    $('#createUserForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: './../user/client.php',
            data: $(this).serialize() + '&action=create',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $(".modal-backdrop").remove(); 
                    $("#addAdminModal").hide();
                    alert('User created successfully');

                    table.ajax.reload();
                   
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error with AJAX request');
            }
        });
    });

    // Show update modal and fill the fields
    $(document).on('click', '.editBtn', function() {
        const id = $(this).data('id');
        const username = $(this).data('username');
        const email = $(this).data('email');
        const password = $(this).data('password');
        const role = $(this).data('role');

        $('#id').val(id);
        $('#updateUsername').val(username);
        $('#updateEmail').val(email);
        $('#updatePassword').val(password);
        $('#updateRoleSelect').val(role);
        $('#updateUserModal').show(); // Show the modal
    });

    // Close modal
    $('#closeModalBtn').on('click', function() {
        $('#updateUserModal').hide(); // Hide the modal
    });

    // Edit user
    $('#updateUserForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: './../user/client.php',
            data: $(this).serialize() + '&action=update',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('User updated successfully');
                    $(".modal-backdrop").remove(); 
                    table.ajax.reload(); // Refresh the user table
                    $('#updateUserModal').hide(); // Hide modal after update
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error with AJAX request');
            }
        });
    });

    // Delete user
    $(document).on('click', '.deleteBtn', function() {
        const id = $(this).data('id');
        $('#deleteUserModal').show();

        $('#confirmDeleteBtn').off('click').on('click', function() {
            $.ajax({
                url: './../user/client.php',
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
