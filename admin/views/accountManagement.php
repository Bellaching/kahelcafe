<?php
include './../inc/topNav.php';
include './../../connection/connection.php';



// Handle backend processing first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $response = ['success' => false];
    
    switch ($_POST['action']) {
        case 'create_user':
            $response = handleCreateUser($conn);
            break;
            
        case 'check_email':
            $response = checkEmailExists($conn, $_POST['email']);
            break;
            
        case 'check_username':
            $response = checkUsernameExists($conn, $_POST['username']);
            break;
            
        case 'update_user':
            $response = updateUserRole($conn, $_POST['id'], $_POST['role']);
            break;
            
        case 'delete_user':
            $response = deleteUser($conn, $_POST['id']);
            break;
            
        default:
            $response['error'] = 'Invalid action';
    }
    
    echo json_encode($response);
    exit;
}

function handleCreateUser($conn) {
    $response = ['success' => false, 'errors' => []];
    $required = ['email', 'username', 'password', 'role'];
    
    // Validate required fields
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $response['errors'][$field] = ucfirst($field) . ' is required';
        }
    }

    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = 'Invalid email format';
    }

    // Validate username length
    if (strlen($_POST['username']) < 4) {
        $response['errors']['username'] = 'Username must be at least 4 characters';
    }

    // Validate password strength
    if (strlen($_POST['password']) < 8) {
        $response['errors']['password'] = 'Password must be at least 8 characters';
    } elseif (!preg_match('/[A-Z]/', $_POST['password']) || 
             !preg_match('/[a-z]/', $_POST['password']) || 
             !preg_match('/[0-9]/', $_POST['password'])) {
        $response['errors']['password'] = 'Password must contain uppercase, lowercase letters and numbers';
    }

    // Validate role
    if (!in_array($_POST['role'], ['owner', 'staff'])) {
        $response['errors']['role'] = 'Invalid role selected';
    }

    if (!empty($response['errors'])) {
        return $response;
    }

    // Check for existing email or username
    $emailExists = checkEmailExists($conn, $_POST['email']);
    $usernameExists = checkUsernameExists($conn, $_POST['username']);

    if ($emailExists['exists']) {
        $response['errors']['email'] = 'Email already exists';
    }
    if ($usernameExists['exists']) {
        $response['errors']['username'] = 'Username already exists';
    }

    if (!empty($response['errors'])) {
        return $response;
    }

    // Hash the password with bcrypt
    $passwordHash = password_hash($_POST['password'], PASSWORD_BCRYPT);
    if ($passwordHash === false) {
        $response['error'] = 'Password hashing failed';
        return $response;
    }

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO admin_list 
                          (email, username, password, role, date_created) 
                          VALUES (?, ?, ?, ?, NOW())");
    if (!$stmt) {
        $response['error'] = 'Prepare failed: ' . $conn->error;
        return $response;
    }
    
    $stmt->bind_param("ssss", $_POST['email'], $_POST['username'], $passwordHash, $_POST['role']);
    $success = $stmt->execute();

    if ($success) {
        $response['success'] = true;
        $response['message'] = 'User created successfully';
        $response['user_id'] = $stmt->insert_id;
    } else {
        $response['error'] = 'Failed to create user: ' . $stmt->error;
    }
    $stmt->close();

    return $response;
}

function checkEmailExists($conn, $email) {
    $stmt = $conn->prepare("SELECT id FROM admin_list WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    return ['exists' => $stmt->num_rows > 0, 'success' => true];
}

function checkUsernameExists($conn, $username) {
    $stmt = $conn->prepare("SELECT id FROM admin_list WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    return ['exists' => $stmt->num_rows > 0, 'success' => true];
}

function updateUserRole($conn, $id, $role) {
    $stmt = $conn->prepare("UPDATE admin_list SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $role, $id);
    $success = $stmt->execute();
    $stmt->close();
    return ['success' => $success, 'error' => $success ? null : 'Failed to update user: ' . $conn->error];
}

function deleteUser($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM admin_list WHERE id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();
    return ['success' => $success, 'error' => $success ? null : 'Failed to delete user: ' . $conn->error];
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Operations</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/js-sha256/0.9.0/sha256.min.js"></script>
    <script src="./../js/accountManagement.js"></script>

    <style>
        body {
            display: flex;
            flex-direction: column;
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
        #userTable, th {
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
        .table-container {
            overflow-x: auto;
        }
        .is-invalid {
            border-color: #dc3545 !important;
        }
        .is-valid {
            border-color: #28a745 !important;
        }
        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }
        .valid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #28a745;
        }
        @media (max-width: 768px) {
            .account-text {
                font-size: 1.5rem;
            }
            .container-fluid {
                padding-left: 15px;
                padding-right: 15px;
            }
            .ms-5 {
                margin-left: 15px !important;
            }
            .mx-5 {
                margin-left: 15px !important;
                margin-right: 15px !important;
            }
        }
        .password-strength {
            margin-top: 5px;
            font-size: 0.85rem;
        }
        .strength-weak {
            color: #dc3545;
        }
        .strength-medium {
            color: #fd7e14;
        }
        .strength-strong {
            color: #28a745;
        }
    </style>
</head>
<body>

<div class="container-fluid mb-3">
    <div class="row mt-md-5 mt-3 ms-md-5 ms-0">
        <div class="col-12 col-md-10 col-lg-8">
            <p class="account-text">
                Account <span class="management-underline">Management</span>
            </p>
        </div>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="addAdminModalLabel">Create Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" id="createUserForm" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 border-bottom">
                                <i class="fa-regular fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control border-0 border-bottom" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                        <div class="invalid-feedback" id="emailError">Please provide a valid email.</div>
                        <div class="valid-feedback">Looks good!</div>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 border-bottom">
                                <i class="fa-regular fa-user"></i>
                            </span>
                            <input type="text" class="form-control border-0 border-bottom" id="username" name="username" placeholder="Enter a username" required minlength="4">
                        </div>
                        <div class="invalid-feedback" id="usernameError">Username must be at least 4 characters.</div>
                        <div class="valid-feedback">Looks good!</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 border-bottom">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input type="password" class="form-control border-0 border-bottom" id="password" name="password" placeholder="Enter a password" required minlength="8">
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                        <div class="invalid-feedback" id="passwordError">Password must be at least 8 characters.</div>
                        <div class="valid-feedback">Looks good!</div>
                    </div>

                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 border-bottom">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input type="password" class="form-control border-0 border-bottom" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required minlength="8">
                        </div>
                        <div class="invalid-feedback" id="confirmPasswordError">Passwords do not match.</div>
                        <div class="valid-feedback">Passwords match!</div>
                    </div>

                    <div class="mb-3">
                        <label for="roleSelect" class="form-label">Select Role</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 border-bottom">
                                <i class="fa-regular fa-user"></i>
                            </span>
                            <select class="form-select border-0 border-bottom" id="roleSelect" name="role" required>
                                <option value="" selected disabled>Select Role</option>
                                <option value="owner">Owner</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                        <div class="invalid-feedback">Please select a role.</div>
                    </div>

                    <button type="submit" name="submit" class="btn btn_color btn-primary mx-auto w-100 mt-3 mb-2 p-2" id="submitAdmin" style="background-color: #FF902B; border-radius: 30px; border: none;">
                        Submit
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-0 px-md-3  mb-5">
    <div class="row justify-content-center mx-0">
        <div class="col-12 col-lg-10 px-0 px-md-3">
            <div class="shadow p-3 bg-body-tertiary rounded">
                <div class="d-flex justify-content-end m-2 mb-3 mb-md-5">
                    <button type="button" class="btn btn_color btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                        + Add Account
                    </button>
                </div>
                <div class="table-container">
                    <table id="userTable" class="display w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Employee</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Date Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Table data will be filled dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update User Modal -->
<div class="modal fade" id="updateUserModal" tabindex="-1" aria-labelledby="updateUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
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
                        <p class="error text-danger"></p>
                    </div>

                    <div class="mb-3">
                        <label for="updateUsername" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 border-bottom border-dark">
                                <i class="fa-regular fa-user"></i>
                            </span>
                            <input type="text" name="username" id="updateUsername" class="form-control border-0 border-bottom border-dark" disabled>
                        </div>
                        <p class="error text-danger"></p>
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

                    <button type="submit" class="btn btn_color btn-primary mx-auto w-100 mt-3 mb-2 p-2" style="background-color: #FF902B; border-radius: 30px; border: none;">
                        Update User
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
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

<script>
$(document).ready(function() {
    // Initialize DataTable only if not already initialized
    if (!$.fn.DataTable.isDataTable('#userTable')) {
        var userTable = $('#userTable').DataTable({
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            },
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            initComplete: function() {
                $('.dataTables_filter input').addClass('form-control');
            }
        });
    }

    // Variables to track validation state
    let isEmailValid = false;
    let isUsernameValid = false;
    let isPasswordValid = false;
    let isConfirmPasswordValid = false;
    let isRoleValid = false;

    // Password strength indicator
    $('#password').on('input', function() {
        const password = $(this).val();
        const strengthIndicator = $('#passwordStrength');
        
        if (password.length === 0) {
            strengthIndicator.text('');
            return;
        }
        
        // Check password strength
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        
        // Contains numbers
        if (/\d/.test(password)) strength++;
        
        // Contains special characters
        if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
        
        // Contains both uppercase and lowercase
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        
        // Determine strength level
        if (strength <= 2) {
            strengthIndicator.text('Weak').removeClass().addClass('password-strength strength-weak');
        } else if (strength <= 4) {
            strengthIndicator.text('Medium').removeClass().addClass('password-strength strength-medium');
        } else {
            strengthIndicator.text('Strong').removeClass().addClass('password-strength strength-strong');
        }
        
        // Validate password
        validatePassword();
        validateConfirmPassword();
    });

    // Confirm password validation
    $('#confirmPassword').on('input', function() {
        validateConfirmPassword();
    });

    // Email validation (check if exists)
    $('#email').on('blur', function() {
        validateEmail();
    });

    // Username validation (check if exists)
    $('#username').on('blur', function() {
        validateUsername();
    });

    // Role validation
    $('#roleSelect').on('change', function() {
        validateRole();
    });

    // In the form submission handler, replace the password hashing part:
    $('#createUserForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate all fields before submission
        validateEmail();
        validateUsername();
        validatePassword();
        validateConfirmPassword();
        validateRole();
        
        if (isEmailValid && isUsernameValid && isPasswordValid && isConfirmPasswordValid && isRoleValid) {
            // Submit form via AJAX - send raw password (will be hashed server-side)
            $.ajax({
                url: 'accountManagement.php', // Changed from create_user.php to self
                method: 'POST',
                data: {
                    action: 'create_user', // Added action parameter
                    email: $('#email').val(),
                    username: $('#username').val(),
                    password: $('#password').val(), // Send raw password (over HTTPS)
                    role: $('#roleSelect').val(),
                    date_created: new Date().toISOString().slice(0, 19).replace('T', ' ')
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#addAdminModal').modal('hide');
                        if ($.fn.DataTable.isDataTable('#userTable')) {
                            $('#userTable').DataTable().ajax.reload();
                        }
                        $('#createUserForm')[0].reset();
                        $('#passwordStrength').text('');
                        isEmailValid = isUsernameValid = isPasswordValid = isConfirmPasswordValid = isRoleValid = false;
                        alert('User created successfully!');
                    } else {
                        if (response.errors) {
                            if (response.errors.email) {
                                $('#email').addClass('is-invalid');
                                $('#emailError').text(response.errors.email).show();
                                isEmailValid = false;
                            }
                            if (response.errors.username) {
                                $('#username').addClass('is-invalid');
                                $('#usernameError').text(response.errors.username).show();
                                isUsernameValid = false;
                            }
                            if (response.errors.password) {
                                $('#password').addClass('is-invalid');
                                $('#passwordError').text(response.errors.password).show();
                                isPasswordValid = false;
                            }
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('Error creating user. Please try again.');
                }
            });
        } else {
            // Highlight the first invalid field
            if (!isEmailValid) $('#email').focus();
            else if (!isUsernameValid) $('#username').focus();
            else if (!isPasswordValid) $('#password').focus();
            else if (!isConfirmPasswordValid) $('#confirmPassword').focus();
            else if (!isRoleValid) $('#roleSelect').focus();
        }
    });

    function validateEmail() {
        const email = $('#email').val().trim();
        const emailInput = $('#email');
        const emailError = $('#emailError');
        
        if (!email) {
            emailInput.removeClass('is-valid').addClass('is-invalid');
            emailError.text('Email is required').show();
            isEmailValid = false;
            return;
        }
        
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            emailInput.removeClass('is-valid').addClass('is-invalid');
            emailError.text('Please enter a valid email address').show();
            isEmailValid = false;
            return;
        }
        
        // Check if email already exists
        $.ajax({
            url: 'accountManagement.php',
            method: 'POST',
            data: { 
                action: 'check_email',
                email: email 
            },
            dataType: 'json',
            success: function(response) {
                if (response.exists) {
                    emailInput.removeClass('is-valid').addClass('is-invalid');
                    emailError.text('Email already exists').show();
                    isEmailValid = false;
                } else {
                    emailInput.removeClass('is-invalid').addClass('is-valid');
                    emailError.hide();
                    $('.valid-feedback', emailInput.closest('.mb-3')).show();
                    isEmailValid = true;
                }
            },
            error: function() {
                emailInput.removeClass('is-valid is-invalid');
                emailError.text('Error checking email availability').show();
                isEmailValid = false;
            }
        });
    }

    function validateUsername() {
        const username = $('#username').val();
        const usernameInput = $('#username');
        const usernameError = $('#usernameError');
        
        if (!username) {
            usernameInput.removeClass('is-valid').addClass('is-invalid');
            usernameError.text('Username is required').show();
            isUsernameValid = false;
            return;
        }
        
        if (username.length < 4) {
            usernameInput.removeClass('is-valid').addClass('is-invalid');
            usernameError.text('Username must be at least 4 characters').show();
            isUsernameValid = false;
            return;
        }
        
        // Check if username already exists
        $.ajax({
            url: 'accountManagement.php',
            method: 'POST',
            data: { 
                action: 'check_username',
                username: username 
            },
            dataType: 'json',
            success: function(response) {
                if (response.exists) {
                    usernameInput.removeClass('is-valid').addClass('is-invalid');
                    usernameError.text('Username already exists').show();
                    isUsernameValid = false;
                } else {
                    usernameInput.removeClass('is-invalid').addClass('is-valid');
                    usernameError.hide();
                    $('.valid-feedback', usernameInput.closest('.mb-3')).show();
                    isUsernameValid = true;
                }
            },
            error: function() {
                usernameInput.removeClass('is-valid is-invalid');
                usernameError.text('Error checking username availability').show();
                isUsernameValid = false;
            }
        });
    }

    function validatePassword() {
        const password = $('#password').val();
        const passwordInput = $('#password');
        const passwordError = $('#passwordError');
        
        if (!password) {
            passwordInput.removeClass('is-valid').addClass('is-invalid');
            passwordError.text('Password is required').show();
            isPasswordValid = false;
            return;
        }
        
        if (password.length < 8) {
            passwordInput.removeClass('is-valid').addClass('is-invalid');
            passwordError.text('Password must be at least 8 characters').show();
            isPasswordValid = false;
            return;
        }
        
        passwordInput.removeClass('is-invalid').addClass('is-valid');
        passwordError.hide();
        $('.valid-feedback', passwordInput.closest('.mb-3')).show();
        isPasswordValid = true;
    }

    function validateConfirmPassword() {
        const password = $('#password').val();
        const confirmPassword = $('#confirmPassword').val();
        const confirmPasswordInput = $('#confirmPassword');
        const confirmPasswordError = $('#confirmPasswordError');
        
        if (!confirmPassword) {
            confirmPasswordInput.removeClass('is-valid').addClass('is-invalid');
            confirmPasswordError.text('Please confirm your password').show();
            isConfirmPasswordValid = false;
            return;
        }
        
        if (password !== confirmPassword) {
            confirmPasswordInput.removeClass('is-valid').addClass('is-invalid');
            confirmPasswordError.text('Passwords do not match').show();
            isConfirmPasswordValid = false;
            return;
        }
        
        confirmPasswordInput.removeClass('is-invalid').addClass('is-valid');
        confirmPasswordError.hide();
        $('.valid-feedback', confirmPasswordInput.closest('.mb-3')).show();
        isConfirmPasswordValid = true;
    }

    function validateRole() {
        const role = $('#roleSelect').val();
        const roleInput = $('#roleSelect');
        
        if (!role) {
            roleInput.removeClass('is-valid').addClass('is-invalid');
            isRoleValid = false;
        } else {
            roleInput.removeClass('is-invalid').addClass('is-valid');
            isRoleValid = true;
        }
    }

    // Reset validation when modal is closed
    $('#addAdminModal').on('hidden.bs.modal', function () {
        $('#createUserForm')[0].reset();
        $('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
        $('.invalid-feedback, .valid-feedback').hide();
        $('#passwordStrength').text('');
        isEmailValid = isUsernameValid = isPasswordValid = isConfirmPasswordValid = isRoleValid = false;
    });

    // Example: Handle edit button click
    $(document).on('click', '.editBtn', function() {
        const userId = $(this).data('id');
        const username = $(this).data('username');
        const email = $(this).data('email');
        const role = $(this).data('role');

        $('#updateUserId').val(userId);
        $('#updateUsername').val(username);
        $('#updateEmail').val(email);
        $('#updateRoleSelect').val(role);
        $('#updateUserModal').modal('show');
    });

    // Update user form submission
    $('#updateUserForm').on('submit', function(e) {
        e.preventDefault();
        
        const userId = $('#updateUserId').val();
        const role = $('#updateRoleSelect').val();
        
        $.ajax({
            url: 'accountManagement.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'update_user',
                id: userId,
                role: role
            },
            success: function(response) {
                if (response.success) {
                    $('#updateUserModal').modal('hide');
                    if ($.fn.DataTable.isDataTable('#userTable')) {
                        $('#userTable').DataTable().ajax.reload();
                    }
                    alert('User updated successfully!');
                } else {
                    alert('Error updating user: ' + (response.error || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error updating user:', error);
                alert('Error updating user. Please try again.');
            }
        });
    });

    // Example: Handle delete button click
    $(document).on('click', '.deleteBtn', function() {
        const userId = $(this).data('id');
        $('#confirmDeleteBtn').data('id', userId);
        $('#deleteUserModal').modal('show');
    });

    // Confirm delete action
    $('#confirmDeleteBtn').click(function() {
        const userId = $(this).data('id');
        // Perform the delete action using AJAX
        $.ajax({
            url: 'accountManagement.php',
            method: 'POST',
            dataType: 'json',
            data: { 
                action: 'delete_user',
                id: userId 
            },
            success: function(response) {
                if (response.success) {
                    $('#deleteUserModal').modal('hide');
                    if ($.fn.DataTable.isDataTable('#userTable')) {
                        $('#userTable').DataTable().ajax.reload();
                    }
                    alert('User deleted successfully!');
                } else {
                    alert('Error deleting user: ' + (response.error || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error deleting user:', error);
                alert('Error deleting user. Please try again.');
            }
        });
    });
});
</script>

<?php
// PHP Backend Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    include './../../connection/connection.php';
    
    $response = ['success' => false];
    
    // Handle different actions
    switch ($_POST['action'] ?? '') {
        case 'create_user':
            // Create user logic from create_user.php
            $response = ['success' => false, 'errors' => []];

            // Validate required fields
            $required = ['email', 'username', 'password', 'role'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    $response['errors'][$field] = ucfirst($field) . ' is required';
                }
            }

            // Validate email format
            if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $response['errors']['email'] = 'Invalid email format';
            }

            // Validate username length
            if (strlen($_POST['username']) < 4) {
                $response['errors']['username'] = 'Username must be at least 4 characters';
            }

            // Validate password strength
            if (strlen($_POST['password']) < 8) {
                $response['errors']['password'] = 'Password must be at least 8 characters';
            } elseif (!preg_match('/[A-Z]/', $_POST['password']) || 
                     !preg_match('/[a-z]/', $_POST['password']) || 
                     !preg_match('/[0-9]/', $_POST['password'])) {
                $response['errors']['password'] = 'Password must contain uppercase, lowercase letters and numbers';
            }

            // Validate role
            if (!in_array($_POST['role'], ['owner', 'staff'])) {
                $response['errors']['role'] = 'Invalid role selected';
            }

            // Only proceed if no validation errors
            if (empty($response['errors'])) {
                try {
                    // Check for existing email or username
                    $stmt = $pdo->prepare("SELECT id FROM admin_list WHERE email = ? OR username = ?");
                    $stmt->execute([$_POST['email'], $_POST['username']]);
                    
                    if ($stmt->fetch()) {
                        // Check which one exists
                        $stmt = $pdo->prepare("SELECT id FROM admin_list WHERE email = ?");
                        $stmt->execute([$_POST['email']]);
                        if ($stmt->fetch()) {
                            $response['errors']['email'] = 'Email already exists';
                        }
                        
                        $stmt = $pdo->prepare("SELECT id FROM admin_list WHERE username = ?");
                        $stmt->execute([$_POST['username']]);
                        if ($stmt->fetch()) {
                            $response['errors']['username'] = 'Username already exists';
                        }
                    } else {
                        // Hash the password with bcrypt
                        $passwordHash = password_hash($_POST['password'], PASSWORD_BCRYPT);
                        
                        if ($passwordHash === false) {
                            throw new Exception('Password hashing failed');
                        }
                        
                        // Insert new user
                        $stmt = $pdo->prepare("INSERT INTO admin_list 
                                            (email, username, password, role, date_created) 
                                            VALUES (?, ?, ?, ?, NOW())");
                        
                        $success = $stmt->execute([
                            $_POST['email'],
                            $_POST['username'],
                            $passwordHash,
                            $_POST['role']
                        ]);
                        
                        if ($success) {
                            $response['success'] = true;
                            $response['message'] = 'User created successfully';
                            $response['user_id'] = $pdo->lastInsertId();
                        } else {
                            $response['error'] = 'Failed to create user';
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Database error: " . $e->getMessage());
                    $response['error'] = 'Database error occurred. Please try again.';
                }
            }
            break;
            
        case 'check_email':
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM admin_list WHERE email = ?");
            $stmt->execute([$_POST['email']]);
            $response['exists'] = (bool)$stmt->fetch();
            $response['success'] = true;
            break;
            
        case 'check_username':
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM admin_list WHERE username = ?");
            $stmt->execute([$_POST['username']]);
            $response['exists'] = (bool)$stmt->fetch();
            $response['success'] = true;
            break;
            
        case 'update_user':
            // Update user role
            $stmt = $pdo->prepare("UPDATE admin_list SET role = ? WHERE id = ?");
            $success = $stmt->execute([$_POST['role'], $_POST['id']]);
            $response['success'] = $success;
            if (!$success) {
                $response['error'] = 'Failed to update user';
            }
            break;
            
        case 'delete_user':
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM admin_list WHERE id = ?");
            $success = $stmt->execute([$_POST['id']]);
            $response['success'] = $success;
            if (!$success) {
                $response['error'] = 'Failed to delete user';
            }
            break;
            
        default:
            $response['error'] = 'Invalid action';
    }
    
    echo json_encode($response);
    exit;
}
?>
</body>
</html>