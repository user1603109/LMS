<?php
// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit();
    }
}

function requireRole($requiredRole) {
    requireLogin();
    if ($_SESSION['role'] !== $requiredRole) {
        header('Location: ../dashboard.php');
        exit();
    }
}

function requireRoles($requiredRoles) {
    requireLogin();
    if (!in_array($_SESSION['role'], $requiredRoles)) {
        header('Location: ../dashboard.php');
        exit();
    }
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateCaptcha() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $captcha = '';
    for ($i = 0; $i < 5; $i++) {
        $captcha .= $characters[rand(0, strlen($characters) - 1)];
    }
    $_SESSION['captcha'] = $captcha;
    return $captcha;
}

function verifyCaptcha($input) {
    return isset($_SESSION['captcha']) && strtoupper($input) === strtoupper($_SESSION['captcha']);
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function validateToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>