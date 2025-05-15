<?php
include './../user/authenticate.php';
include __DIR__ . '/../../connection/connection.php';

$userId = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? '';

// Initialize all variables for profile change
$successMessage = '';
$errorMessages = [];
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) ){
    // Reset error messages for each request
    $errorMessages = [];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'updateProfile':
                $username = $_POST['username'];
                $email = $_POST['email'];
                $newPassword = $_POST['newPassword'] ?? '';
                $confirmPassword = $_POST['confirmPassword'] ?? '';

                if (empty($username)) {
                    $errorMessages[] = "Username is required.";
                }

                if (!empty($newPassword)) {
                    if (strlen($newPassword) < 8) {
                        $errorMessages[] = "The new password must be at least 8 characters long.";
                    } elseif ($newPassword !== $confirmPassword) {
                        $errorMessages[] = "The passwords do not match.";
                    }
                }

                if (empty($errorMessages)) {
                    try {
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
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
                            exit;
                        } else {
                            throw new Exception("Update failed: " . $stmt->error);
                        }
                    } catch (Exception $e) {
                        header('Content-Type: application/json');
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                        exit;
                    }
                } else {
                    header('Content-Type: application/json');
                    http_response_code(400);
                    echo json_encode(['success' => false, 'errors' => $errorMessages]);
                    exit;
                }
                break;
                
            case 'uploadProfilePicture':
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        body {
            overflow-x: hidden;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
            background-color: white;
        }
        
        .sidebar {
            width: 60px;
            min-height: 100vh;
            background-color: #ffffff;
            transition: all 0.3s;
            position: fixed;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar:hover {
            width: 250px;
        }
        
        .sidebar:hover .nav-link-text {
            display: inline;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-item {
            position: relative;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #FF902B;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-link:hover {
            color: #FF902B;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-icon {
            font-size: 1.2rem;
            margin-right: 15px;
            width: 20px;
            text-align: center;
            color: #FF902B;
        }
        
        .nav-link-text {
            display: none;
            white-space: nowrap;
        }
        
        .main-content {
            flex: 1;
            margin-left: 60px;
            transition: margin-left 0.3s;
        }
        
        .sidebar:hover ~ .main-content {
            margin-left: 250px;
        }
        
        .navbar-default {
            background-color: white;
            width: 100%;
            margin: 0;
            padding: 0;
            border: none !important;
        }
        
        .navbar-container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0 15px;
        }
        
        .navbar-nav > li > a {
            color: black;
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            white-space: nowrap;
            border: none !important;
        }
        
        .navbar-nav > li > a:hover,
        .dropdown-menu > li > a:hover {
            background-color: #f8f9fa;
            color: black;
        }
        
        .shadow-bottom {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            position: absolute;
        }
        
        .dropdown-item {
            padding: 8px 16px;
            color: #333;
            border: none !important;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .nav-link,
        .dropdown-toggle,
        .navbar-toggler {
            border: none !important;
            outline: none !important;
        }
        
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1100;
        }

        /* Profile Picture Styles */
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
            opacity: 1;
            transition: opacity 0.3s;
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
            display: none;
        }
        
        /* Sidebar toggle button */
        .sidebar-toggle {
            position: fixed;
            left: 10px;
            top: 10px;
            z-index: 1100;
            background-color: #ffffff;
            color: #FF902B;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        @media (max-width: 991.98px) {
            .sidebar {
                width: 250px;
                transform: translateX(-100%);
                z-index: 1050;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: flex;
            }
            
            .sidebar:hover {
                width: 250px;
            }
            
            .sidebar:hover .nav-link-text,
            .sidebar.show .nav-link-text {
                display: inline;
            }
            
            .profile-picture-container {
                width: 120px;
                height: 120px;
            }
            .edit-profile-picture {
                width: 35px;
                height: 35px;
            }
        }
    </style>
    <title>Navigation</title>
</head>
<body>
<!-- Sidebar Toggle Button -->
<button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <ul class="sidebar-nav">
        <?php if ($role === 'owner'): ?>
          
            <!-- Direct links for owner -->
               <li class="sidebar-item">
                <a href="./../views/index.php" class="sidebar-link">
                  
                    <span class="nav-link-text fs-5 fw-bold">Kahel Cafe</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="./../views/accountManagement.php" class="sidebar-link">
                    <i class="sidebar-icon fas fa-users-cog"></i>
                    <span class="nav-link-text">Account Management</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="./../views/client.php" class="sidebar-link">
                    <i class="sidebar-icon fas fa-users"></i>
                    <span class="nav-link-text">Client Management</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="./../views/index.php" class="sidebar-link">
                    <i class="sidebar-icon fas fa-shopping-cart"></i>
                    <span class="nav-link-text">Order Management</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="./../views/reservation.php" class="sidebar-link">
                    <i class="sidebar-icon fas fa-calendar-alt"></i>
                    <span class="nav-link-text">Reservation Management</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="./../views/report.php" class="sidebar-link">
                    <i class="sidebar-icon fas fa-chart-line"></i>
                    <span class="nav-link-text">Performance Report</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="../views/menuManagement.php" class="sidebar-link">
                    <i class="sidebar-icon fas fa-utensils"></i>
                    <span class="nav-link-text">Menu Management</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="../views/content.php" class="sidebar-link">
                    <i class="sidebar-icon fas fa-desktop"></i>
                    <span class="nav-link-text">Virtual Management</span>
                </a>
            </li>

             <li class="sidebar-item">
                <a href="../views/payment_settings.php" class="sidebar-link">
                   <i class="sidebar-icon fas fa-money-check-alt"></i>

                    <span class="nav-link-text">Payment Settings</span>
                </a>
            </li>
        <?php elseif ($role === 'staff'): ?>
            <!-- Direct links for staff -->
            <li class="sidebar-item">
                <a href="./../views/index.php.php" class="sidebar-link">
                    <i class="sidebar-icon fas fa-shopping-cart"></i>
                    <span class="nav-link-text">Order Management</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="./../views/reservation.php" class="sidebar-link">
                    <i class="sidebar-icon fas fa-calendar-alt"></i>
                    <span class="nav-link-text">Reservation Management</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="../views/menuManagement.php" class="sidebar-link">
                    <i class="sidebar-icon fas fa-utensils"></i>
                    <span class="nav-link-text">Menu Management</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<!-- Main Content -->
<div class="main-content">
<nav class="navbar navbar-expand navbar-default shadow-bottom">
    <div class="container-fluid navbar-container">
        <!-- Brand/logo on the left -->
        <a class="navbar-brand me-auto" href="#">
            <img src="./../../components/icon/kahel-cafe-logo.png" alt="MyWebsite" class="img-fluid" style="max-height: 60px;">
        </a>

        <!-- Navigation items - all pushed to the right -->
        <div class="d-flex align-items-center ms-auto">
            <!-- Notification Icon -->
            <li class="nav-item position-relative mx-2" style="list-style-type: none; padding-left: 0; margin: 0;">
                <?php include "noti.php" ?>
            </li>

            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-black" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo htmlspecialchars($username); ?>(<?php echo htmlspecialchars($role); ?>)
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changeProfileModal"><i class="fa-regular fa-user me-2" style="color: #FF902B;"></i>Change Profile</a></li>
                    <li><a class="dropdown-item" href="./../views/login.php"><i class="fa-solid fa-power-off me-2" style="color: #FF902B;"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

    <!-- Change Profile Modal -->
    <div class="modal fade" id="changeProfileModal" tabindex="-1" aria-labelledby="changeProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeProfileModalLabel">Change Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($successMessage): ?>
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

                    <form method="POST" id="profileForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
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
                            <input type="checkbox" class="form-check-input" id="changePasswordCheckbox">
                            <label class="form-check-label" for="changePasswordCheckbox">Change Password</label>
                        </div>

                        <div class="password-fields" id="passwordFields">
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

    <!-- Toast Notification -->
    <div class="toast-container">
        <div id="errorToast" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toastMessage"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Toggle sidebar on mobile
        $('#sidebarToggle').click(function() {
            $('#sidebar').toggleClass('show');
        });

        // Close sidebar when clicking outside on mobile
        $(document).click(function(event) {
            if ($(window).width() < 992) {
                if (!$(event.target).closest('#sidebar, #sidebarToggle').length) {
                    $('#sidebar').removeClass('show');
                }
            }
        });

        // Toggle password fields visibility
        $('#changePasswordCheckbox').change(function() {
            if (this.checked) {
                $('#passwordFields').show();
            } else {
                $('#passwordFields').hide();
                $('#newPassword').val('');
                $('#confirmPassword').val('');
                $('#password-strength').text('');
            }
        });

        // Password strength detection
        $('#newPassword').on('input', function() {
            const password = $(this).val();
            const strengthElement = $('#password-strength');
            
            if (password.length === 0) {
                strengthElement.text('');
                return;
            }
            
            if (password.length < 8) {
                strengthElement.text('Strength: Weak').removeClass().addClass('password-strength weak');
            } else if (/[A-Za-z]/.test(password) && /\d/.test(password)) {
                strengthElement.text('Strength: Medium').removeClass().addClass('password-strength medium');
            } else {
                strengthElement.text('Strength: Strong').removeClass().addClass('password-strength strong');
            }
        });

        // Profile form submission
        $('#profileForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize();
            
            $.ajax({
                url: '',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#changeProfileModal .alert-danger').remove();
                        $('#changeProfileModal .modal-body').prepend(
                            '<div class="alert alert-success">Profile updated successfully!</div>'
                        );
                        
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        let errorHtml = '';
                        if (response.errors) {
                            errorHtml = response.errors.map(error => `<div>${error}</div>`).join('');
                        } else {
                            errorHtml = `<div>${response.message}</div>`;
                        }
                        
                        $('#changeProfileModal .alert-danger').remove();
                        $('#changeProfileModal .modal-body').prepend(
                            `<div class="alert alert-danger" role="alert">${errorHtml}</div>`
                        );
                    }
                },
                error: function(xhr, status, error) {
                    showToast('Error updating profile: ' + error);
                }
            });
        });

        // Profile picture form submission
        $('#profilePictureForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            
            $.ajax({
                url: '',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    location.reload();
                },
                error: function(xhr, status, error) {
                    showToast('Error updating profile picture: ' + error);
                }
            });
        });

        // Automatically hide the success message after 5 seconds
        setTimeout(function() {
            $('#successMessage').fadeOut();
        }, 5000);

        // Preview profile picture before upload
        $('#profile_picture').change(function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = $('#profilePictureModal .profile-picture');
                    if (preview.length) {
                        preview.attr('src', event.target.result);
                    } else {
                        const placeholder = $('#profilePictureModal .profile-picture-placeholder');
                        if (placeholder.length) {
                            placeholder.html(`<img src="${event.target.result}" alt="Preview" class="profile-picture" style="width: 100%; height: 100%;">`);
                        }
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });

    function showToast(message) {
        const toast = new bootstrap.Toast(document.getElementById('errorToast'));
        $('#toastMessage').text(message);
        toast.show();
    }
    </script>
</div>
</body>
</html>