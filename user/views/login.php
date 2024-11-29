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
                    $errors['password'] = "Invalid password.";
                } else {
                 

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['verified'] = $user['verified']; // Store verification status
                    $loginSuccess = true;
                    header("Location: index.php"); // Redirect to a secure page after login
                    exit();
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
     .form-container {
            background-color: #FFFFFF;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .btn-login{
            background-color: #FF902B;
        }

        
</style>
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h4 text-center mb-4">Login</h1>
                        <form method="POST" action="login.php" class="border-0">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="text-danger small"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="text-danger small"><?php echo $errors['password']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="login" class="btn btn-warning btn-login text-light">Login</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <small>No account? <a href="./../../user/views/register.php">Sign up</a></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
