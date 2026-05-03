<?php
session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireAuth($role = null): void {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit;
    }

    if ($role && ($_SESSION['role'] !== $role)) {
        header('Location: ../unauthorized.php');
        exit;
    }
}

function logout(): void {
    session_unset();
    session_destroy();
    header('Location: ../index.php');
    exit;
}
