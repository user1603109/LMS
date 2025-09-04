<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container-fluid">
        <button class="btn btn-outline-light me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
            <i class="fas fa-bars"></i>
        </button>
        
        <a class="navbar-brand fw-bold" href="../dashboard.php">
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
                <li><a class="dropdown-item" href="../profile.php"><i class="fas fa-user me-2"></i>View Profile</a></li>
                <li><a class="dropdown-item" href="../settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
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
                            <a class="nav-link" href="../cataloging/books.php">
                                <i class="fas fa-book me-2"></i>Books
                            </a>
                            <a class="nav-link" href="../cataloging/academic-coursework.php">
                                <i class="fas fa-graduation-cap me-2"></i>Academic Coursework
                            </a>
                            <a class="nav-link" href="../cataloging/electronic-resources.php">
                                <i class="fas fa-laptop me-2"></i>Electronic Resources
                            </a>
                            <a class="nav-link" href="../cataloging/serials.php">
                                <i class="fas fa-newspaper me-2"></i>Serials
                            </a>
                            <a class="nav-link" href="../cataloging/audio-visual.php">
                                <i class="fas fa-video me-2"></i>Audio-Visual Materials
                            </a>
                            <a class="nav-link" href="../cataloging/audio-records.php">
                                <i class="fas fa-microphone me-2"></i>Audio Records
                            </a>
                            <a class="nav-link" href="../cataloging/video-records.php">
                                <i class="fas fa-film me-2"></i>Video Records
                            </a>
                        </div>
                    </div>
                </div>
                <a class="nav-link" href="../patrons/index.php">
                    <i class="fas fa-users me-2"></i>PATRON
                </a>
            </div>

            <!-- CIRCULATION Section -->
            <div class="nav-section">
                <h6 class="nav-section-title">CIRCULATION</h6>
                <a class="nav-link" href="../circulation/borrow-return.php">
                    <i class="fas fa-exchange-alt me-2"></i>Borrow-Return
                </a>
                <a class="nav-link" href="../circulation/reservations.php">
                    <i class="fas fa-calendar-check me-2"></i>Resource Reservation
                </a>
                <a class="nav-link" href="../circulation/fines.php">
                    <i class="fas fa-dollar-sign me-2"></i>Fines & Payment
                </a>
            </div>

            <!-- INVENTORY Section -->
            <div class="nav-section">
                <h6 class="nav-section-title">INVENTORY</h6>
                <a class="nav-link" href="../inventory/management.php">
                    <i class="fas fa-boxes me-2"></i>Management
                </a>
                <a class="nav-link" href="../inventory/acquisition.php">
                    <i class="fas fa-plus-circle me-2"></i>Acquisition
                </a>
            </div>

            <!-- REPORTS Section -->
            <div class="nav-section">
                <h6 class="nav-section-title">REPORTS</h6>
                <a class="nav-link" href="../reports/accession-list.php">
                    <i class="fas fa-list me-2"></i>Accession List
                </a>
                <a class="nav-link" href="../reports/patron-masterlist.php">
                    <i class="fas fa-users me-2"></i>Patron Masterlist
                </a>
            </div>

            <?php if ($_SESSION['role'] === 'admin'): ?>
            <!-- ADMINISTRATION Section -->
            <div class="nav-section">
                <h6 class="nav-section-title">ADMINISTRATION</h6>
                <a class="nav-link" href="../admin/staff-management.php">
                    <i class="fas fa-user-tie me-2"></i>Staff Management
                </a>
                <a class="nav-link" href="../admin/roles-permissions.php">
                    <i class="fas fa-shield-alt me-2"></i>Roles & Permissions
                </a>
                <a class="nav-link" href="../admin/user-admin.php">
                    <i class="fas fa-user-cog me-2"></i>User Admin
                </a>
            </div>
            <?php endif; ?>

            <!-- SETTINGS Section -->
            <div class="nav-section">
                <h6 class="nav-section-title">SETTINGS</h6>
                <a class="nav-link" href="../settings/export-backups.php">
                    <i class="fas fa-download me-2"></i>Export & Backups
                </a>
                <a class="nav-link" href="../settings/audit-logs.php">
                    <i class="fas fa-history me-2"></i>Audit Logs
                </a>
                <a class="nav-link" href="../settings/user-settings.php">
                    <i class="fas fa-user-edit me-2"></i>User Settings
                </a>
            </div>

            <!-- ABOUT Section -->
            <div class="nav-section">
                <h6 class="nav-section-title">ABOUT</h6>
                <a class="nav-link" href="../about/system.php">
                    <i class="fas fa-info-circle me-2"></i>System
                </a>
                <a class="nav-link" href="../about/developers.php">
                    <i class="fas fa-code me-2"></i>Developers
                </a>
            </div>

            <!-- HELP Section -->
            <div class="nav-section">
                <h6 class="nav-section-title">HELP</h6>
                <a class="nav-link" href="../help/contact.php">
                    <i class="fas fa-envelope me-2"></i>Contact Us
                </a>
                <a class="nav-link" href="../help/report.php">
                    <i class="fas fa-bug me-2"></i>Send Report
                </a>
            </div>
        </nav>
    </div>
</div>