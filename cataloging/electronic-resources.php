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
        $whereClause = "WHERE (title LIKE ? OR creator LIKE ? OR publisher LIKE ? OR identifier LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }

    if (!empty($filter)) {
        $whereClause .= empty($whereClause) ? 'WHERE ' : ' AND ';
        $whereClause .= "type_of_material = ?";
        $params[] = $filter;
    }

    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM electronic_resources $whereClause");
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Get electronic resources
    $electronicStmt = $db->prepare("
        SELECT * FROM electronic_resources 
        $whereClause 
        ORDER BY date_entered DESC 
        LIMIT $limit OFFSET $offset
    ");
    $electronicStmt->execute($params);
    $electronicItems = $electronicStmt->fetchAll();

} catch (PDOException $e) {
    $electronicItems = [];
    $totalPages = 0;
    $totalRecords = 0;
}

// Get material types for filter
$materialTypes = [
    'E-Book' => 'E-Book',
    'E-Journal' => 'E-Journal',
    'Database' => 'Database',
    'Website' => 'Website',
    'Software' => 'Software',
    'Multimedia' => 'Multimedia',
    'Online Course' => 'Online Course',
    'Research Data' => 'Research Data',
    'Digital Archive' => 'Digital Archive',
    'Streaming Media' => 'Streaming Media'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electronic Resources - ASC Library Management System</title>
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

        .electronic-link {
            color: var(--primary-blue);
            text-decoration: none;
        }

        .electronic-link:hover {
            text-decoration: underline;
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
                                <i class="fas fa-laptop me-2"></i>Electronic Resources
                            </h1>
                            <p class="page-subtitle">Manage digital and electronic resources</p>
                        </div>
                        <div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addElectronicModal">
                                <i class="fas fa-plus me-2"></i>Add New Resource
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
                                   placeholder="Search resources..." value="<?php echo htmlspecialchars($search); ?>">
                            <label for="search"><i class="fas fa-search me-2"></i>Search</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select class="form-select" id="filter" name="filter">
                                <option value="">All Material Types</option>
                                <?php foreach ($materialTypes as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $filter === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="filter">Filter by Type</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="electronic-resources.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times me-2"></i>Clear
                        </a>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-success w-100" onclick="exportElectronic()">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Electronic Resources Table -->
            <div class="table-container">
                <div class="table-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Electronic Resources Catalog
                        <span class="badge bg-light text-dark ms-2"><?php echo number_format($totalRecords); ?> records</span>
                    </h5>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Creator</th>
                                <th>Publisher</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Access</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($electronicItems)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-laptop fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No electronic resources found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($electronicItems as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['identifier']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="electronic-title">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                                <?php if ($item['description']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($item['description'], 0, 100)) . '...'; ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['creator']); ?></td>
                                        <td><?php echo htmlspecialchars($item['publisher']); ?></td>
                                        <td><?php echo $item['date'] ? date('Y', strtotime($item['date'])) : '-'; ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($item['type_of_material']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($item['electronic_access']): ?>
                                                <a href="<?php echo htmlspecialchars($item['electronic_access']); ?>" 
                                                   target="_blank" class="electronic-link">
                                                    <i class="fas fa-external-link-alt me-1"></i>Access
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">No link</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewElectronic(<?php echo $item['id']; ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" 
                                                        onclick="editElectronic(<?php echo $item['id']; ?>)"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteElectronic(<?php echo $item['id']; ?>)"
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
                        <nav aria-label="Electronic resources pagination">
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

    <!-- Add Electronic Resource Modal -->
    <div class="modal fade" id="addElectronicModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Electronic Resource
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addElectronicForm" method="POST" action="api/electronic-resources.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="title" name="title" required>
                                    <label for="title">Title *</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="identifier" name="identifier">
                                    <label for="identifier">Identifier</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="creator" name="creator">
                                    <label for="creator">Creator/Author</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="publisher" name="publisher">
                                    <label for="publisher">Publisher</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="date" class="form-control" id="date" name="date">
                                    <label for="date">Date</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="type_of_material" name="type_of_material">
                                        <option value="">Select Type</option>
                                        <?php foreach ($materialTypes as $value => $label): ?>
                                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="type_of_material">Material Type</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="format_language" name="format_language">
                                    <label for="format_language">Format/Language</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="url" class="form-control" id="electronic_access" name="electronic_access">
                            <label for="electronic_access">Electronic Access URL</label>
                        </div>

                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="description" name="description" style="height: 100px"></textarea>
                            <label for="description">Description</label>
                        </div>

                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="subjects" name="subjects" style="height: 80px"></textarea>
                            <label for="subjects">Subjects/Keywords</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Resource
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function viewElectronic(id) {
            window.location.href = `view-electronic.php?id=${id}`;
        }

        function editElectronic(id) {
            window.location.href = `edit-electronic.php?id=${id}`;
        }

        function deleteElectronic(id) {
            if (confirm('Are you sure you want to delete this electronic resource?')) {
                fetch(`api/electronic-resources.php?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting resource: ' + data.message);
                    }
                });
            }
        }

        function exportElectronic() {
            const params = new URLSearchParams(window.location.search);
            window.open(`api/export-electronic.php?${params.toString()}`, '_blank');
        }

        // Form submission
        document.getElementById('addElectronicForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create');

            fetch('api/electronic-resources.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error adding resource: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>