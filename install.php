<?php
// ASC Library Management System - Installation Script

// Check if already installed
if (file_exists('config/installed.lock')) {
    die('System is already installed. Delete config/installed.lock to reinstall.');
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            // Database configuration
            $dbHost = $_POST['db_host'] ?? 'localhost';
            $dbName = $_POST['db_name'] ?? 'asc_library';
            $dbUser = $_POST['db_user'] ?? 'root';
            $dbPass = $_POST['db_pass'] ?? '';
            
            try {
                // Test database connection
                $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Create database if it doesn't exist
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
                $pdo->exec("USE `$dbName`");
                
                // Update database configuration
                $configContent = "<?php
// Database configuration
define('DB_HOST', '$dbHost');
define('DB_NAME', '$dbName');
define('DB_USER', '$dbUser');
define('DB_PASS', '$dbPass');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private \$host = DB_HOST;
    private \$db_name = DB_NAME;
    private \$username = DB_USER;
    private \$password = DB_PASS;
    private \$charset = DB_CHARSET;
    public \$conn;

    public function getConnection() {
        \$this->conn = null;
        
        try {
            \$dsn = \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name . \";charset=\" . \$this->charset;
            \$this->conn = new PDO(\$dsn, \$this->username, \$this->password);
            \$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            \$this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException \$exception) {
            echo \"Connection error: \" . \$exception->getMessage();
        }
        
        return \$this->conn;
    }
}

// Create database connection
\$database = new Database();
\$db = \$database->getConnection();
?>";
                
                file_put_contents('config/database.php', $configContent);
                $success = 'Database configuration saved successfully!';
                $step = 2;
                
            } catch (PDOException $e) {
                $error = 'Database connection failed: ' . $e->getMessage();
            }
            break;
            
        case 2:
            // Create database tables
            try {
                require_once 'config/database.php';
                
                // Read and execute schema
                $schema = file_get_contents('database/schema.sql');
                $statements = explode(';', $schema);
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $db->exec($statement);
                    }
                }
                
                $success = 'Database tables created successfully!';
                $step = 3;
                
            } catch (Exception $e) {
                $error = 'Failed to create database tables: ' . $e->getMessage();
            }
            break;
            
        case 3:
            // Create admin user
            $adminUsername = $_POST['admin_username'] ?? 'admin';
            $adminPassword = $_POST['admin_password'] ?? '';
            $adminName = $_POST['admin_name'] ?? 'System Administrator';
            $adminEmail = $_POST['admin_email'] ?? 'admin@asclibrary.edu';
            
            if (empty($adminPassword)) {
                $error = 'Admin password is required!';
            } else {
                try {
                    require_once 'config/database.php';
                    
                    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
                    
                    $stmt = $db->prepare("UPDATE users SET username = ?, password = ?, name = ?, email = ? WHERE role = 'admin'");
                    $stmt->execute([$adminUsername, $hashedPassword, $adminName, $adminEmail]);
                    
                    // Create installation lock file
                    file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
                    
                    $success = 'Installation completed successfully! You can now login to the system.';
                    $step = 4;
                    
                } catch (Exception $e) {
                    $error = 'Failed to create admin user: ' . $e->getMessage();
                }
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASC Library Management System - Installation</title>
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

        .install-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }

        .install-header {
            background: var(--primary-gold);
            color: var(--primary-blue);
            padding: 2rem;
            text-align: center;
        }

        .install-header h1 {
            font-weight: 700;
            margin: 0;
            font-size: 1.8rem;
        }

        .install-body {
            padding: 2rem;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: 600;
        }

        .step.active {
            background: var(--primary-blue);
            color: white;
        }

        .step.completed {
            background: var(--primary-gold);
            color: var(--primary-blue);
        }

        .step.pending {
            background: #e9ecef;
            color: #6c757d;
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

        .btn-primary {
            background: var(--primary-blue);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: var(--secondary-blue);
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1><i class="fas fa-book"></i> ASC Library</h1>
            <p>Management System Installation</p>
        </div>
        
        <div class="install-body">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : 'pending'; ?>">1</div>
                <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : 'pending'; ?>">2</div>
                <div class="step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : 'pending'; ?>">3</div>
                <div class="step <?php echo $step >= 4 ? 'active' : 'pending'; ?>">4</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <!-- Step 1: Database Configuration -->
                <h4 class="mb-3">Database Configuration</h4>
                <form method="POST">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                        <label for="db_host">Database Host</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="db_name" name="db_name" value="asc_library" required>
                        <label for="db_name">Database Name</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                        <label for="db_user">Database Username</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="db_pass" name="db_pass">
                        <label for="db_pass">Database Password</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-arrow-right me-2"></i>Next Step
                    </button>
                </form>

            <?php elseif ($step == 2): ?>
                <!-- Step 2: Create Tables -->
                <h4 class="mb-3">Create Database Tables</h4>
                <p class="text-muted mb-4">Click the button below to create all necessary database tables.</p>
                <form method="POST">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-database me-2"></i>Create Tables
                    </button>
                </form>

            <?php elseif ($step == 3): ?>
                <!-- Step 3: Admin User -->
                <h4 class="mb-3">Create Administrator Account</h4>
                <form method="POST">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="admin_username" name="admin_username" value="admin" required>
                        <label for="admin_username">Admin Username</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                        <label for="admin_password">Admin Password</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="admin_name" name="admin_name" value="System Administrator" required>
                        <label for="admin_name">Admin Full Name</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="admin_email" name="admin_email" value="admin@asclibrary.edu" required>
                        <label for="admin_email">Admin Email</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-user-shield me-2"></i>Complete Installation
                    </button>
                </form>

            <?php elseif ($step == 4): ?>
                <!-- Step 4: Installation Complete -->
                <div class="text-center">
                    <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                    <h4 class="mb-3">Installation Complete!</h4>
                    <p class="text-muted mb-4">Your ASC Library Management System has been successfully installed.</p>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>