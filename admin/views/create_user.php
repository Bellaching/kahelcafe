<?php 
header('Content-Type: application/json');

// Include your database connection
include './../../connection/connection.php';

$response = ['success' => false, 'errors' => []];

// Validate required fields
$required = ['email', 'username', 'password', 'role'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $response['errors'][$field] = ucfirst($field) . ' is required';
    }
}

// Validate email format
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $response['errors']['email'] = 'Invalid email format';
}

// Validate username length
if (strlen($_POST['username']) < 4) {
    $response['errors']['username'] = 'Username must be at least 4 characters';
}

// Validate password strength
if (strlen($_POST['password']) < 8) {
    $response['errors']['password'] = 'Password must be at least 8 characters';
} elseif (!preg_match('/[A-Z]/', $_POST['password']) || 
           !preg_match('/[a-z]/', $_POST['password']) || 
           !preg_match('/[0-9]/', $_POST['password'])) {
    $response['errors']['password'] = 'Password must contain uppercase, lowercase letters and numbers';
}

// Validate role
if (!in_array($_POST['role'], ['owner', 'staff'])) {
    $response['errors']['role'] = 'Invalid role selected';
}

// Only proceed if no validation errors
if (empty($response['errors'])) {
    try {
        // Check for existing email or username
        $stmt = $pdo->prepare("SELECT id FROM admin_list WHERE email = ? OR username = ?");
        $stmt->execute([$_POST['email'], $_POST['username']]);
        
        if ($stmt->fetch()) {
            // Check which one exists
            $stmt = $pdo->prepare("SELECT id FROM admin_list WHERE email = ?");
            $stmt->execute([$_POST['email']]);
            if ($stmt->fetch()) {
                $response['errors']['email'] = 'Email already exists';
            }
            
            $stmt = $pdo->prepare("SELECT id FROM admin_list WHERE username = ?");
            $stmt->execute([$_POST['username']]);
            if ($stmt->fetch()) {
                $response['errors']['username'] = 'Username already exists';
            }
        } else {
            // Hash the password with bcrypt
            $passwordHash = password_hash($_POST['password'], PASSWORD_BCRYPT);
            
            if ($passwordHash === false) {
                throw new Exception('Password hashing failed');
            }
            
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO admin_list 
                                  (email, username, password, role, date_created) 
                                  VALUES (?, ?, ?, ?, NOW())");
            
            $success = $stmt->execute([
                $_POST['email'],
                $_POST['username'],
                $passwordHash,
                $_POST['role']
            ]);
            
            if ($success) {
                $response['success'] = true;
                $response['message'] = 'User created successfully';
                $response['user_id'] = $pdo->lastInsertId();
            } else {
                $response['error'] = 'Failed to create user';
            }
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $response['error'] = 'Database error occurred. Please try again.';
    }
}

echo json_encode($response);
?>