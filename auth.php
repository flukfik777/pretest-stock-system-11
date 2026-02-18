<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        die("Access Denied: Admins only.");
    }
}

function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) {
        return [
            'username' => 'Guest',
            'role' => 'user'
        ];
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: [
        'username' => 'Guest',
        'role' => 'user'
    ];
}
?>
