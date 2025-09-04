<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = sanitizeInput($_POST['role']);
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $captcha = sanitizeInput($_POST['captcha']);

    // Verify CAPTCHA
    if (!verifyCaptcha($captcha)) {
        $_SESSION['error'] = 'Invalid CAPTCHA. Please try again.';
        header('Location: ../index.php');
        exit();
    }

    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND role = ? AND status = 'active'");
        $stmt->execute([$username, $role]);
        $user = $stmt->fetch();

        if ($user && verifyPassword($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['csrf_token'] = generateToken();

            // Update last login
            $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);

            header('Location: ../dashboard.php');
            exit();
        } else {
            $_SESSION['error'] = 'Invalid username, password, or role.';
            header('Location: ../index.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Database error. Please try again.';
        header('Location: ../index.php');
        exit();
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>