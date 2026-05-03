<?php
require_once '../includes/db.php';

// SECURE_QUERY - Input validation and sanitization
$name = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';
$phone = htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8');

// Validate required fields
if (empty($name) || empty($email) || empty($password)) {
    header('Location: ../index.php?error=missing_fields');
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../index.php?error=invalid_email');
    exit;
}

// Password hashing for security
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if email already exists
    $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    
    if ($checkStmt->fetch()) {
        header('Location: ../index.php?error=email_exists');
        exit;
    }
    
    // SECURE_QUERY - Prepared statement for user insertion
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'passenger')");
    $stmt->execute([$name, $email, $passwordHash]);
    
    $userId = $pdo->lastInsertId();
    
    // SECURE_QUERY - Prepared statement for profile insertion
    $stmt2 = $pdo->prepare("INSERT INTO passenger_profiles (user_id, phone) VALUES (?, ?)");
    $stmt2->execute([$userId, $phone]);
    
    header('Location: ../index.php?registered=success');
    exit;
    
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    header('Location: ../index.php?error=registration_failed');
    exit;
}
