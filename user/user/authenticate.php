<?php
session_start();
include './../../connection/connection.php'; // Ensure this path is correct

$loginSuccess = false;
$errors = [];

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
            if ($user['verified'] == 1) {
                // Use password hashing for checking passwords
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['verified'] = $user['verified'];
                    header("Location: index.php");
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

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
