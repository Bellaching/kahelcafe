<?php
$successMessage = ''; // Initialize the variable to avoid "undefined variable" notice
$errorMessages = []; // Initialize error messages array
$contact_number = ''; // Initialize $contact_number to avoid undefined variable notice
$newPassword = ''; // Initialize new password
$confirmPassword = ''; // Initialize confirm password

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updateProfile') {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number']; // Capture contact number from the form
    $newPassword = $_POST['newPassword']; // Capture new password from the form
    $confirmPassword = $_POST['confirmPassword']; // Capture confirm password from the form

    // Validate contact number (must start with '09' and have exactly 11 digits)
    if (empty($contact_number) || !preg_match('/^09\d{9}$/', $contact_number)) {
        $errorMessages[] = "A valid contact number is required (must start with 09 and have exactly 11 digits).";
    }

    // Validate passwords
    if (!empty($newPassword)) {
        // Password length validation (minimum 8 characters)
        if (strlen($newPassword) < 8) {
            $errorMessages[] = "The new password must be at least 8 characters long.";
        }

        // Password confirmation validation
        if ($newPassword !== $confirmPassword) {
            $errorMessages[] = "The passwords do not match.";
        }

        // Password strength validation (Weak, Medium, Strong)
        if (strlen($newPassword) >= 8) {
            if (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/\d/', $newPassword)) {
                $passwordStrength = 'Weak';
            } elseif (preg_match('/[A-Za-z]/', $newPassword) && preg_match('/\d/', $newPassword)) {
                $passwordStrength = 'Medium';
            } else {
                $passwordStrength = 'Strong';
            }
        }
    }

    if (empty($errorMessages)) {
        // Proceed with the update query
        $sql = "UPDATE client SET firstname = ?, lastname = ?, email = ?, contact_number = ?, password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT); // Hash the new password
        $stmt->bind_param("sssssi", $firstName, $lastName, $email, $contact_number, $hashedPassword, $userId);
        if ($stmt->execute()) {
            $successMessage = "Profile updated successfully!";
        } else {
            $errorMessages[] = "Update failed: " . $stmt->error;
        }
        $stmt->close();
    }
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
.alert {
    margin-top: 10px;
}
.password-strength {
    font-size: 14px;
}
.password-strength.weak {
    color: red;
}
.password-strength.medium {
    color: orange;
}
.password-strength.strong {
    color: green;
}
.password-fields {
    display: none; /* Initially hide password fields */
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
                <!-- Success Message -->
                <?php if ($successMessage): ?>
                    <div class="alert alert-success" role="alert" id="successMessage">
                        <?= htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <!-- Error Messages -->
                <?php if (!empty($errorMessages)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php foreach ($errorMessages as $error): ?>
                            <div><?= htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="profileForm">
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
                    <div class="mb-3">
                        <label for="contact_number" class="form-label">Contact Number</label>
                        <input type="text" class="form-control" name="contact_number" id="contact_number" value="<?= htmlspecialchars($contact_number); ?>" required>
                    </div>

                    <!-- Show Password Fields if checkbox is checked -->
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="showPasswordCheckbox">
                        <label class="form-check-label" for="showPasswordCheckbox">Show Password</label>
                    </div>

                    <!-- Password Fields (Initially hidden) -->
                    <div class="password-fields">
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" name="newPassword" id="newPassword" value="<?= htmlspecialchars($newPassword); ?>">
                            <div id="password-strength" class="password-strength"></div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" name="confirmPassword" id="confirmPassword" value="<?= htmlspecialchars($confirmPassword); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="submit" name="action" value="updateProfile" 
                            class="btn w-100 text-white rounded-pill" 
                            style="background-color: #FF902B;">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Automatically hide the success message after 5 seconds
    setTimeout(function() {
        var successMessage = document.getElementById('successMessage');
        if (successMessage) {
            successMessage.style.display = 'none';
        }
    }, 5000);

    // Show the modal only if a success message is set
    <?php if ($successMessage): ?>
        const modalElement = document.getElementById('changeProfileModal');
        const modal = new bootstrap.Modal(modalElement);
        modal.show(); // Show modal only if there's a success message
    <?php endif; ?>

    // Toggle visibility of password fields based on checkbox
    document.getElementById('showPasswordCheckbox').addEventListener('change', function () {
        const passwordFields = document.querySelector('.password-fields');
        if (this.checked) {
            passwordFields.style.display = 'block'; // Show password fields
        } else {
            passwordFields.style.display = 'none'; // Hide password fields
        }
    });

    // Password strength detection
    document.getElementById('newPassword').addEventListener('input', function () {
        const password = this.value;
        const strengthElement = document.getElementById('password-strength');
        let strength = '';
        if (password.length < 8) {
            strength = 'Weak';
            strengthElement.className = 'password-strength weak';
        } else if (/[A-Za-z]/.test(password) && /\d/.test(password)) {
            strength = 'Medium';
            strengthElement.className = 'password-strength medium';
        } else {
            strength = 'Strong';
            strengthElement.className = 'password-strength strong';
        }
        strengthElement.textContent = `Strength: ${strength}`;
    });
</script>

<!-- Include Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
