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
        $whereClause = "WHERE (title LIKE ? OR creator LIKE ? OR institution LIKE ? OR accession LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }

    if (!empty($filter)) {
        $whereClause .= empty($whereClause) ? 'WHERE ' : ' AND ';
        $whereClause .= "type_of_research_study = ?";
        $params[] = $filter;
    }

    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM academic_coursework $whereClause");
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Get academic coursework
    $academicStmt = $db->prepare("
        SELECT * FROM academic_coursework 
        $whereClause 
        ORDER BY date_entered DESC 
        LIMIT $limit OFFSET $offset
    ");
    $academicStmt->execute($params);
    $academicItems = $academicStmt->fetchAll();

} catch (PDOException $e) {
    $academicItems = [];
    $totalPages = 0;
    $totalRecords = 0;
}

// Get research study types for filter
$researchTypes = [
    'Thesis' => 'Thesis',
    'Dissertation' => 'Dissertation',
    'Research Paper' => 'Research Paper',
    'Capstone Project' => 'Capstone Project',
    'Case Study' => 'Case Study',
    'Survey Research' => 'Survey Research',
    'Experimental Study' => 'Experimental Study',
    'Qualitative Research' => 'Qualitative Research',
    'Quantitative Research' => 'Quantitative Research',
    'Mixed Methods' => 'Mixed Methods'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Coursework - ASC Library Management System</title>
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
                                <i class="fas fa-graduation-cap me-2"></i>Academic Coursework
                            </h1>
                            <p class="page-subtitle">Manage academic research and coursework</p>
                        </div>
                        <div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAcademicModal">
                                <i class="fas fa-plus me-2"></i>Add New Item
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
                                   placeholder="Search coursework..." value="<?php echo htmlspecialchars($search); ?>">
                            <label for="search"><i class="fas fa-search me-2"></i>Search</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select class="form-select" id="filter" name="filter">
                                <option value="">All Research Types</option>
                                <?php foreach ($researchTypes as $value => $label): ?>
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
                        <a href="academic-coursework.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times me-2"></i>Clear
                        </a>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-success w-100" onclick="exportAcademic()">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Academic Coursework Table -->
            <div class="table-container">
                <div class="table-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Academic Coursework Catalog
                        <span class="badge bg-light text-dark ms-2"><?php echo number_format($totalRecords); ?> records</span>
                    </h5>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Accession</th>
                                <th>Title</th>
                                <th>Creator</th>
                                <th>Institution</th>
                                <th>Program/Course</th>
                                <th>Year</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($academicItems)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No academic coursework found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($academicItems as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['accession']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="academic-title">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                                <?php if ($item['call_number']): ?>
                                                    <br><small class="text-muted">Call #: <?php echo htmlspecialchars($item['call_number']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['creator']); ?></td>
                                        <td><?php echo htmlspecialchars($item['institution']); ?></td>
                                        <td><?php echo htmlspecialchars($item['program_course']); ?></td>
                                        <td><?php echo $item['date_year'] ?: '-'; ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($item['type_of_research_study']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($item['on_shelf']): ?>
                                                <span class="badge bg-success status-badge">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning status-badge">Checked Out</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewAcademic(<?php echo $item['id']; ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" 
                                                        onclick="editAcademic(<?php echo $item['id']; ?>)"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteAcademic(<?php echo $item['id']; ?>)"
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
                        <nav aria-label="Academic coursework pagination">
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

    <!-- Add Academic Coursework Modal -->
    <div class="modal fade" id="addAcademicModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Academic Coursework
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addAcademicForm" method="POST" action="api/academic-coursework.php">
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
                                    <input type="text" class="form-control" id="accession" name="accession" required>
                                    <label for="accession">Accession Number *</label>
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
                                    <input type="text" class="form-control" id="institution" name="institution">
                                    <label for="institution">Institution</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="program_course" name="program_course">
                                    <label for="program_course">Program/Course</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <input type="number" class="form-control" id="date_year" name="date_year" min="1900" max="2100">
                                    <label for="date_year">Year</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="type_of_research_study" name="type_of_research_study">
                                        <option value="">Select Type</option>
                                        <?php foreach ($researchTypes as $value => $label): ?>
                                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="type_of_research_study">Research Type</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="call_number" name="call_number">
                                    <label for="call_number">Call Number</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="language" name="language">
                                    <label for="language">Language</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="location" name="location">
                                    <label for="location">Location</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="abstract" name="abstract" style="height: 100px"></textarea>
                            <label for="abstract">Abstract</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function viewAcademic(id) {
            window.location.href = `view-academic.php?id=${id}`;
        }

        function editAcademic(id) {
            window.location.href = `edit-academic.php?id=${id}`;
        }

        function deleteAcademic(id) {
            if (confirm('Are you sure you want to delete this academic coursework?')) {
                fetch(`api/academic-coursework.php?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting item: ' + data.message);
                    }
                });
            }
        }

        function exportAcademic() {
            const params = new URLSearchParams(window.location.search);
            window.open(`api/export-academic.php?${params.toString()}`, '_blank');
        }

        // Form submission
        document.getElementById('addAcademicForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create');

            fetch('api/academic-coursework.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error adding item: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>