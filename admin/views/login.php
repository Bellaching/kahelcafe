<?php 



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
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <h2 class="text-center">Login</h2>
        <form id="loginForm">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-custom w-100">Login</button>
        </form>
    </div>

    <script>
    $(document).ready(function() {
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();

            let username = $('#username').val();
            let password = $('#password').val();

            $.ajax({
                url: './../../admin/user/login_handler.php',
                type: 'POST',
                dataType: 'json',
                cache: false,
                data: {
                    action: 'login',
                    username: username,
                    password: password
                },
                success: function(response) {
                    console.log('Response:', response);
                    if (response.success) {
                        window.location.href = "./../views/index.php";
                    } else {
                        alert('Login failed: ' + response.message);
                        $('#password').val('');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', xhr.responseText);
                    alert('An error occurred. Please try again.');
                }
            });
        });
    });
    </script>
</body>
</html>