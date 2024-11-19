<?php 
include './../../connection/connection.php';

$firstName = $lastName = $email = $contactNumber = null;
$errors = [];
$successMessage = "";

// Fetch user details if session exists
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

// Handle form submission
if (isset($_POST['updateProfile'])) {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $changePassword = isset($_POST['changePassword']);
    $oldPassword = $_POST['oldPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';

    // Validate inputs
    if (!preg_match('/^[a-zA-Z]+$/', $firstName)) {
        $errors[] = "First name should only contain alphabetic characters.";
    }

    if (!preg_match('/^[a-zA-Z]+$/', $lastName)) {
        $errors[] = "Last name should only contain alphabetic characters.";
    }

    if (!preg_match('/^\d+$/', $contactNumber)) {
        $errors[] = "Contact number should contain only digits.";
    }

    // Password validation
    if ($changePassword) {
        $sql = "SELECT password FROM client WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($storedPassword);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($oldPassword, $storedPassword)) {
            $errors[] = "Old password is incorrect.";
        } elseif (strlen($newPassword) < 8) {
            $errors[] = "New password must be at least 8 characters long.";
        }
    }

    // If no errors, update the database
    if (empty($errors)) {
        if ($changePassword && !empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE client SET password = ?, firstname = ?, lastname = ?, email = ?, contact_number = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $hashedPassword, $firstName, $lastName, $email, $contactNumber, $userId);
        } else {
            $sql = "UPDATE client SET firstname = ?, lastname = ?, email = ?, contact_number = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $firstName, $lastName, $email, $contactNumber, $userId);
        }

        if ($stmt->execute()) {
            $successMessage = "Profile updated successfully!";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!-- Form HTML -->
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
                        <label class="form-check-label" for="changePassword">Change Password</label>
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

<script>
    document.getElementById("changePassword").addEventListener("change", function() {
        document.querySelector(".password-fields").style.display = this.checked ? "block" : "none";
    });
</script>
