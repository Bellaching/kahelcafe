<?php
include './../inc/header.php';
include './../../connection/connection.php'; // Ensure this path is correct
require 'PHPMailer/src/PHPMailer.php'; 
require 'PHPMailer/src/SMTP.php'; 
require 'PHPMailer/src/Exception.php'; 

session_start();

if (!isset($_GET['email']) || empty($_GET['email'])) {
    header("Location: login.php"); // Redirect to login page if email is missing
    exit();
}


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$verificationSuccess = false; // Track verification status
$verificationError = ""; // Track verification errors
$resendSuccess = false; // Track resend status

// Handle verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify'])) {
    $email = $_POST['email'];
    $inputCode = $_POST['verification_code'];

    // Check if the code and email match in the database
    $stmt = $conn->prepare("SELECT * FROM client WHERE email = ? AND verification_code = ?");
    $stmt->bind_param("ss", $email, $inputCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Email verified successfully
        $verificationSuccess = true; // Set verification success
        // Update the database to mark the email as verified
        $stmt = $conn->prepare("UPDATE client SET verified = 1 WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
    } else {
        $verificationError = "Invalid verification code.";
    }
}

// Handle resend verification code
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resend'])) {
    $email = $_POST['email'];
    $verificationCode = generateVerificationCode();
    $expiryTime = date("Y-m-d H:i:s", strtotime('+2 minutes'));

    // Update the verification code and expiry time in the database
    $stmt = $conn->prepare("UPDATE client SET verification_code = ?, code_expiry = ? WHERE email = ?");
    $stmt->bind_param("sss", $verificationCode, $expiryTime, $email);
    
    if ($stmt->execute()) {
        // Send the new verification code to the email
        sendVerificationEmail($email, $verificationCode);
        $resendSuccess = true; // Mark resend success
    }
}

function generateVerificationCode() {
    return bin2hex(random_bytes(5)); // Generates a random 10-character verification code
}

function sendVerificationEmail($email, $verificationCode) {
    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'kahelcafeweb@gmail.com'; // Replace with your email
    $mail->Password = 'qccb avlu ejjb fkmv'; // Replace with your App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('kahelcafeweb@gmail.com', 'Kahel Cafe');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Email Verification Code';
    $mail->Body = "Your new verification code is: $verificationCode. It is valid for 2 minutes.";

    // Enable debugging output
    $mail->SMTPDebug = 0; // 1 = errors and messages, 2 = messages only

    // Send the email and check for errors
    if (!$mail->send()) {
        // Log the error
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false; // Return false if sending failed
    }
    return true; // Return true if sending succeeded
}

$expiryTime = isset($row['code_expiry']) ? strtotime($row['code_expiry']) : time(); // Get the expiry time
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
            height: 100vh; /* Full viewport height */
            background-color: #f8f9fa; /* Light background */
        }
        .form-container {
            background-color: white;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* Subtle shadow */
            border-radius: 10px; /* Rounded corners */
            width: 400px; /* Fixed width for form */
        }

        .btn-resend{
            background-color: none;
            border: none;
            color: #FF8682;
        }

        .btn-verify{
            background-color: #FF902B;
            color: #ffffff;
        }
    </style>
    <script>
        let timer; // Variable to hold the timer
        let expiryTime = <?php echo $expiryTime * 1000; ?>; // Convert to milliseconds
        const countdownDisplay = document.addEventListener("DOMContentLoaded", () => {
            const countdownElement = document.getElementById("countdown");
            const updateCountdown = () => {
                const now = new Date().getTime();
                const remainingTime = expiryTime - now;

                if (remainingTime <= 0) {
                    clearInterval(timer);
                    countdownElement.innerHTML = "2 minutes before the code expire!";
                } else {
                    const seconds = Math.floor((remainingTime % (1000 * 60)) / 1000);
                    countdownElement.innerHTML = "Time remaining: " + seconds + " seconds";
                }
            };

            timer = setInterval(updateCountdown, 1000); // Update countdown every second
            updateCountdown(); // Initial call to display immediately
        });

        // Restart timer on resend
        function restartTimer(newExpiryTime) {
            expiryTime = newExpiryTime * 1000; // Update the expiry time
            clearInterval(timer); // Clear the previous timer
            timer = setInterval(() => {
                const now = new Date().getTime();
                const remainingTime = expiryTime - now;

                if (remainingTime <= 0) {
                    clearInterval(timer);
                    document.getElementById("countdown").innerHTML = "Code expired!";
                } else {
                    const seconds = Math.floor((remainingTime % (1000 * 60)) / 1000);
                    document.getElementById("countdown").innerHTML = "Time remaining: " + seconds + " seconds";
                }
            }, 1000); // Start new countdown
        }

        // Add event listener to resend button to restart timer
        document.addEventListener("DOMContentLoaded", () => {
            const resendBtn = document.getElementById("resendBtn");
            resendBtn.addEventListener("click", function () {
                restartTimer(120); // 2 minutes (120 seconds)
            });
        });
    </script>
</head>
<body>
    <div class="form-container">
        <h1>Verify code</h1>
        <small class="mt-3 mb-3">An authentication code has been sent to your email.</small>
        <?php if ($verificationSuccess): ?>

            <div class='alert alert-success'>Email verified successfully!</div>
            <div class="form-group">
                    <label for="verification_code" class="mt-3">Verification Code</label>
                    <input type="text" class="form-control" name="verification_code" id="verification_code">
                    <?php if ($verificationError): ?>
                        <p class="text-danger"><?php echo $verificationError; ?></p>
                    <?php endif; ?>
                    <p id="countdown" class="text-warning">Loading timer...</p> <!-- Countdown display -->
                </div>
         
                <div class="container">
    <div class="row">
        <div class="col-12 col-md-12  verify-resend">
            <p>Didn’t receive a code? 
                <button type="submit" name="resend" id="resendBtn" class="btn btn-resend">Resend</button>
            </p>
            <button type="submit" name="verify" class="btn btn-verify container-fluid">Verify</button>
        </div>
    </div>
</div>

            <script>
                setTimeout(function() {
                    window.location.href = 'login.php'; // Redirect to your login page
                }, 5000); // Redirect after 5 seconds
            </script>
        <?php else: ?>
            <form action="" method="POST">
                <div class="form-group">
                    <label for="verification_code">Verification Code</label>
                    <input type="text" class="form-control" name="verification_code" id="verification_code">
                    <?php if ($verificationError): ?>
                        <p class="text-danger"><?php echo $verificationError; ?></p>
                    <?php endif; ?>
                    <p id="countdown" class="text-warning">Loading timer...</p> <!-- Countdown display -->
                </div>
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email']); ?>">
                <div class="container">
    <div class="row ">
    <div class="col-12 col-md-12  verify-resend">
            <p>Didn’t receive a code? 
                <button type="submit" name="resend" id="resendBtn" class="btn btn-resend">Resend</button>
            </p>
            <button type="submit" name="verify" class="btn btn-verify container-fluid">Verify</button>
                    </div>
    </div>
</div>

                <?php if ($resendSuccess): ?>
                    <div class="text-center">
                    <p class="text-success">New verification code sent successfully!</p>
                    </div>
                    
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
