<?php
include './../../connection/connection.php';
// Initialize all variables at the top
$successMessage = '';
$errorMessages = [];
$username = '';
$email = '';
$profile_picture = null;
$role = '';
$newPassword = '';
$confirmPassword = '';

// Get current user data from database including profile picture
$sql = "SELECT username, email, profile_picture, role FROM admin_list WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $userData = $result->fetch_assoc();
    $username = $userData['username'];
    $email = $userData['email'];
    $profile_picture = $userData['profile_picture'];
    $role = $userData['role'];
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Reset error messages for each request
    $errorMessages = [];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'updateProfile':
                // Only validate when updating profile
                $username = $_POST['username'];
                $email = $_POST['email'];
                $newPassword = $_POST['newPassword'] ?? '';
                $confirmPassword = $_POST['confirmPassword'] ?? '';

                // Validate username
                if (empty($username)) {
                    $errorMessages[] = "Username is required.";
                }

                // Validate passwords only if they're provided
                if (!empty($newPassword)) {
                    if (strlen($newPassword) < 8) {
                        $errorMessages[] = "The new password must be at least 8 characters long.";
                    } elseif ($newPassword !== $confirmPassword) {
                        $errorMessages[] = "The passwords do not match.";
                    }
                }

                if (empty($errorMessages)) {
                    if (empty($newPassword)) {
                        $sql = "UPDATE admin_list SET username = ?, email = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssi", $username, $email, $userId);
                    } else {
                        $sql = "UPDATE admin_list SET username = ?, email = ?, password = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt->bind_param("sssi", $username, $email, $hashedPassword, $userId);
                    }
                    
                    if ($stmt->execute()) {
                        $successMessage = "Profile updated successfully!";
                    } else {
                        $errorMessages[] = "Update failed: " . $stmt->error;
                    }
                    $stmt->close();
                }
                break;
                
            case 'uploadProfilePicture':
                // Only validate file upload
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $fileType = $_FILES['profile_picture']['type'];
                    
                    if (in_array($fileType, $allowedTypes)) {
                        $uploadDir = 'uploads/profile_pictures/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileName = uniqid('profile_') . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                        $uploadPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                            $sql = "UPDATE admin_list SET profile_picture = ? WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("si", $uploadPath, $userId);
                            
                            if ($stmt->execute()) {
                                $profile_picture = $uploadPath;
                                $successMessage = "Profile picture updated successfully!";
                            } else {
                                $errorMessages[] = "Failed to update profile picture in database.";
                            }
                            $stmt->close();
                        } else {
                            $errorMessages[] = "Failed to upload profile picture.";
                        }
                    } else {
                        $errorMessages[] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
                    }
                } else {
                    $errorMessages[] = "Please select a valid image file.";
                }
                break;
                
            case 'deleteProfilePicture':
                if (!empty($profile_picture) && file_exists($profile_picture)) {
                    unlink($profile_picture);
                }
                
                $sql = "UPDATE admin_list SET profile_picture = NULL WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $userId);
                
                if ($stmt->execute()) {
                    $profile_picture = null;
                    $successMessage = "Profile picture removed successfully!";
                } else {
                    $errorMessages[] = "Failed to remove profile picture from database.";
                }
                $stmt->close();
                break;
        }
    }
}
?>

<style>
body {
    overflow-x: hidden;
}
.profile-picture-container {
    position: relative;
    width: 150px;
    height: 150px;
    margin: 0 auto 20px;
    cursor: pointer;
}
.profile-picture {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #FF902B;
}
.profile-picture-placeholder {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background-color: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid #FF902B;
    color: #777;
    font-size: 50px;
}
.edit-profile-picture {
    position: absolute;
    bottom: 0;
    right: 0;
    background-color: #FF902B;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 1; /* Changed from 0 to 1 to always show the button */
    transition: opacity 0.3s;
}
/* Remove the hover effect since we always show the button now */
/* .profile-picture-container:hover .edit-profile-picture {
    opacity: 1;
} */
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
    display: none;
}
@media (max-width: 576px) {
    .profile-picture-container {
        width: 120px;
        height: 120px;
    }
    .edit-profile-picture {
        width: 35px;
        height: 35px;
        opacity: 1; /* Ensure it's always visible on mobile */
    }
}
</style>

<!-- Profile Picture Modal -->
<div class="modal fade" id="profilePictureModal" tabindex="-1" aria-labelledby="profilePictureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profilePictureModalLabel">Change Profile Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($errorMessages) && isset($_POST['action']) && $_POST['action'] === 'uploadProfilePicture'): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php foreach ($errorMessages as $error): ?>
                            <div><?= htmlspecialchars($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="profilePictureForm">
                    <input type="hidden" name="action" value="uploadProfilePicture">
                    <div class="mb-3 text-center">
                        <?php if (!empty($profile_picture)): ?>
                            <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Current Profile Picture" class="profile-picture mb-3" style="width: 200px; height: 200px;">
                        <?php else: ?>
                            <div class="profile-picture-placeholder mb-3 mx-auto" style="width: 200px; height: 200px;">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        
                        <input type="file" class="form-control" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/gif">
                        <small class="text-muted">Max size: 2MB. Formats: JPG, PNG, GIF</small>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <?php if (!empty($profile_picture)): ?>
                            <button type="submit" name="action" value="deleteProfilePicture" class="btn btn-danger rounded-pill">
                                <i class="fas fa-trash-alt"></i> Delete Picture
                            </button>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn text-white rounded-pill ms-auto" style="background-color: #FF902B;">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal for changing profile -->
<div class="modal fade" id="changeProfileModal" tabindex="-1" aria-labelledby="changeProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeProfileModalLabel">Change Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if ($successMessage && (!isset($_POST['action']) || $_POST['action'] !== 'uploadProfilePicture')): ?>
                    <div class="alert alert-success" role="alert" id="successMessage">
                        <?= htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMessages) && isset($_POST['action']) && $_POST['action'] === 'updateProfile'): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php foreach ($errorMessages as $error): ?>
                            <div><?= htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="profileForm">
                    <input type="hidden" name="action" value="updateProfile">
                    
                    <div class="text-center mb-4">
                        <div class="profile-picture-container" data-bs-toggle="modal" data-bs-target="#profilePictureModal">
                            <?php if (!empty($profile_picture)): ?>
                                <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Profile Picture" class="profile-picture">
                            <?php else: ?>
                                <div class="profile-picture-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <div class="edit-profile-picture">
                                <i class="fas fa-pencil-alt"></i>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" id="username" value="<?= htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($role); ?>" readonly>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="showPasswordCheckbox">
                        <label class="form-check-label" for="showPasswordCheckbox">Change Password</label>
                    </div>

                    <div class="password-fields">
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" name="newPassword" id="newPassword">
                            <div id="password-strength" class="password-strength"></div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" name="confirmPassword" id="confirmPassword">
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn w-100 text-white rounded-pill" style="background-color: #FF902B;">Update Profile</button>
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
        modal.show();
    <?php endif; ?>

    // Toggle visibility of password fields based on checkbox
    document.getElementById('showPasswordCheckbox').addEventListener('change', function () {
        const passwordFields = document.querySelector('.password-fields');
        if (this.checked) {
            passwordFields.style.display = 'block';
        } else {
            passwordFields.style.display = 'none';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            document.getElementById('password-strength').textContent = '';
        }
    });

    // Password strength detection
    document.getElementById('newPassword').addEventListener('input', function () {
        const password = this.value;
        const strengthElement = document.getElementById('password-strength');
        let strength = '';
        
        if (password.length === 0) {
            strengthElement.textContent = '';
            return;
        }
        
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
    
    // Preview profile picture before upload
    document.getElementById('profile_picture').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const preview = document.querySelector('#profilePictureModal .profile-picture');
                if (preview) {
                    preview.src = event.target.result;
                } else {
                    const placeholder = document.querySelector('#profilePictureModal .profile-picture-placeholder');
                    if (placeholder) {
                        placeholder.innerHTML = `<img src="${event.target.result}" alt="Preview" class="profile-picture" style="width: 100%; height: 100%;">`;
                    }
                }
            };
            reader.readAsDataURL(file);
        }
    });
</script>

<!-- Include Bootstrap JS and Font Awesome -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">