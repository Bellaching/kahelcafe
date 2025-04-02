<?php
include './../inc/header.php';
include './../../connection/connection.php'; // Ensure this path is correct

session_start();

$errors = []; // Array to hold errors
$emailSent = false; // Track if email was sent successfully
$verificationSuccess = false; // Track verification status
$passwordUpdated = false; // Track password reset status
$resendSuccess = false; // Track resend status

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to generate a 6-digit numeric verification code
function generateVerificationCode() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT); // 6-digit code
}

// Function to send verification email
function sendVerificationEmail($email, $verificationCode) {
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
    require 'PHPMailer/src/Exception.php';

    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'jewellsalongcong09@gmail.com'; // Replace with your email
    $mail->Password = 'xmjw ytju kkeu adoj'; // Replace with your App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('jewellsalongcong09@gmail.com', 'Jewell Salongcong');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Email Verification Code';
    $mail->Body = "Your new verification code is: $verificationCode. It is valid for 2 minutes.";

    if ($mail->send()) {
        return true;
    } else {
        return false;
    }
}

// Step 3: Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    if (!isset($_SESSION['verified_email'])) {
        header("Location: forgot-password.php"); // Redirect if email is not verified
        exit();
    }

    $email = $_SESSION['verified_email'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate passwords
    if (empty($newPassword)) {
        $errors['new_password'] = "New password is required.";
    } else {
        $passwordStrength = checkPasswordStrength($newPassword);
        if ($passwordStrength['strength'] == 'weak') {
            $errors['new_password'] = "Password is too weak. " . implode(" ", $passwordStrength['messages']);
        } elseif ($passwordStrength['strength'] == 'medium') {
            // Allow medium strength passwords
        } elseif ($passwordStrength['strength'] == 'strong') {
            // Allow strong passwords
        }
    }

    if (empty($confirmPassword)) {
        $errors['confirm_password'] = "Confirm password is required.";
    } elseif ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    if (empty($errors)) {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the password in the database
        $stmt = $conn->prepare("UPDATE client SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);
        $stmt->execute();

        $passwordUpdated = true;
        unset($_SESSION['verified_email']); // Clear session data

        // Redirect to login page after successful password reset
       echo "<script>
    alert('Successfully reset password!');
    window.location.href = 'login.php';
</script>";
        exit();
    }
}

function checkPasswordStrength($password) {
    $strength = 0;
    $messages = [];

    // Check length
    if (strlen($password) >= 8) {
        $strength += 1;
    } else {
        $messages[] = "Password must be at least 8 characters long.";
    }

    // Check for at least one capital letter
    if (preg_match('/[A-Z]/', $password)) {
        $strength += 1;
    } else {
        $messages[] = "Password must contain at least one capital letter.";
    }

    // Determine strength level
    if ($strength == 2) {
        return ['strength' => 'strong', 'messages' => $messages];
    } elseif ($strength == 1) {
        return ['strength' => 'medium', 'messages' => $messages];
    } else {
        return ['strength' => 'weak', 'messages' => $messages];
    }
}

// Step 1: Handle email submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_email'])) {
    $email = $_POST['email'];

    // Validate email
    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    if (empty($errors)) {
        // Check if the email exists in the database
        $stmt = $conn->prepare("SELECT * FROM client WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Generate a 6-digit numeric verification code
            $verificationCode = generateVerificationCode();
            $expiryTime = date("Y-m-d H:i:s", strtotime('+2 minutes')); // Code expires in 2 minutes

            // Update the database with the new verification code and expiry time
            $stmt = $conn->prepare("UPDATE client SET verification_code = ?, code_expiry = ? WHERE email = ?");
            $stmt->bind_param("sss", $verificationCode, $expiryTime, $email);
            $stmt->execute();

            // Send the verification code via email
            if (sendVerificationEmail($email, $verificationCode)) {
                $emailSent = true;
                $_SESSION['reset_email'] = $email; // Store email in session for verification
            } else {
                $errors['email'] = "Failed to send verification code. Please try again.";
            }
        } else {
            $errors['email'] = "No account found with that email.";
        }
    }
}

// Step 2: Handle verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify'])) {
    if (!isset($_SESSION['reset_email'])) {
        header("Location: forgot-password.php"); // Redirect if email is not set
        exit();
    }

    $email = $_SESSION['reset_email'];
    $inputCode = $_POST['verification_code'];

    // Check if the code and email match in the database
    $stmt = $conn->prepare("SELECT * FROM client WHERE email = ? AND verification_code = ?");
    $stmt->bind_param("ss", $email, $inputCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $verificationSuccess = true;
        $_SESSION['verified_email'] = $email; // Store verified email in session

        // Mark the email as verified in the database
        $stmt = $conn->prepare("UPDATE client SET verified = 1 WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
    } else {
        $errors['verification_code'] = "Invalid verification code.";
    }
}

// Handle resend verification code
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resend'])) {
    if (!isset($_SESSION['reset_email'])) {
        header("Location: forgot-password.php"); // Redirect if email is not set
        exit();
    }

    $email = $_SESSION['reset_email'];
    $verificationCode = generateVerificationCode(); // Generate a new 6-digit code
    $expiryTime = date("Y-m-d H:i:s", strtotime('+2 minutes')); // Code expires in 2 minutes

    // Update the database with the new verification code and expiry time
    $stmt = $conn->prepare("UPDATE client SET verification_code = ?, code_expiry = ? WHERE email = ?");
    $stmt->bind_param("sss", $verificationCode, $expiryTime, $email);
    $stmt->execute();

    // Send the new verification code via email
    if (sendVerificationEmail($email, $verificationCode)) {
        $resendSuccess = true;
    } else {
        $errors['resend'] = "Failed to resend verification code. Please try again.";
    }
}

// Step 3: Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    if (!isset($_SESSION['verified_email'])) {
        header("Location: forgot-password.php"); // Redirect if email is not verified
        exit();
    }

    $email = $_SESSION['verified_email'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate passwords
    if (empty($newPassword)) {
        $errors['new_password'] = "New password is required.";
    } elseif (strlen($newPassword) < 8) {
        $errors['new_password'] = "Password must be at least 8 characters long.";
    }

    if (empty($confirmPassword)) {
        $errors['confirm_password'] = "Confirm password is required.";
    } elseif ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    if (empty($errors)) {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the password in the database
        $stmt = $conn->prepare("UPDATE client SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);
        $stmt->execute();

        $passwordUpdated = true;
        unset($_SESSION['verified_email']); // Clear session data

        // Redirect to login page after successful password reset
        header("Location: login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .form-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 400px;
        }
    </style>
</head>
<body>
    <div class="form-container container-sm w-30 w-md-50 w-lg-30 mx-auto p-4 shadow-sm rounded">
        <?php if (!$emailSent && !$verificationSuccess && !$passwordUpdated): ?>
            <!-- Step 1: Input Email -->
            <a href="login.php" class="text-dark fs-6 d-flex align-items-center mb-3" style="text-decoration: none;">
                <i class="fa fa-angle-left me-2"></i> Back to login
            </a>
            <h1 class="fw-bold mb-3 fs-4">Forgot Password?</h1>
            <p class="fs-6 text-muted">Don’t worry, happens to all of us. Enter your email below to recover your password.</p>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email">
                    <?php if (isset($errors['email'])): ?>
                        <div class="text-danger small"><?php echo $errors['email']; ?></div>
                    <?php endif; ?>
                </div>
                <div class="d-grid">
                    <button type="submit" name="send_email" class="btn btn-warning text-white" style="background-color: #FF902B; color: white;">Submit</button>
                </div>
            </form>
        <?php elseif ($emailSent && !$verificationSuccess && !$passwordUpdated): ?>
            <!-- Step 2: Verify Code -->
            <a href="login.php" class="text-dark fs-6 d-flex align-items-center mb-3" style="text-decoration: none;">
                <i class="fa fa-angle-left me-2"></i> Back to login
            </a>
            <h1 class="fw-bold mb-3 fs-4">Verify Code</h1>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="verification_code" class="form-label text-muted">An authentication code has been sent to your email.</label>
                    <input type="text" class="form-control" id="verification_code" name="verification_code" maxlength="6" required>
                    <?php if (isset($errors['verification_code'])): ?>
                        <div class="text-danger small"><?php echo $errors['verification_code']; ?></div>
                    <?php endif; ?>
                </div>
                <div class=" mt-3 fs-6">
                    <p class="text-muted">Didn’t receive a code? 
                        <button type="submit" name="resend" class="btn btn-link p-0" style="color: #FF8682;">Resend</button>
                    </p>
                </div>
                <div class="d-grid">
                    <button type="submit" name="verify" class="btn btn-primary" style="background-color: #FF902B; color: white; border:none;">Verify</button>
                </div>
               
                <?php if ($resendSuccess): ?>
                    <div class="text-center">
                        <p class="text-success">New verification code sent successfully!</p>
                    </div>
                <?php endif; ?>
            </form>
            <?php elseif ($verificationSuccess && !$passwordUpdated): ?>
    <!-- Step 3: Reset Password -->
    <a href="login.php" class="text-dark fs-6 d-flex align-items-center mb-3" style="text-decoration: none;">
                <i class="fa fa-angle-left me-2"></i> Back to login
            </a>

    <h1 class=" mb-3 fs-4">Set a password</h1>
    <p  class="form-label text-muted">Your previous password has been reseted. Please set a new password for your account.</p>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="new_password" class="form-label">New Password</label>
            <div class="input-group">
                <input type="password" class="form-control" id="new_password" name="new_password" oninput="checkPasswordStrength(this.value)">
                <button type="button" class="btn btn-outline-secondary" style="border-color: #ccc; color: #6c757d;" onclick="togglePasswordVisibility('new_password')">
                    <i class="fa fa-eye"></i>
                </button>
            </div>
            <?php if (isset($errors['new_password'])): ?>
                <div class="text-danger small"><?php echo $errors['new_password']; ?></div>
            <?php endif; ?>
            <div id="password-strength-feedback" class="small mt-1"></div>
        </div>
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <div class="input-group">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                <button type="button" class="btn btn-outline-secondary" style="border-color: #ccc; color: #6c757d;" onclick="togglePasswordVisibility('confirm_password')">

                    <i class="fa fa-eye"></i>
                </button>
            </div>
            <?php if (isset($errors['confirm_password'])): ?>
                <div class="text-danger small"><?php echo $errors['confirm_password']; ?></div>
            <?php endif; ?>
        </div>
        <div class="d-grid">
            <button type="submit" name="reset_password" class="btn btn-primary" style="background-color: #FF902B; color: white; border:none;">Reset Password</button>
        </div>
    </form>
<?php elseif ($passwordUpdated): ?>
    <!-- Password Updated Successfully -->
    <div class="alert alert-success text-center">Password updated successfully! <a href="login.php">Login here</a>.</div>
<?php endif; ?>
    </div>

    <script>
function togglePasswordVisibility(inputId) {
    const passwordInput = document.getElementById(inputId);
    const eyeIcon = passwordInput.nextElementSibling.querySelector('i');

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = "password";
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}

function checkPasswordStrength(password) {
    const feedback = document.getElementById('password-strength-feedback');
    let strength = 0;
    let messages = [];

    // Check length
    if (password.length >= 8) {
        strength += 1;
    } else {
        messages.push("Password must be at least 8 characters long.");
    }

    // Check for at least one capital letter
    if (/[A-Z]/.test(password)) {
        strength += 1;
    } else {
        messages.push("Password must contain at least one capital letter.");
    }

    // Determine strength level
    if (strength == 2) {
        feedback.textContent = "Strong password.";
        feedback.style.color = "green";
    } else if (strength == 1) {
        feedback.textContent = "Medium strength password. " + messages.join(" ");
        feedback.style.color = "orange";
    } else {
        feedback.textContent = "Weak password. " + messages.join(" ");
        feedback.style.color = "red";
    }
}
</script>
</body>
</html>