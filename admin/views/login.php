<?php
session_start();
include './../../connection/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = $conn->prepare("SELECT id, username, password, role FROM admin_list WHERE username = ?");
    $query->bind_param('s', $username);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Password is correct
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            echo json_encode(['success' => true, 'role' => $user['role']]);
            exit();
        } else {
            // Password is incorrect
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Login</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f7f7f7;
        }
        .login-container {
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            background-color: white;
            width: 400px;
        }
        .btn-custom {
            background-color: #FF902B;
            color: white;
            border: none;
        }
        .btn-custom:hover {
            background-color: #e07d24;
        }
        #errorMessage {
            height: 20px;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <h2 class="text-center mb-4">Login</h2>
        <form id="loginForm">
            <input type="hidden" name="action" value="login">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-custom w-100 mt-3">Login</button>
            <div id="errorMessage" class="mt-3 text-danger text-center"></div>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in...'
                );
                $('#errorMessage').text('').hide();
                
                $.ajax({
                    type: 'POST',
                    url: window.location.href,
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.role === 'admin' ? 'admin.php' : 'index.php';
                        } else {
                            $('#errorMessage').text(response.message).show();
                            submitBtn.prop('disabled', false).text('Login');
                        }
                    },
                    error: function() {
                        $('#errorMessage').text('An error occurred. Please try again.').show();
                        submitBtn.prop('disabled', false).text('Login');
                    }
                });
            });
        });
    </script>
</body>
</html>