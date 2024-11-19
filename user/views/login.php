<?php
include './../inc/header.php';
include './../../connection/connection.php'; // Ensure this path is correct

session_start();

$loginSuccess = false;
$errors = []; // Array to hold login errors

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate email
    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    // Validate password
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    }

    if (empty($errors)) {
        // Check if the email exists in the database
        $stmt = $conn->prepare("SELECT * FROM client WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Check if the user is verified
            if ($user['verified'] == 1) { // Assuming 'verified' is the column name
                // Check the password (plain text)
                if ($password == $user['password']) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['verified'] = $user['verified']; // Store verification status
                    $loginSuccess = true;
                    header("Location: index.php"); // Redirect to a secure page after login
                    exit();
                } else {
                    $errors['password'] = "Invalid password.";
                }
            } else {
                $errors['email'] = "Your email is not verified. <a href='verification.php'>Click here to verify.</a>";
            }
        } else {
            $errors['email'] = "No account found with that email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./../styles.css">
    <title>Login</title>
    <style>
        /* Add your custom styles here */
        .login{
            background-color: #F2F4F7;
        }
        .form-container {
            background-color: #FFFFFF;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .header-title {
            font-size: 2.5rem;
        }
        .form-control {
            border: 1px solid #ADADAD;
            border-radius: 8px;
            padding: 15px;
            position: relative;
        }
        .btn-custom {
            background-color: #E48700;
            color: #fff;
            border-radius: 8px;
            padding: 12px;
        }
        .btn-custom:hover {
            background-color: #D47600;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="col-md-8 col-lg-6 form-container p-5">
            <div class="header mb-3 d-flex justify-content-between align-items-center">
                <h1 class="modal-title header-title">Login</h1>
                <div class="text-end">
                    <small>No account?</small><br>
                    <a href="./../../user/views/register.php">Sign up</a>
                </div>
            </div>
            <div class="form-section p-4">
                <form method="POST" action="login.php">
                    <div class="form-group m-3">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
                        <?php if (isset($errors['email'])): ?>
                            <p class="error text-danger"><?php echo $errors['email']; ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="form-group m-3">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                        <?php if (isset($errors['password'])): ?>
                            <p class="error text-danger"><?php echo $errors['password']; ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="form-group m-3">
                        <button type="submit" name="login" class="btn btn-custom container-fluid">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
