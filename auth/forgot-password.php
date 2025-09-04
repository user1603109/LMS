<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $captcha = sanitizeInput($_POST['captcha']);

    // Verify CAPTCHA
    if (!verifyCaptcha($captcha)) {
        $error = 'Invalid CAPTCHA. Please try again.';
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate reset token
                $resetToken = generateToken();
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Store reset token
                $updateStmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?");
                $updateStmt->execute([$resetToken, $expiry, $user['id']]);

                // In a real application, you would send an email here
                $message = 'Password reset instructions have been sent to your email address.';
            } else {
                $error = 'No account found with that email address.';
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ASC Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gold: #FFD700;
            --primary-blue: #0033A0;
            --secondary-blue: #1e3a8a;
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

        .forgot-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }

        .forgot-header {
            background: var(--primary-gold);
            color: var(--primary-blue);
            padding: 2rem;
            text-align: center;
        }

        .forgot-form {
            padding: 2rem;
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

        .btn-reset {
            background: var(--primary-blue);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            width: 100%;
        }

        .btn-reset:hover {
            background: var(--secondary-blue);
        }

        .back-link {
            text-align: center;
            margin-top: 1rem;
        }

        .back-link a {
            color: var(--primary-blue);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <h1><i class="fas fa-key"></i> Forgot Password</h1>
            <p>Enter your email to reset password</p>
        </div>
        
        <div class="forgot-form">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="captcha" name="captcha" placeholder="Enter CAPTCHA" required>
                    <label for="captcha"><i class="fas fa-shield-alt"></i> CAPTCHA</label>
                </div>
                <div class="text-center mb-3">
                    <img src="captcha.php" alt="CAPTCHA" class="img-fluid" style="border-radius: 5px;">
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="refreshCaptcha()">
                        <i class="fas fa-refresh"></i>
                    </button>
                </div>

                <button type="submit" class="btn btn-primary btn-reset">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>

            <div class="back-link">
                <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        function refreshCaptcha() {
            document.querySelector('img[src*="captcha.php"]').src = 'captcha.php?' + Math.random();
        }
    </script>
</body>
</html>