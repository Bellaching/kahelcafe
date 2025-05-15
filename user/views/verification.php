<?php
// Start session first
session_start();

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Include files
include './../inc/header.php';
include './../../connection/connection.php';
require 'PHPMailer/src/PHPMailer.php'; 
require 'PHPMailer/src/SMTP.php'; 
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Initialize variables with safe defaults
$verificationSuccess = false;
$verificationError = "";
$resendSuccess = false;
$email = $_GET['email'] ?? '';
$expiryTime = time();

// Handle verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify'])) {
    $email = $_POST['email'] ?? '';
    $inputCode = trim($_POST['verification_code'] ?? '');

    // Debug logging
    error_log("Verification attempt - Email: $email, Code: $inputCode");

    if (empty($email) || empty($inputCode)) {
        $verificationError = "Email and verification code are required.";
    } else {
        // Check if the code and email match in the database
        $stmt = $conn->prepare("SELECT verification_code, code_expiry FROM client WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $dbCode = $row['verification_code'];
            $expiryTime = strtotime($row['code_expiry']);

            // Debug logging
            error_log("DB Code: $dbCode, Input Code: $inputCode");

            if (time() > $expiryTime) {
                $verificationError = "Verification code has expired. Please request a new one.";
            } elseif ($inputCode === $dbCode) {
                // Successful verification
                $verificationSuccess = true;
                $stmt = $conn->prepare("UPDATE client SET verified = 1, verification_code = NULL WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
            } else {
                $verificationError = "Invalid verification code.";
            }
        } else {
            $verificationError = "No verification request found for this email.";
        }
    }
}

// Handle resend verification code
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resend'])) {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $verificationError = "Email is required to resend verification code.";
    } else {
        $verificationCode = bin2hex(random_bytes(5)); // 10-character code
        $expiryTime = date("Y-m-d H:i:s", strtotime('+2 minutes'));

        // Update the verification code and expiry time
        $stmt = $conn->prepare("UPDATE client SET verification_code = ?, code_expiry = ? WHERE email = ?");
        $stmt->bind_param("sss", $verificationCode, $expiryTime, $email);
        
        if ($stmt->execute()) {
            if (sendVerificationEmail($email, $verificationCode)) {
                $resendSuccess = true;
                $expiryTime = strtotime($expiryTime); // Update for the countdown
            } else {
                $verificationError = "Error sending verification email. Please try again.";
            }
        } else {
            $verificationError = "Error updating verification code. Please try again.";
        }
    }
}

function sendVerificationEmail($email, $verificationCode) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'kahelcafeweb@gmail.com';
        $mail->Password = 'your_app_password'; // Use app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Recipients
        $mail->setFrom('kahelcafeweb@gmail.com', 'Kahel Cafe');
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification Code';
        $mail->Body = "Your verification code is: <strong>$verificationCode</strong><br>
                      This code will expire in 2 minutes.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error sending email: " . $mail->ErrorInfo);
        return false;
    }
}

// Get current expiry time if available
if (!empty($email)) {
    $stmt = $conn->prepare("SELECT code_expiry FROM client WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $expiryTime = strtotime($row['code_expiry']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Email Verification</title>
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
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            width: 400px;
        }
        .btn-resend {
            background: none;
            border: none;
            color: #FF8682;
            padding: 0;
        }
        .btn-verify {
            background-color: #FF902B;
            color: #ffffff;
            width: 100%;
        }
        .text-warning {
            color: #FF902B !important;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Verify code</h1>
        <small class="mt-3 mb-3">An authentication code has been sent to your email.</small>
        
        <?php if ($verificationSuccess): ?>
            <div class='alert alert-success'>Email verified successfully! Redirecting to login page...</div>
            <script>
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 3000);
            </script>
        <?php else: ?>
            <form action="" method="POST">
                <div class="form-group">
                    <label for="verification_code">Verification Code</label>
                    <input type="text" class="form-control" name="verification_code" id="verification_code" >
                    <?php if ($verificationError): ?>
                        <div class="alert alert-danger mt-2"><?php echo $verificationError; ?></div>
                    <?php endif; ?>
                    <p id="countdown" class="text-warning mt-2">Loading timer...</p>
                </div>
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="mt-4">
                    <p>Didn't receive a code? 
                        <button type="submit" name="resend" id="resendBtn" class="btn btn-resend">Resend</button>
                    </p>
                    <button type="submit" name="verify" class="btn btn-verify">Verify</button>
                </div>
                
                <?php if ($resendSuccess): ?>
                    <div class="alert alert-success mt-3">New verification code sent successfully!</div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>

    <script>
        let timer;
        let expiryTime = <?php echo $expiryTime * 1000; ?>;
        
        function updateCountdown() {
            const now = new Date().getTime();
            const remainingTime = expiryTime - now;
            const countdownElement = document.getElementById("countdown");
            
            if (remainingTime <= 0) {
                clearInterval(timer);
                countdownElement.innerHTML = "Verification code has expired!";
                countdownElement.className = "text-danger";
            } else {
                const minutes = Math.floor((remainingTime % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((remainingTime % (1000 * 60)) / 1000);
                countdownElement.innerHTML = `Code expires in: ${minutes}m ${seconds}s`;
            }
        }
        
        function restartTimer(newExpiryTime) {
            expiryTime = newExpiryTime * 1000;
            clearInterval(timer);
            updateCountdown();
            timer = setInterval(updateCountdown, 1000);
        }
        
        document.addEventListener("DOMContentLoaded", () => {
            updateCountdown();
            timer = setInterval(updateCountdown, 1000);
            
            const resendBtn = document.getElementById("resendBtn");
            if (resendBtn) {
                resendBtn.addEventListener("click", function() {
                    restartTimer(<?php echo strtotime('+2 minutes') * 1000; ?>);
                });
            }
        });
    </script>
</body>
</html>