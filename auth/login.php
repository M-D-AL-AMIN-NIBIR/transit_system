<?php
require_once '../includes/db.php';
session_start();

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

// Validate input
if (empty($email) || empty($password)) {
    header('Location: ../index.php?error=missing_fields');
    exit;
}

// SECURE_QUERY - Prepared statement for user lookup
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password_hash'])) {
    // Session Protection - regenerate session ID
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['user_name'] = htmlspecialchars($user['name']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // Redirect based on role
    if ($user['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../passenger/dashboard.php');
    }
    exit;
}

header('Location: ../index.php?error=invalid');
exit;
