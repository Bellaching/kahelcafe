<?php 
include './../../connection/connection.php';

$firstName = null;
$lastName = null;
$email = null;
$contactNumber = null;

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $sql = "SELECT firstname, lastname, email, contact_number FROM client WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($firstName, $lastName, $email, $contactNumber);
    $stmt->fetch();
    $stmt->close();
}

$errors = [];
$successMessage = "";
if (isset($_POST['updateProfile'])) {
    // Get user input
    $changePassword = isset($_POST['changePassword']) ? true : false;
    $oldPassword = $_POST['oldPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
   
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
  

    // Validate first name and last name (only alphabetic characters)
    if (!preg_match('/^[a-zA-Z]+$/', $firstName)) {
        $errors[] = "First name should only contain alphabetic characters.";
    }

    if (!preg_match('/^[a-zA-Z]+$/', $lastName)) {
        $errors[] = "Last name should only contain alphabetic characters.";
    }

    // Validate contact number (digits only)
    if (!preg_match('/^\d+$/', $contactNumber)) {
        $errors[] = "Contact number should contain only digits.";
    }

    // If password change is requested, validate the old password and new password
    if ($changePassword && !empty($oldPassword)) {
        $sql = "SELECT password FROM client WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($storedPassword);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($oldPassword, $storedPassword)) {
            $errors[] = "Old password is incorrect.";
        } else {
            // Validate new password length (at least 8 characters)
            if (strlen($newPassword) < 8) {
                $errors[] = "New password must be at least 8 characters long.";
            }

            // Check password strength
            $passwordStrength = checkPasswordStrength($newPassword);
            if ($passwordStrength === "weak") {
                $errors[] = "New password is too weak.";
            } elseif ($passwordStrength === "medium") {
                $errors[] = "New password is medium strength.";
            }

            // Check if new password and confirm password match
         
        }
    }

    // If no errors, update the profile
    if (empty($errors)) {
        if ($changePassword && !empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE client SET password = ?, firstname = ?, lastname = ?, email = ?, contact_number = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $hashedPassword, $firstName, $lastName, $email, $contactNumber, $userId);
            $stmt->execute();
            $stmt->close();
        } else {
            $sql = "UPDATE client SET firstname = ?, lastname = ?, email = ?, contact_number = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $firstName, $lastName, $email, $contactNumber, $userId);
            $stmt->execute();
            $stmt->close();
        }

        // Refetch updated data from the database
        $sql = "SELECT firstname, lastname, email, contact_number FROM client WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($firstName, $lastName, $email, $contactNumber);
        $stmt->fetch();
        $stmt->close();

        $successMessage = "Profile updated successfully!";
    }
}

$conn->close();

// Function to check password strength
function checkPasswordStrength($password) {
    if (strlen($password) < 8) {
        return "weak";
    }

    if (preg_match("/[A-Z]/", $password) && preg_match("/[a-z]/", $password) && preg_match("/\d/", $password)) {
        return "strong";
    }

    return "medium";
}
?>

<style>
body {
    overflow-x: hidden;
}
.modal-header {
    background-color: #FF902B;
    color: white;
}
.modal-content {
    border-radius: 10px;
}
.form-check-input {
    margin-top: 7px;
}
.error-message {
    color: red;
    font-size: 0.875em;
}
</style>

<!-- Modal for changing profile -->
<div class="modal fade" id="changeProfileModal" tabindex="-1" aria-labelledby="changeProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeProfileModalLabel">Change Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($successMessage); ?></div>
                    <?php endif; ?>

                    <?php foreach ($errors as $error): ?>
                        <div class="error-message"><?= htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>

                    <div class="mb-3">
                        <label for="firstName" class="form-label">First Name</label>
                        <input type="text" class="form-control" name="firstName" id="firstName" value="<?= htmlspecialchars($firstName); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="lastName" class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="lastName" id="lastName" value="<?= htmlspecialchars($lastName); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($email); ?>" required>
                    </div>
                 

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="changePassword" id="changePassword">
                        <label class="form-check-label" for="changePassword">
                            Change Password
                        </label>
                    </div>

                    <div class="password-fields" style="display:none;">
                        <div class="mb-3">
                            <label for="oldPassword" class="form-label">Old Password</label>
                            <input type="password" class="form-control" name="oldPassword" id="oldPassword">
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" name="newPassword" id="newPassword">
                        </div>
                      
                    </div>

                    <button type="submit" name="updateProfile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Optional: Add script to toggle password fields visibility -->
<script>
    document.getElementById("changePassword").addEventListener("change", function() {
        var passwordFields = document.querySelector(".password-fields");
        passwordFields.style.display = this.checked ? "block" : "none";
    });
</script>
