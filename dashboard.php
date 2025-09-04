<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

// Get user info
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get dashboard statistics
try {
    // Books count
    $booksStmt = $db->prepare("SELECT COUNT(*) as count FROM books");
    $booksStmt->execute();
    $booksCount = $booksStmt->fetch()['count'];

    // Patrons count
    $patronsStmt = $db->prepare("SELECT COUNT(*) as count FROM patrons WHERE status = 'active'");
    $patronsStmt->execute();
    $patronsCount = $patronsStmt->fetch()['count'];

    // Active circulations
    $circulationStmt = $db->prepare("SELECT COUNT(*) as count FROM circulation WHERE status = 'borrowed'");
    $circulationStmt->execute();
    $circulationCount = $circulationStmt->fetch()['count'];

    // Overdue items
    $overdueStmt = $db->prepare("SELECT COUNT(*) as count FROM circulation WHERE status = 'borrowed' AND due_date < CURDATE()");
    $overdueStmt->execute();
    $overdueCount = $overdueStmt->fetch()['count'];

    // Recent activities
    $recentStmt = $db->prepare("
        SELECT 'book' as type, title, date_entered as date, 'Added' as action 
        FROM books 
        WHERE date_entered >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION ALL
        SELECT 'patron' as type, name, date_entered as date, 'Registered' as action 
        FROM patrons 
        WHERE date_entered >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY date DESC 
        LIMIT 10
    ");
    $recentStmt->execute();
    $recentActivities = $recentStmt->fetchAll();

} catch (PDOException $e) {
    $booksCount = $patronsCount = $circulationCount = $overdueCount = 0;
    $recentActivities = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ASC Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
                <i class="fas fa-bars"></i>
            </button>
            
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-book me-2"></i>ASC Library
            </a>

            <!-- Global Search Bar -->
            <div class="d-flex flex-grow-1 mx-4">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search catalog, patrons, resources..." id="globalSearch">
                    <button class="btn btn-warning" type="button" id="searchBtn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <!-- Profile Dropdown -->
            <div class="dropdown">
                <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-2"></i><?php echo $_SESSION['name']; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>View Profile</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar">
        <div class="offcanvas-header bg-primary text-white">
            <h5 class="offcanvas-title">
                <i class="fas fa-book me-2"></i>ASC Library
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="nav flex-column">
                <!-- FILES Section -->
                <div class="nav-section">
                    <h6 class="nav-section-title">FILES</h6>
                    <div class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#catalogingMenu" role="button">
                            <i class="fas fa-folder me-2"></i>CATALOGING
                            <i class="fas fa-chevron-down ms-auto"></i>
                        </a>
                        <div class="collapse" id="catalogingMenu">
                            <div class="nav-submenu">
                                <a class="nav-link" href="cataloging/books.php">
                                    <i class="fas fa-book me-2"></i>Books
                                </a>
                                <a class="nav-link" href="cataloging/academic-coursework.php">
                                    <i class="fas fa-graduation-cap me-2"></i>Academic Coursework
                                </a>
                                <a class="nav-link" href="cataloging/electronic-resources.php">
                                    <i class="fas fa-laptop me-2"></i>Electronic Resources
                                </a>
                                <a class="nav-link" href="cataloging/serials.php">
                                    <i class="fas fa-newspaper me-2"></i>Serials
                                </a>
                                <a class="nav-link" href="cataloging/audio-visual.php">
                                    <i class="fas fa-video me-2"></i>Audio-Visual Materials
                                </a>
                                <a class="nav-link" href="cataloging/audio-records.php">
                                    <i class="fas fa-microphone me-2"></i>Audio Records
                                </a>
                                <a class="nav-link" href="cataloging/video-records.php">
                                    <i class="fas fa-film me-2"></i>Video Records
                                </a>
                            </div>
                        </div>
                    </div>
                    <a class="nav-link" href="patrons/index.php">
                        <i class="fas fa-users me-2"></i>PATRON
                    </a>
                </div>

                <!-- CIRCULATION Section -->
                <div class="nav-section">
                    <h6 class="nav-section-title">CIRCULATION</h6>
                    <a class="nav-link" href="circulation/borrow-return.php">
                        <i class="fas fa-exchange-alt me-2"></i>Borrow-Return
                    </a>
                    <a class="nav-link" href="circulation/reservations.php">
                        <i class="fas fa-calendar-check me-2"></i>Resource Reservation
                    </a>
                    <a class="nav-link" href="circulation/fines.php">
                        <i class="fas fa-dollar-sign me-2"></i>Fines & Payment
                    </a>
                </div>

                <!-- INVENTORY Section -->
                <div class="nav-section">
                    <h6 class="nav-section-title">INVENTORY</h6>
                    <a class="nav-link" href="inventory/management.php">
                        <i class="fas fa-boxes me-2"></i>Management
                    </a>
                    <a class="nav-link" href="inventory/acquisition.php">
                        <i class="fas fa-plus-circle me-2"></i>Acquisition
                    </a>
                </div>

                <!-- REPORTS Section -->
                <div class="nav-section">
                    <h6 class="nav-section-title">REPORTS</h6>
                    <a class="nav-link" href="reports/accession-list.php">
                        <i class="fas fa-list me-2"></i>Accession List
                    </a>
                    <a class="nav-link" href="reports/patron-masterlist.php">
                        <i class="fas fa-users me-2"></i>Patron Masterlist
                    </a>
                </div>

                <?php if ($role === 'admin'): ?>
                <!-- ADMINISTRATION Section -->
                <div class="nav-section">
                    <h6 class="nav-section-title">ADMINISTRATION</h6>
                    <a class="nav-link" href="admin/staff-management.php">
                        <i class="fas fa-user-tie me-2"></i>Staff Management
                    </a>
                    <a class="nav-link" href="admin/roles-permissions.php">
                        <i class="fas fa-shield-alt me-2"></i>Roles & Permissions
                    </a>
                    <a class="nav-link" href="admin/user-admin.php">
                        <i class="fas fa-user-cog me-2"></i>User Admin
                    </a>
                </div>
                <?php endif; ?>

                <!-- SETTINGS Section -->
                <div class="nav-section">
                    <h6 class="nav-section-title">SETTINGS</h6>
                    <a class="nav-link" href="settings/export-backups.php">
                        <i class="fas fa-download me-2"></i>Export & Backups
                    </a>
                    <a class="nav-link" href="settings/audit-logs.php">
                        <i class="fas fa-history me-2"></i>Audit Logs
                    </a>
                    <a class="nav-link" href="settings/user-settings.php">
                        <i class="fas fa-user-edit me-2"></i>User Settings
                    </a>
                </div>

                <!-- ABOUT Section -->
                <div class="nav-section">
                    <h6 class="nav-section-title">ABOUT</h6>
                    <a class="nav-link" href="about/system.php">
                        <i class="fas fa-info-circle me-2"></i>System
                    </a>
                    <a class="nav-link" href="about/developers.php">
                        <i class="fas fa-code me-2"></i>Developers
                    </a>
                </div>

                <!-- HELP Section -->
                <div class="nav-section">
                    <h6 class="nav-section-title">HELP</h6>
                    <a class="nav-link" href="help/contact.php">
                        <i class="fas fa-envelope me-2"></i>Contact Us
                    </a>
                    <a class="nav-link" href="help/report.php">
                        <i class="fas fa-bug me-2"></i>Send Report
                    </a>
                </div>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid py-4">
            <!-- Welcome Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="welcome-card">
                        <h1 class="welcome-title">Welcome back, <?php echo $_SESSION['name']; ?>!</h1>
                        <p class="welcome-subtitle">Here's what's happening in your library today.</p>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-primary">
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo number_format($booksCount); ?></h3>
                            <p class="stat-label">Total Books</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-success">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo number_format($patronsCount); ?></h3>
                            <p class="stat-label">Active Patrons</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-warning">
                        <div class="stat-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo number_format($circulationCount); ?></h3>
                            <p class="stat-label">Active Circulations</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-danger">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number"><?php echo number_format($overdueCount); ?></h3>
                            <p class="stat-label">Overdue Items</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clock me-2"></i>Recent Activities
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentActivities)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No recent activities to display.</p>
                                </div>
                            <?php else: ?>
                                <div class="activity-list">
                                    <?php foreach ($recentActivities as $activity): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon">
                                                <i class="fas fa-<?php echo $activity['type'] === 'book' ? 'book' : 'user'; ?>"></i>
                                            </div>
                                            <div class="activity-content">
                                                <h6 class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></h6>
                                                <p class="activity-meta">
                                                    <?php echo $activity['action']; ?> • 
                                                    <?php echo date('M j, Y', strtotime($activity['date'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tachometer-alt me-2"></i>Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="cataloging/books.php" class="btn btn-outline-primary">
                                    <i class="fas fa-plus me-2"></i>Add New Book
                                </a>
                                <a href="patrons/index.php" class="btn btn-outline-success">
                                    <i class="fas fa-user-plus me-2"></i>Register Patron
                                </a>
                                <a href="circulation/borrow-return.php" class="btn btn-outline-warning">
                                    <i class="fas fa-exchange-alt me-2"></i>Process Borrow/Return
                                </a>
                                <a href="reports/accession-list.php" class="btn btn-outline-info">
                                    <i class="fas fa-file-export me-2"></i>Generate Report
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>