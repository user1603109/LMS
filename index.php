<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Redirect to dashboard if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASC Library Management System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gold: #FFD700;
            --primary-blue: #0033A0;
            --secondary-blue: #1e3a8a;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }

        .login-header {
            background: var(--primary-gold);
            color: var(--primary-blue);
            padding: 2rem;
            text-align: center;
        }

        .login-header h1 {
            font-weight: 700;
            margin: 0;
            font-size: 1.8rem;
        }

        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.8;
        }

        .login-form {
            padding: 2rem;
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
        }

        .form-control:focus {
            border-color: var(--primary-gold);
            box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
        }

        .btn-login {
            background: var(--primary-blue);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: var(--secondary-blue);
            transform: translateY(-2px);
        }

        .role-selector {
            margin-bottom: 1rem;
        }

        .role-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .role-option:hover {
            border-color: var(--primary-gold);
            background: rgba(255, 215, 0, 0.1);
        }

        .role-option.selected {
            border-color: var(--primary-gold);
            background: rgba(255, 215, 0, 0.2);
        }

        .role-option input[type="radio"] {
            margin-right: 0.5rem;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }

        .forgot-password a {
            color: var(--primary-blue);
            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-book"></i> ASC Library</h1>
            <p>Management System</p>
        </div>
        
        <div class="login-form">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="auth/login.php">
                <div class="role-selector">
                    <h6 class="mb-3">Select Role:</h6>
                    <div class="role-option" onclick="selectRole('admin')">
                        <input type="radio" name="role" value="admin" id="admin" required>
                        <label for="admin"><i class="fas fa-user-shield"></i> Administrator</label>
                    </div>
                    <div class="role-option" onclick="selectRole('librarian')">
                        <input type="radio" name="role" value="librarian" id="librarian" required>
                        <label for="librarian"><i class="fas fa-user-tie"></i> Librarian</label>
                    </div>
                    <div class="role-option" onclick="selectRole('student')">
                        <input type="radio" name="role" value="student" id="student" required>
                        <label for="student"><i class="fas fa-user-graduate"></i> Student</label>
                    </div>
                </div>

                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                </div>

                <div class="form-floating">
                    <input type="text" class="form-control" id="captcha" name="captcha" placeholder="Enter CAPTCHA" required>
                    <label for="captcha"><i class="fas fa-shield-alt"></i> CAPTCHA</label>
                </div>
                <div class="text-center mb-3">
                    <img src="auth/captcha.php" alt="CAPTCHA" class="img-fluid" style="border-radius: 5px;">
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="refreshCaptcha()">
                        <i class="fas fa-refresh"></i>
                    </button>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="forgot-password">
                <a href="auth/forgot-password.php">Forgot Password?</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectRole(role) {
            // Remove selected class from all options
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            document.getElementById(role).checked = true;
        }

        function refreshCaptcha() {
            document.querySelector('img[src*="captcha.php"]').src = 'auth/captcha.php?' + Math.random();
        }
    </script>
</body>
</html>