<?php
include './../../connection/connection.php';
// Initialize all variables
$successMessage = '';
$errorMessages = [];
$username = '';
$email = '';
$role = '';
$date_created = '';
$profile_picture = '';
$newPassword = '';
$confirmPassword = '';

// Assuming $userId is set somewhere in your code (from session or elsewhere)
// For testing purposes, I'll set it to 1 if not already set
if (!isset($userId)) {
    $userId = $_SESSION['user_id'] ?? 1; // Replace with your actual session variable
}

// Get current admin data from database including profile picture
$sql = "SELECT id, username, email, role, date_created, profile_picture FROM admin_list WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $adminData = $result->fetch_assoc();
    $username = $adminData['username'] ?? '';
    $email = $adminData['email'] ?? '';
    $role = $adminData['role'] ?? '';
    $date_created = $adminData['date_created'] ?? '';
    $profile_picture = $adminData['profile_picture'] ?? '';
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errorMessages = [];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'updateProfile':
                $username = $_POST['username'] ?? '';
                $email = $_POST['email'] ?? '';
                $newPassword = $_POST['newPassword'] ?? '';
                $confirmPassword = $_POST['confirmPassword'] ?? '';

                if (!empty($newPassword)) {
                    if (strlen($newPassword) < 8) {
                        $errorMessages[] = "Password must be at least 8 characters long";
                    } elseif ($newPassword !== $confirmPassword) {
                        $errorMessages[] = "Passwords do not match";
                    }
                }

                if (empty($errorMessages)) {
                    if (empty($newPassword)) {
                        $sql = "UPDATE admin_list SET username = ?, email = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        if ($stmt === false) {
                            $errorMessages[] = "Error preparing statement: " . $conn->error;
                        } else {
                            $stmt->bind_param("ssi", $username, $email, $userId);
                        }
                    } else {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $sql = "UPDATE admin_list SET username = ?, email = ?, password = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        if ($stmt === false) {
                            $errorMessages[] = "Error preparing statement: " . $conn->error;
                        } else {
                            $stmt->bind_param("sssi", $username, $email, $hashedPassword, $userId);
                        }
                    }
                    
                    if (empty($errorMessages)) {
                        if ($stmt->execute()) {
                            $successMessage = "Profile updated successfully!";
                        } else {
                            $errorMessages[] = "Update failed: " . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
                break;
                
            case 'uploadProfilePicture':
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $fileType = $_FILES['profile_picture']['type'];
                    
                    if (in_array($fileType, $allowedTypes)) {
                        $uploadDir = './../../uploads/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileName = uniqid('profile_') . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                        $uploadPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                            $sql = "UPDATE admin_list SET profile_picture = ? WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            if ($stmt === false) {
                                $errorMessages[] = "Error preparing statement: " . $conn->error;
                            } else {
                                $relativePath = 'uploads/' . $fileName;
                                $stmt->bind_param("si", $relativePath, $userId);
                                
                                if ($stmt->execute()) {
                                    $profile_picture = $relativePath;
                                    $successMessage = "Profile picture updated successfully!";
                                    // Refresh the page to show the new image
                                    echo "<script>window.location.href = window.location.href;</script>";
                                    exit();
                                } else {
                                    $errorMessages[] = "Failed to update profile picture";
                                }
                                $stmt->close();
                            }
                        } else {
                            $errorMessages[] = "Failed to upload file";
                        }
                    } else {
                        $errorMessages[] = "Only JPG, PNG, and GIF files are allowed";
                    }
                } else {
                    $errorMessages[] = "No file selected or upload error";
                }
                break;
                
            case 'deleteProfilePicture':
                if (!empty($profile_picture)) {
                    $fullPath = './../../' . $profile_picture;
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
                
                $sql = "UPDATE admin_list SET profile_picture = NULL WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $errorMessages[] = "Error preparing statement: " . $conn->error;
                } else {
                    $stmt->bind_param("i", $userId);
                    
                    if ($stmt->execute()) {
                        $profile_picture = null;
                        $successMessage = "Profile picture removed successfully!";
                        // Refresh the page
                        echo "<script>window.location.href = window.location.href;</script>";
                        exit();
                    } else {
                        $errorMessages[] = "Failed to remove profile picture";
                    }
                    $stmt->close();
                }
                break;
        }
    }
}
?>

<style>
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
</style>

<div class="modal-body">
    <?php if ($successMessage): ?>
        <div class="alert alert-success" role="alert" id="successMessage">
            <?= htmlspecialchars($successMessage); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMessages)): ?>
        <div class="alert alert-danger" role="alert">
            <?php foreach ($errorMessages as $error): ?>
                <div><?= htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="profileForm">
        <input type="hidden" name="action" value="updateProfile">
        
        <div class="text-center mb-4">
            <div class="profile-picture-container" onclick="openProfilePictureModal()">
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
        <div class="mb-3">
            <label class="form-label">Date Created</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($date_created); ?>" readonly>
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

<!-- Profile Picture Modal -->
<div class="modal fade" id="profilePictureModal" tabindex="-1" aria-labelledby="profilePictureModalLabel" aria-hidden="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="profilePictureModalLabel">Change Profile Picture</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="profilePictureForm">
                    <input type="hidden" name="action" value="uploadProfilePicture">
                    <div class="mb-3 text-center">
                        <?php if (!empty($profile_picture)): ?>
                            <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Current Profile Picture" class="profile-picture mb-3" style="width: 200px; height: 200px;" id="profilePicturePreview">
                        <?php else: ?>
                            <div class="profile-picture-placeholder mb-3 mx-auto" style="width: 200px; height: 200px;">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        
                        <input type="file" class="form-control" name="profile_picture" id="profilePictureInput" accept="image/jpeg,image/png,image/gif">
                        <small class="text-muted">Max size: 2MB. Formats: JPG, PNG, GIF</small>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <?php if (!empty($profile_picture)): ?>
                            <button type="submit" name="action" value="deleteProfilePicture" class="btn btn-danger rounded-pill">
                                <i class="fas fa-trash-alt me-1"></i> Delete Picture
                            </button>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary rounded-pill ms-auto">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openProfilePictureModal() {
    var modal = new bootstrap.Modal(document.getElementById('profilePictureModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    // Toggle password fields
    const passwordCheckbox = document.getElementById('showPasswordCheckbox');
    if (passwordCheckbox) {
        passwordCheckbox.addEventListener('change', function() {
            const passwordFields = document.querySelector('.password-fields');
            if (passwordFields) {
                passwordFields.style.display = this.checked ? 'block' : 'none';
            }
        });
    }

    // Password strength indicator
    const newPasswordInput = document.getElementById('newPassword');
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', function() {
            const strengthElement = document.getElementById('password-strength');
            if (!strengthElement) return;
            
            const password = this.value;
            
            if (password.length === 0) {
                strengthElement.textContent = '';
                return;
            }
            
            if (password.length < 8) {
                strengthElement.textContent = 'Weak';
                strengthElement.className = 'password-strength weak';
            } else if (/[A-Z]/.test(password) && /[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) {
                strengthElement.textContent = 'Strong';
                strengthElement.className = 'password-strength strong';
            } else if (/[A-Z]/.test(password) || /[0-9]/.test(password)) {
                strengthElement.textContent = 'Medium';
                strengthElement.className = 'password-strength medium';
            } else {
                strengthElement.textContent = 'Weak';
                strengthElement.className = 'password-strength weak';
            }
        });
    }

    // Preview image before upload in the modal
    const profilePictureInput = document.getElementById('profilePictureInput');
    if (profilePictureInput) {
        profilePictureInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    let preview = document.getElementById('profilePicturePreview');
                    if (!preview) {
                        const placeholder = document.querySelector('#profilePictureModal .profile-picture-placeholder');
                        if (placeholder) {
                            placeholder.innerHTML = '';
                            placeholder.className = '';
                            placeholder.style = '';
                            placeholder.classList.add('profile-picture');
                            preview = placeholder;
                        }
                    }
                    if (preview) {
                        preview.src = event.target.result;
                        if (preview.tagName.toLowerCase() === 'div') {
                            const img = document.createElement('img');
                            img.src = event.target.result;
                            img.className = 'profile-picture mb-3';
                            img.style = 'width: 200px; height: 200px;';
                            img.id = 'profilePicturePreview';
                            preview.parentNode.replaceChild(img, preview);
                            preview = img;
                        }
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Auto-hide success message after 5 seconds
    const successMessage = document.getElementById('successMessage');
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.display = 'none';
        }, 5000);
    }
});
</script>