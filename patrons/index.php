<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
requireRoles(['admin', 'librarian']);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$filter = isset($_GET['filter']) ? sanitizeInput($_GET['filter']) : '';

try {
    // Build query
    $whereClause = '';
    $params = [];

    if (!empty($search)) {
        $whereClause = "WHERE (name LIKE ? OR id_number LIKE ? OR email LIKE ? OR course_department LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }

    if (!empty($filter)) {
        $whereClause .= empty($whereClause) ? 'WHERE ' : ' AND ';
        $whereClause .= "status = ?";
        $params[] = $filter;
    }

    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM patrons $whereClause");
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Get patrons
    $patronsStmt = $db->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM circulation WHERE patron_id = p.id AND status = 'borrowed') as active_borrows,
               (SELECT SUM(fine) FROM circulation WHERE patron_id = p.id AND status = 'borrowed' AND due_date < CURDATE()) as total_fines
        FROM patrons p 
        $whereClause 
        ORDER BY date_entered DESC 
        LIMIT $limit OFFSET $offset
    ");
    $patronsStmt->execute($params);
    $patrons = $patronsStmt->fetchAll();

} catch (PDOException $e) {
    $patrons = [];
    $totalPages = 0;
    $totalRecords = 0;
}

// Get status options for filter
$statusOptions = [
    'active' => 'Active',
    'inactive' => 'Inactive',
    'suspended' => 'Suspended'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patron Management - ASC Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .table-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            color: white;
            padding: 1.5rem;
        }
        
        .table {
            margin: 0;
        }
        
        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
            color: var(--primary-blue);
        }
        
        .action-buttons .btn {
            margin: 0 2px;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .search-filters {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .patron-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gold);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-blue);
            font-weight: 600;
        }

        .fine-amount {
            color: var(--danger);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid py-4">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="page-title">
                                <i class="fas fa-users me-2"></i>Patron Management
                            </h1>
                            <p class="page-subtitle">Manage library patrons and members</p>
                        </div>
                        <div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPatronModal">
                                <i class="fas fa-user-plus me-2"></i>Add New Patron
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="search-filters">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Search patrons..." value="<?php echo htmlspecialchars($search); ?>">
                            <label for="search"><i class="fas fa-search me-2"></i>Search</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select class="form-select" id="filter" name="filter">
                                <option value="">All Status</option>
                                <?php foreach ($statusOptions as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $filter === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="filter">Filter by Status</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="index.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times me-2"></i>Clear
                        </a>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-success w-100" onclick="exportPatrons()">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Patrons Table -->
            <div class="table-container">
                <div class="table-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Patron Directory
                        <span class="badge bg-light text-dark ms-2"><?php echo number_format($totalRecords); ?> patrons</span>
                    </h5>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Avatar</th>
                                <th>Name</th>
                                <th>ID Number</th>
                                <th>Course/Department</th>
                                <th>Year Level</th>
                                <th>Contact</th>
                                <th>Active Borrows</th>
                                <th>Fines</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($patrons)): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No patrons found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($patrons as $patron): ?>
                                    <tr>
                                        <td>
                                            <div class="patron-avatar">
                                                <?php echo strtoupper(substr($patron['name'], 0, 2)); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="patron-info">
                                                <strong><?php echo htmlspecialchars($patron['name']); ?></strong>
                                                <br><small class="text-muted"><?php echo ucfirst($patron['gender']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($patron['id_number']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($patron['course_department']); ?>
                                            <?php if ($patron['group']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($patron['group']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($patron['year_level']); ?></td>
                                        <td>
                                            <div class="contact-info">
                                                <?php if ($patron['email']): ?>
                                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($patron['email']); ?><br>
                                                <?php endif; ?>
                                                <?php if ($patron['contact_number']): ?>
                                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($patron['contact_number']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $patron['active_borrows']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($patron['total_fines'] > 0): ?>
                                                <span class="fine-amount">$<?php echo number_format($patron['total_fines'], 2); ?></span>
                                            <?php else: ?>
                                                <span class="text-success">$0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            switch ($patron['status']) {
                                                case 'active':
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case 'inactive':
                                                    $statusClass = 'bg-secondary';
                                                    break;
                                                case 'suspended':
                                                    $statusClass = 'bg-danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?> status-badge">
                                                <?php echo ucfirst($patron['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewPatron(<?php echo $patron['id']; ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" 
                                                        onclick="editPatron(<?php echo $patron['id']; ?>)"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" 
                                                        onclick="viewBorrowHistory(<?php echo $patron['id']; ?>)"
                                                        title="Borrow History">
                                                    <i class="fas fa-history"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deletePatron(<?php echo $patron['id']; ?>)"
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="p-3">
                        <nav aria-label="Patrons pagination">
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Add Patron Modal -->
    <div class="modal fade" id="addPatronModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Add New Patron
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addPatronForm" method="POST" action="api/patrons.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="name" name="name" required>
                                    <label for="name">Full Name *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="id_number" name="id_number" required>
                                    <label for="id_number">ID Number *</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <label for="gender">Gender *</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="group" name="group">
                                    <label for="group">Group</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="year_level" name="year_level">
                                    <label for="year_level">Year Level</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="course_department" name="course_department">
                                    <label for="course_department">Course/Department</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control" id="email" name="email">
                                    <label for="email">Email Address</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="tel" class="form-control" id="contact_number" name="contact_number">
                                    <label for="contact_number">Contact Number</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="status" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="suspended">Suspended</option>
                                    </select>
                                    <label for="status">Status</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="address" name="address" style="height: 100px"></textarea>
                            <label for="address">Address</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Patron
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function viewPatron(id) {
            window.location.href = `view.php?id=${id}`;
        }

        function editPatron(id) {
            window.location.href = `edit.php?id=${id}`;
        }

        function viewBorrowHistory(id) {
            window.location.href = `borrow-history.php?id=${id}`;
        }

        function deletePatron(id) {
            if (confirm('Are you sure you want to delete this patron?')) {
                fetch(`api/patrons.php?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting patron: ' + data.message);
                    }
                });
            }
        }

        function exportPatrons() {
            const params = new URLSearchParams(window.location.search);
            window.open(`api/export-patrons.php?${params.toString()}`, '_blank');
        }

        // Form submission
        document.getElementById('addPatronForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create');

            fetch('api/patrons.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error adding patron: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>