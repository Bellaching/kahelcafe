<?php 
include './../inc/header.php';
include './../../connection/connection.php'; // Ensure this path is correct
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer();
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'jewellsalongcong09@gmail.com'; // Replace with your email
$mail->Password = 'xmjw ytju kkeu adoj'; // Replace with your App Password
$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$registrationSuccess = false; // Track registration status
$errors = []; // Array to hold validation errors

// Handle registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $email = $_POST['email'];
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $contact_number = trim($_POST['contact_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $firstname = ucfirst(strtolower($firstname)); 
    $lastname = ucfirst(strtolower($lastname)); 

    // Validation
    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    } else {
        // Check if the email already exists in the database
        $stmt = $conn->prepare("SELECT * FROM client WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors['email'] = "Email already registered.";
        }
    }

    // Validate first name
    if (empty($firstname)) {
        $errors['firstname'] = "First name is required.";
    } elseif (!preg_match("/^[a-zA-Z]{3,}$/", $firstname)) {
        $errors['firstname'] = "First name must be at least 3 letters and contain only alphabets.";
    }

    // Validate last name
    if (empty($lastname)) {
        $errors['lastname'] = "Last name is required.";
    } elseif (!preg_match("/^[a-zA-Z]{3,}$/", $lastname)) {
        $errors['lastname'] = "Last name must be at least 3 letters and contain only alphabets.";
    }

    // Validate contact number
    if (empty($contact_number)) {
        $errors['contact_number'] = "Contact number is required.";
    } elseif (!preg_match("/^09[0-9]{9}$/", $contact_number)) {
        $errors['contact_number'] = "Contact number must start with 09 and be 11 digits long.";
    }

    // Validate password
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    }

    // Validate confirm password
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

   
  // If there are no errors, proceed with registration
if (empty($errors)) {
    $verificationCode = generateVerificationCode();
    $expiryTime = date("Y-m-d H:i:s", strtotime('+2 minutes'));

    // Directly use the password without hashing
    $plainPassword = $password; // Use the plain password

    // Insert the new email and verification code into the database
    $stmt = $conn->prepare("INSERT INTO client (email, firstname, lastname, contact_number, password, verification_code, code_expiry) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $email, $firstname, $lastname, $contact_number, $plainPassword, $verificationCode, $expiryTime);

    if ($stmt->execute()) {
        // Prepare verification link
        $mail->addAddress($email); // Send to the registered email
        $mail->setFrom('jewellsalongcong09@gmail.com', 'Jewell Salongcong');
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification';
        $mail->Body = "Your verification code is: $verificationCode. It is valid for 2 minutes.";

        if ($mail->send()) {
            $registrationSuccess = true; // Set registration success only after sending the email
        } else {
            $errors['email'] = "Error sending verification email: " . $mail->ErrorInfo;
        }
    } else {
        $errors['database'] = "Error executing insert statement: " . $stmt->error;
    }
}

}

function generateVerificationCode() {
    return bin2hex(random_bytes(5)); // Generates a random 10-character verification code
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>User Registration</title>

    <style>
        .register {
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

        .password-container {
            position: relative;
        }

        .eye-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #ADADAD; /* Eye icon color */
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
        <!-- Header -->
        <div class="header mb-3 d-flex justify-content-between align-items-center">
            <h1 class="modal-title header-title">Sign Up</h1>
            <div class="text-end">
                <small>Have an account?</small><br>
                <a href="login.php">Sign In</a>
            </div>
        </div>

        <!-- Registration Form -->
        <div class="form-section p-4">
            <?php if (!$registrationSuccess): ?>
                <form action="" method="POST" id="createUserForm" class="form-register">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email">
                            <?php if (isset($errors['email'])): ?>
                                <p class="error text-danger"><?php echo $errors['email']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="firstname" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstname" name="firstname" placeholder="Enter first name">
                            <?php if (isset($errors['firstname'])): ?>
                                <p class="error text-danger"><?php echo $errors['firstname']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="lastname" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastname" name="lastname" placeholder="Enter last name">
                            <?php if (isset($errors['lastname'])): ?>
                                <p class="error text-danger"><?php echo $errors['lastname']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="contact_number" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number" placeholder="Enter contact number">
                            <?php if (isset($errors['contact_number'])): ?>
                                <p class="error text-danger"><?php echo $errors['contact_number']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="password" class="form-label">Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter password">
                                <i class="fas fa-eye eye-icon" id="togglePassword"></i>
                            </div>
                            <span id="password-strength" style="font-size: 12px;"></span>
                            <?php if (isset($errors['password'])): ?>
                                <p class="error text-danger"><?php echo $errors['password']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm password">
                                <i class="fas fa-eye eye-icon" id="toggleConfirmPassword"></i>
                            </div>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <p class="error text-danger"><?php echo $errors['confirm_password']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <button type="submit" name="register" class="btn btn-custom container-fluid">Register</button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-success">Registration successful! Please check your email to verify your account.</div>
                <a href="verification.php?email=<?php echo urlencode($email); ?>">Go to Verification</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script>
    $(document).ready(function() {
        const passwordField = $('#password');
        const confirmPasswordField = $('#confirm_password');
        const passwordStrengthText = $('#password-strength');

        passwordField.on('input', function() {
            const password = $(this).val();
            let strength = '';

            if (password.length < 6) {
                strength = 'Weak';
                passwordStrengthText.css('color', 'red');
            } else if (password.length < 10) {
                strength = 'Medium';
                passwordStrengthText.css('color', 'orange');
            } else {
                strength = 'Strong';
                passwordStrengthText.css('color', 'green');
            }

            passwordStrengthText.text(`Password Strength: ${strength}`);
        });

        // Toggle password visibility
        const togglePassword = document.querySelector('#togglePassword');
        const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');

        togglePassword.addEventListener('click', function() {
            const isPasswordField = passwordField[0].type === 'password';
            passwordField[0].type = isPasswordField ? 'text' : 'password';
            this.classList.toggle('fa-eye-slash', !isPasswordField);
        });

        toggleConfirmPassword.addEventListener('click', function() {
            const isConfirmPasswordField = confirmPasswordField[0].type === 'password';
            confirmPasswordField[0].type = isConfirmPasswordField ? 'text' : 'password';
            this.classList.toggle('fa-eye-slash', !isConfirmPasswordField);
        });
    });
</script>
</body>
</html>
