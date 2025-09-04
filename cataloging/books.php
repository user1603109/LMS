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
        $whereClause = "WHERE (title LIKE ? OR main_creator LIKE ? OR isbn LIKE ? OR accession_number LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }

    if (!empty($filter)) {
        $whereClause .= empty($whereClause) ? 'WHERE ' : ' AND ';
        $whereClause .= "prefix = ?";
        $params[] = $filter;
    }

    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM books $whereClause");
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Get books
    $booksStmt = $db->prepare("
        SELECT * FROM books 
        $whereClause 
        ORDER BY date_entered DESC 
        LIMIT $limit OFFSET $offset
    ");
    $booksStmt->execute($params);
    $books = $booksStmt->fetchAll();

} catch (PDOException $e) {
    $books = [];
    $totalPages = 0;
    $totalRecords = 0;
}

// Get prefix options for filter
$prefixOptions = [
    'CER' => 'CER',
    'CIR' => 'CIR', 
    'FIC' => 'FIC',
    'FIL' => 'FIL',
    'FOLIO' => 'FOLIO',
    'GN-FIC' => 'GN-FIC',
    'GN-NF' => 'GN-NF',
    'ISL' => 'ISL',
    'LR-FIC' => 'LR-FIC',
    'LR-NF' => 'LR-NF',
    'MEP' => 'MEP',
    'NF' => 'NF',
    'PAM' => 'PAM',
    'PB-FIC' => 'PB-FIC',
    'PB-NF' => 'PB-NF',
    'PHD' => 'PHD',
    'REF' => 'REF',
    'TR-FIC' => 'TR-FIC',
    'TR-NF' => 'TR-NF'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books - ASC Library Management System</title>
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
                                <i class="fas fa-book me-2"></i>Books Management
                            </h1>
                            <p class="page-subtitle">Manage library book catalog</p>
                        </div>
                        <div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                                <i class="fas fa-plus me-2"></i>Add New Book
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
                                   placeholder="Search books..." value="<?php echo htmlspecialchars($search); ?>">
                            <label for="search"><i class="fas fa-search me-2"></i>Search</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select class="form-select" id="filter" name="filter">
                                <option value="">All Prefixes</option>
                                <?php foreach ($prefixOptions as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $filter === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="filter">Filter by Prefix</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="books.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times me-2"></i>Clear
                        </a>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-success w-100" onclick="exportBooks()">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Books Table -->
            <div class="table-container">
                <div class="table-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Books Catalog
                        <span class="badge bg-light text-dark ms-2"><?php echo number_format($totalRecords); ?> records</span>
                    </h5>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Accession #</th>
                                <th>Title</th>
                                <th>Creator</th>
                                <th>Publisher</th>
                                <th>Date</th>
                                <th>Prefix</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($books)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No books found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($books as $book): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($book['accession_number']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="book-title">
                                                <?php echo htmlspecialchars($book['title']); ?>
                                                <?php if ($book['isbn']): ?>
                                                    <br><small class="text-muted">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($book['main_creator']); ?></td>
                                        <td><?php echo htmlspecialchars($book['publisher']); ?></td>
                                        <td><?php echo $book['date_of_publication'] ? date('Y', strtotime($book['date_of_publication'])) : '-'; ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($book['prefix']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($book['on_shelf']): ?>
                                                <span class="badge bg-success status-badge">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning status-badge">Checked Out</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewBook(<?php echo $book['id']; ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" 
                                                        onclick="editBook(<?php echo $book['id']; ?>)"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteBook(<?php echo $book['id']; ?>)"
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
                        <nav aria-label="Books pagination">
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

    <!-- Add Book Modal -->
    <div class="modal fade" id="addBookModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Book
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addBookForm" method="POST" action="api/books.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="title" name="title" required>
                                    <label for="title">Title *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="main_creator" name="main_creator">
                                    <label for="main_creator">Main Creator</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="date" class="form-control" id="date_of_publication" name="date_of_publication">
                                    <label for="date_of_publication">Publication Date</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="publisher" name="publisher">
                                    <label for="publisher">Publisher</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="isbn" name="isbn">
                                    <label for="isbn">ISBN</label>
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
                                    <input type="text" class="form-control" id="accession_number" name="accession_number" required>
                                    <label for="accession_number">Accession Number *</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="prefix" name="prefix">
                                        <option value="">Select Prefix</option>
                                        <?php foreach ($prefixOptions as $value => $label): ?>
                                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="prefix">Prefix</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="language" name="language">
                                    <label for="language">Language</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="location" name="location">
                                    <label for="location">Location</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="abstract_summary" name="abstract_summary" style="height: 100px"></textarea>
                            <label for="abstract_summary">Abstract/Summary</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function viewBook(id) {
            window.location.href = `view-book.php?id=${id}`;
        }

        function editBook(id) {
            window.location.href = `edit-book.php?id=${id}`;
        }

        function deleteBook(id) {
            if (confirm('Are you sure you want to delete this book?')) {
                fetch(`api/books.php?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting book: ' + data.message);
                    }
                });
            }
        }

        function exportBooks() {
            const params = new URLSearchParams(window.location.search);
            window.open(`api/export-books.php?${params.toString()}`, '_blank');
        }

        // Form submission
        document.getElementById('addBookForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create');

            fetch('api/books.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error adding book: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>