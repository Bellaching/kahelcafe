<?php
// Start session and include files
session_start();
include './../../connection/connection.php';

// Initialize variables
$successMessage = '';
$errorMessages = [];
$userData = [
    'username' => '',
    'email' => '',
    'role' => '',
    'date_created' => '',
    'profile_picture' => ''
];

// Get user ID from session
$userId = $_SESSION['user_id'] ?? 0;

// Debugging: Verify session and connection
error_log("Session User ID: " . $userId);
error_log("Database Connection: " . ($conn ? "Connected" : "Not connected"));

// Only proceed if we have a valid user ID
if ($userId > 0) {
    // Get user data from database
    $sql = "SELECT username, email, role, date_created, profile_picture 
            FROM admin_list 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $userData = $result->fetch_assoc();
                error_log("Retrieved User Data: " . print_r($userData, true));
                
                // Ensure session username matches database
                if (empty($_SESSION['username']) || $_SESSION['username'] !== $userData['username']) {
                    $_SESSION['username'] = $userData['username'];
                }
            } else {
                error_log("No user found with ID: " . $userId);
                $errorMessages[] = "User account not found";
            }
        } else {
            error_log("Query execution failed: " . $stmt->error);
            $errorMessages[] = "Database error";
        }
        $stmt->close();
    } else {
        error_log("Prepare statement failed: " . $conn->error);
        $errorMessages[] = "Database error";
    }
} else {
    error_log("Invalid user ID in session");
    $errorMessages[] = "Not logged in";
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $errorMessages = []; // Reset errors for new request
    
    switch ($_POST['action']) {
        case 'updateProfile':
            $newUsername = trim($_POST['username'] ?? '');
            $newEmail = trim($_POST['email'] ?? '');
            $newPassword = $_POST['newPassword'] ?? '';
            $confirmPassword = $_POST['confirmPassword'] ?? '';
            
            // Validation
            if (empty($newUsername)) {
                $errorMessages[] = "Username is required";
            }
            
            if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $errorMessages[] = "Valid email is required";
            }

            if (!empty($newPassword)) {
                if (strlen($newPassword) < 8) {
                    $errorMessages[] = "Password must be at least 8 characters";
                } elseif ($newPassword !== $confirmPassword) {
                    $errorMessages[] = "Passwords don't match";
                }
            }

            // Update if validation passes
            if (empty($errorMessages)) {
                try {
                    if (!empty($newPassword)) {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $sql = "UPDATE admin_list 
                                SET username = ?, email = ?, password = ? 
                                WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sssi", $newUsername, $newEmail, $hashedPassword, $userId);
                    } else {
                        $sql = "UPDATE admin_list 
                                SET username = ?, email = ? 
                                WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssi", $newUsername, $newEmail, $userId);
                    }

                    if ($stmt->execute()) {
                        // Update session and local data
                        $_SESSION['username'] = $newUsername;
                        $userData['username'] = $newUsername;
                        $userData['email'] = $newEmail;
                        
                        $_SESSION['success'] = "Profile updated successfully!";
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit();
                    } else {
                        throw new Exception("Update failed: " . $stmt->error);
                    }
                } catch (Exception $e) {
                    $errorMessages[] = "Database error: " . $e->getMessage();
                } finally {
                    if (isset($stmt)) $stmt->close();
                }
            }
            break;

        case 'uploadProfilePicture':
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $fileType = $_FILES['profile_picture']['type'];
                
                if (in_array($fileType, $allowedTypes)) {
                    $uploadDir = './../../uploads/profile_pictures/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileName = 'user_' . $userId . '_' . time() . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                    $uploadPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                        // Delete old picture if exists
                        if (!empty($userData['profile_picture']) && file_exists('./../../' . $userData['profile_picture'])) {
                            unlink('./../../' . $userData['profile_picture']);
                        }
                        
                        $relativePath = 'uploads/profile_pictures/' . $fileName;
                        $sql = "UPDATE admin_list SET profile_picture = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $relativePath, $userId);
                        
                        if ($stmt->execute()) {
                            $userData['profile_picture'] = $relativePath;
                            $_SESSION['success'] = "Profile picture updated!";
                            header("Location: " . $_SERVER['PHP_SELF']);
                            exit();
                        } else {
                            $errorMessages[] = "Failed to update database";
                        }
                        $stmt->close();
                    } else {
                        $errorMessages[] = "Failed to save file";
                    }
                } else {
                    $errorMessages[] = "Only JPG, PNG, and GIF files are allowed";
                }
            } else {
                $errorMessages[] = "Please select a valid image file";
            }
            break;

        case 'deleteProfilePicture':
            if (!empty($userData['profile_picture']) && file_exists('./../../' . $userData['profile_picture'])) {
                unlink('./../../' . $userData['profile_picture']);
            }
            
            $sql = "UPDATE admin_list SET profile_picture = NULL WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            
            if ($stmt->execute()) {
                $userData['profile_picture'] = '';
                $_SESSION['success'] = "Profile picture removed!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $errorMessages[] = "Failed to update database";
            }
            $stmt->close();
            break;
    }
}

// Check for success messages
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .profile-picture-container {
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            position: relative;
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
            background: #f0f0f0;
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
            background: #FF902B;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .password-fields {
            display: none;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">My Profile</h4>
                </div>
                
                <div class="card-body">
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errorMessages)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errorMessages as $error): ?>
                                <div><?= htmlspecialchars($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="updateProfile">
                        
                        <div class="text-center mb-4">
                            <div class="profile-picture-container" onclick="document.getElementById('profilePictureInput').click()">
                                <?php if (!empty($userData['profile_picture'])): ?>
                                    <img src="<?= htmlspecialchars($userData['profile_picture']) ?>" 
                                         alt="Profile Picture" 
                                         class="profile-picture">
                                <?php else: ?>
                                    <div class="profile-picture-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="edit-profile-picture">
                                    <i class="fas fa-pencil-alt"></i>
                                </div>
                            </div>
                            <input type="file" id="profilePictureInput" name="profile_picture" 
                                   accept="image/jpeg,image/png,image/gif" 
                                   style="display: none;"
                                   onchange="document.getElementById('uploadForm').submit()">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" 
                                   value="<?= htmlspecialchars($userData['username']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?= htmlspecialchars($userData['email']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($userData['role']) ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Member Since</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($userData['date_created']) ?>" readonly>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="changePassword">
                            <label class="form-check-label" for="changePassword">Change Password</label>
                        </div>
                        
                        <div class="password-fields" id="passwordFields">
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="newPassword">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" name="confirmPassword">
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                    
                    <form id="uploadForm" method="POST" enctype="multipart/form-data" style="display: none;">
                        <input type="hidden" name="action" value="uploadProfilePicture">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Toggle password fields
    $('#changePassword').change(function() {
        $('#passwordFields').toggle(this.checked);
    });
    
    // Auto-submit profile picture when selected
    $('#profilePictureInput').change(function() {
        if (this.files.length > 0) {
            $('#uploadForm').submit();
        }
    });
    
    // Hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
});
</script>
</body>
</html>