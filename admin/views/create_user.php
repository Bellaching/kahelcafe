<?php
header('Content-Type: application/json');

// Include your database connection
include './../../connection/connection.php';

$errors = [];

$email = $_POST['email'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';

// Validate inputs
if (empty($email)) {
    $errors['email'] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Invalid email format';
}

if (empty($username)) {
    $errors['username'] = 'Username is required';
} elseif (strlen($username) < 4) {
    $errors['username'] = 'Username must be at least 4 characters';
}

if (empty($password)) {
    $errors['password'] = 'Password is required';
} elseif (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters';
}

if (empty($role)) {
    $errors['role'] = 'Role is required';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $errors['email'] = 'Email already exists';
}

// Check if username already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    $errors['username'] = 'Username already exists';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Hash password
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
try {
    $stmt = $pdo->prepare("INSERT INTO admin_list (email, username, password, role) VALUES (?, ?, ?, ?)");
    $success = $stmt->execute([$email, $username, $passwordHash, $role]);
    
    if ($success && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create user']);
    }
} catch (PDOException $e) {
    // Handle duplicate entry in case of race condition
    if ($e->errorInfo[1] == 1062) { // MySQL duplicate entry error code
        echo json_encode(['success' => false, 'errors' => [
            'email' => 'Email or username already exists'
        ]]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>