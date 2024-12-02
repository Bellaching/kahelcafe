
<?php

include './../inc/topNav.php';


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CRUD Operations</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">

    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
 
    <script src="./../js/index.js"></script>

    <style>
        
        body {
            background-color: #FCFCFC;
        }
        .editBtn,
        .deleteBtn {
            background: none;
            border: none;
        }
        .btn_color {
            background-color: #06C185;
            border: #06C185 solid 1px;
        }
        .dataTables_filter input {
            border: #9E9E9E 1px solid;
            border-radius: 0.3rem;
        }
        #usersTable, th {
            border: none;
        }
        .editBtn {
            color: #624DE3;
        }
        .deleteBtn {
            color: #A30D11;
        }
        .account-text {
            font-size: 2rem;
            font-weight: bold;
        }
        .management-underline {
            position: relative;
        }
        .management-underline::after {
            content: '';
            position: absolute;
            bottom: -10px; 
            left: 0;
            width: 100%;
            height: 5px; 
            background-color: #FC8E29;
            border-radius: 3px;
        }



    </style>
</head>
<body>

<div class="container-fluid mb-3 ">
    <div class="row mt-5 ms-5">
        <div class="col-12 col-md-10 col-lg-8">
            <p class="account-text ">
               Order <span class="management-underline">Management</span>
            </p>
        </div>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog p-3">
        <div class="modal-content p-3">
            <!-- Modal Header with no border -->
            <div class="modal-header border-0">
                <h5 class="modal-title" id="addAdminModalLabel">Scan QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div id="qr-reader" style="width: 100%;"></div>
                <div id="qr-reader-results" class="mt-3"></div>
                <p class="error text-danger" id="scanError" style="display: none;"></p>
            </div>
        </div>
    </div>
</div>

<div class ="d-flex justify-content-center w-100">
<div class="container-fluid shadow p-3 mx-5 bg-body-tertiary rounded">

        <div class="d-flex justify-content-end m-2 mb-5">
            <button type="button" class="btn btn_color btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            <i class="fa-solid fa-qrcode"></i> &#160; <strong>QR_Scanner</strong> 
            </button>
        </div>
        <table id="userTable" class="display">
           
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Reservation Type</th>
                    <!-- <th>Status</th> -->
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
               <!-- Table data will be filled dynamically  -->
            </tbody>
        </table>
    </div>
</div>

<!-- <div class="container text-center" style="margin-top: 100px;">
    <div class="alert alert-warning" role="alert">
        Under Development
    </div>
</div> -->


<!-- Update User Modal -->
<!-- Update User Modal -->
<div class="modal fade" id="updateUserModal" tabindex="-1" aria-labelledby="updateUserModalLabel" aria-hidden="true">
    <div class="modal-dialog p-3">
        <div class="modal-content p-3">
            <!-- Modal Header with no border -->
            <div class="modal-header border-0">
                <h5 class="modal-title" id="updateUserModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateUserForm">
                    <input type="hidden" name="id" id="updateUserId">

                    <div class="mb-3">
                        <label for="updateEmail" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 border-bottom border-dark">
                                <i class="fa-regular fa-envelope"></i>
                            </span>
                            <input type="email" name="email" id="updateEmail" class="form-control border-0 border-bottom border-dark" placeholder="Enter your email" disabled>
                        </div>
                        <p class="error text-danger"></p> <!-- Error message container -->
                    </div>

                    <div class="mb-3">
                        <label for="updateUsername" class="form-label">New Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 border-bottom border-dark">
                                <i class="fa-regular fa-user"></i>
                            </span>
                            <input type="text" name="username" id="updateUsername" class="form-control border-0 border-bottom border-dark" disabled>
                        </div>
                        <p class="error text-danger"></p> <!-- Error message container -->
                    </div>

                    

                    <div class="mb-3">
                        <label for="updateRoleSelect" class="form-label">Select Role</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 border-bottom border-dark">
                                <i class="fa-regular fa-user"></i>
                            </span>
                            <select class="form-select border-0 border-bottom border-dark" id="updateRoleSelect" name="role" required>
                                <option selected disabled>Select Role</option>
                                <option value="owner">Owner</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn_color btn-primary mx-auto w-100 m-5 p-3" style="background-color: #FF902B; border-radius: 30px; border: none;">
                        Update User
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>



<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Yes</button>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
<script>
    function onScanSuccess(decodedText, decodedResult) {
        // Handle the result here
        document.getElementById('qr-reader-results').innerText = `Scan result: ${decodedText}`;
        // Optionally, you can process the scanned data (e.g., save to the database)
    }

    function onScanError(errorMessage) {
        // Handle scan error (if necessary)
        console.warn(`QR scan error: ${errorMessage}`);
    }

    const html5QrCode = new Html5Qrcode("qr-reader");
    html5QrCode.start(
        { facingMode: "environment" }, 
        {
            fps: 10, 
            qrbox: { width: 250, height: 250 } 
        },
        onScanSuccess,
        onScanError
    ).catch(err => {
        // Start failed, handle it here
        document.getElementById('scanError').innerText = "Error starting QR scanner: " + err;
        document.getElementById('scanError').style.display = 'block';
    });
</script>

<script>
$(document).ready(function() {
    // Example: Handle edit button click
    $(document).on('click', '.editBtn', function() {
        const userId = $(this).data('id'); // Assuming you store the ID in a data attribute
        const username = $(this).data('username'); // Assuming you store the username

        $('#updateUserId').val(userId);
        $('#updateUsername').val(username);
        $('#updateUserModal').modal('show');
    });

    // Example: Handle delete button click
    $(document).on('click', '.deleteBtn', function() {
        const userId = $(this).data('id'); // Assuming you store the ID in a data attribute

        $('#confirmDeleteBtn').data('id', userId); // Store the ID on the confirm button
        $('#deleteUserModal').modal('show');
    });

    // Confirm delete action
    $('#confirmDeleteBtn').click(function() {
        const userId = $(this).data('id');
        // Perform the delete action using AJAX or a form submission
    });
});


</script>

</body>
</html>