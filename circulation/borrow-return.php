<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
requireRoles(['admin', 'librarian']);

$action = $_GET['action'] ?? 'borrow';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

try {
    if ($action === 'borrow') {
        // Get available resources for borrowing
        $whereClause = '';
        $params = [];

        if (!empty($search)) {
            $whereClause = "WHERE (title LIKE ? OR accession_number LIKE ? OR accession LIKE ? OR identifier LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        }

        // Get books
        $booksStmt = $db->prepare("
            SELECT 'book' as type, id, title, accession_number as identifier, 'on_shelf' as status_field, on_shelf as available
            FROM books 
            WHERE on_shelf = 1
            " . ($whereClause ? "AND " . str_replace('title LIKE ? OR accession_number LIKE ? OR accession LIKE ? OR identifier LIKE ?', 
                'title LIKE ? OR accession_number LIKE ?', $whereClause) : '') . "
            ORDER BY title
            LIMIT $limit OFFSET $offset
        ");
        $booksStmt->execute($params);
        $books = $booksStmt->fetchAll();

        // Get academic coursework
        $academicStmt = $db->prepare("
            SELECT 'academic_coursework' as type, id, title, accession as identifier, 'on_shelf' as status_field, on_shelf as available
            FROM academic_coursework 
            WHERE on_shelf = 1
            " . ($whereClause ? "AND " . str_replace('title LIKE ? OR accession_number LIKE ? OR accession LIKE ? OR identifier LIKE ?', 
                'title LIKE ? OR accession LIKE ?', $whereClause) : '') . "
            ORDER BY title
            LIMIT $limit OFFSET $offset
        ");
        $academicStmt->execute($params);
        $academic = $academicStmt->fetchAll();

        $resources = array_merge($books, $academic);
        $totalRecords = count($resources);

    } else {
        // Get active circulations for return
        $whereClause = '';
        $params = [];

        if (!empty($search)) {
            $whereClause = "WHERE (p.name LIKE ? OR p.id_number LIKE ? OR c.resource_id LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }

        $circulationStmt = $db->prepare("
            SELECT c.*, p.name as patron_name, p.id_number, p.email, p.contact_number,
                   CASE 
                       WHEN c.resource_type = 'book' THEN b.title
                       WHEN c.resource_type = 'academic_coursework' THEN ac.title
                   END as resource_title,
                   CASE 
                       WHEN c.resource_type = 'book' THEN b.accession_number
                       WHEN c.resource_type = 'academic_coursework' THEN ac.accession
                   END as resource_identifier
            FROM circulation c
            JOIN patrons p ON c.patron_id = p.id
            LEFT JOIN books b ON c.resource_type = 'book' AND c.resource_id = b.id
            LEFT JOIN academic_coursework ac ON c.resource_type = 'academic_coursework' AND c.resource_id = ac.id
            WHERE c.status = 'borrowed'
            " . ($whereClause ? "AND " . $whereClause : '') . "
            ORDER BY c.due_date ASC
            LIMIT $limit OFFSET $offset
        ");
        $circulationStmt->execute($params);
        $circulations = $circulationStmt->fetchAll();

        // Get total count
        $countStmt = $db->prepare("
            SELECT COUNT(*) as total
            FROM circulation c
            JOIN patrons p ON c.patron_id = p.id
            WHERE c.status = 'borrowed'
            " . ($whereClause ? "AND " . $whereClause : '')
        );
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetch()['total'];
    }

    $totalPages = ceil($totalRecords / $limit);

} catch (PDOException $e) {
    $resources = [];
    $circulations = [];
    $totalPages = 0;
    $totalRecords = 0;
}

// Get system settings for borrowing
try {
    $settingsStmt = $db->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('max_borrow_days', 'fine_per_day')");
    $settingsStmt->execute();
    $settings = [];
    while ($row = $settingsStmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $maxBorrowDays = (int)($settings['max_borrow_days'] ?? 14);
    $finePerDay = (float)($settings['fine_per_day'] ?? 5.00);
} catch (PDOException $e) {
    $maxBorrowDays = 14;
    $finePerDay = 5.00;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow-Return - ASC Library Management System</title>
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
        
        .search-filters {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .overdue {
            background-color: rgba(220, 53, 69, 0.1);
        }

        .due-soon {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .action-tabs {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .nav-pills .nav-link {
            border-radius: 10px;
            margin: 0 0.25rem;
        }

        .nav-pills .nav-link.active {
            background-color: var(--primary-blue);
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
                                <i class="fas fa-exchange-alt me-2"></i>Borrow-Return Management
                            </h1>
                            <p class="page-subtitle">Process library resource borrowing and returns</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Tabs -->
            <div class="action-tabs">
                <ul class="nav nav-pills nav-fill p-3">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $action === 'borrow' ? 'active' : ''; ?>" 
                           href="?action=borrow">
                            <i class="fas fa-book-reader me-2"></i>Borrow Resources
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $action === 'return' ? 'active' : ''; ?>" 
                           href="?action=return">
                            <i class="fas fa-undo me-2"></i>Return Resources
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Search and Filters -->
            <div class="search-filters">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    <div class="col-md-8">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                            <label for="search">
                                <i class="fas fa-search me-2"></i>
                                <?php echo $action === 'borrow' ? 'Search available resources...' : 'Search patron or resource...'; ?>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="?action=<?php echo $action; ?>" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times me-2"></i>Clear
                        </a>
                    </div>
                </form>
            </div>

            <?php if ($action === 'borrow'): ?>
                <!-- Borrow Resources -->
                <div class="table-container">
                    <div class="table-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Available Resources for Borrowing
                            <span class="badge bg-light text-dark ms-2"><?php echo number_format($totalRecords); ?> available</span>
                        </h5>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Title</th>
                                    <th>Identifier</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($resources)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No available resources found.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($resources as $resource): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst(str_replace('_', ' ', $resource['type'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($resource['title']); ?></strong>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($resource['identifier']); ?></code>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">Available</span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" 
                                                        onclick="borrowResource('<?php echo $resource['type']; ?>', <?php echo $resource['id']; ?>)">
                                                    <i class="fas fa-hand-holding me-1"></i>Borrow
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php else: ?>
                <!-- Return Resources -->
                <div class="table-container">
                    <div class="table-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Active Borrowings
                            <span class="badge bg-light text-dark ms-2"><?php echo number_format($totalRecords); ?> active</span>
                        </h5>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Patron</th>
                                    <th>Resource</th>
                                    <th>Borrow Date</th>
                                    <th>Due Date</th>
                                    <th>Days Overdue</th>
                                    <th>Fine</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($circulations)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No active borrowings found.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($circulations as $circulation): ?>
                                        <?php
                                        $dueDate = new DateTime($circulation['due_date']);
                                        $today = new DateTime();
                                        $daysOverdue = $today->diff($dueDate)->days;
                                        $isOverdue = $today > $dueDate;
                                        $isDueSoon = $daysOverdue <= 2 && !$isOverdue;
                                        $fine = $isOverdue ? $daysOverdue * $finePerDay : 0;
                                        ?>
                                        <tr class="<?php echo $isOverdue ? 'overdue' : ($isDueSoon ? 'due-soon' : ''); ?>">
                                            <td>
                                                <div class="patron-info">
                                                    <strong><?php echo htmlspecialchars($circulation['patron_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($circulation['id_number']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="resource-info">
                                                    <strong><?php echo htmlspecialchars($circulation['resource_title']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($circulation['resource_identifier']); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($circulation['borrow_date'])); ?></td>
                                            <td>
                                                <strong><?php echo date('M j, Y', strtotime($circulation['due_date'])); ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($isOverdue): ?>
                                                    <span class="badge bg-danger"><?php echo $daysOverdue; ?> days</span>
                                                <?php elseif ($isDueSoon): ?>
                                                    <span class="badge bg-warning"><?php echo $daysOverdue; ?> days</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><?php echo $daysOverdue; ?> days</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($fine > 0): ?>
                                                    <span class="text-danger fw-bold">$<?php echo number_format($fine, 2); ?></span>
                                                <?php else: ?>
                                                    <span class="text-success">$0.00</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-success" 
                                                        onclick="returnResource(<?php echo $circulation['id']; ?>, <?php echo $fine; ?>)">
                                                    <i class="fas fa-undo me-1"></i>Return
                                                </button>
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
                            <nav aria-label="Circulation pagination">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?action=return&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?action=return&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?action=return&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Borrow Modal -->
    <div class="modal fade" id="borrowModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-hand-holding me-2"></i>Borrow Resource
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="borrowForm">
                    <div class="modal-body">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="patronSearch" placeholder="Search patron...">
                            <label for="patronSearch">Search Patron (Name or ID Number)</label>
                        </div>
                        <div id="patronResults" class="mb-3"></div>
                        <input type="hidden" id="selectedPatronId" name="patron_id">
                        <input type="hidden" id="resourceType" name="resource_type">
                        <input type="hidden" id="resourceId" name="resource_id">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Maximum borrowing period: <strong><?php echo $maxBorrowDays; ?> days</strong>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-hand-holding me-2"></i>Process Borrow
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Return Modal -->
    <div class="modal fade" id="returnModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-undo me-2"></i>Return Resource
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="returnForm">
                    <div class="modal-body">
                        <input type="hidden" id="returnCirculationId" name="circulation_id">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="returnInfo"></span>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" id="fineAmount" name="fine_amount" step="0.01" min="0">
                            <label for="fineAmount">Fine Amount (if any)</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" id="paymentAmount" name="payment_amount" step="0.01" min="0">
                            <label for="paymentAmount">Payment Amount</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="receiptNumber" name="receipt_number">
                            <label for="receiptNumber">Receipt Number</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-undo me-2"></i>Process Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function borrowResource(type, id) {
            document.getElementById('resourceType').value = type;
            document.getElementById('resourceId').value = id;
            const modal = new bootstrap.Modal(document.getElementById('borrowModal'));
            modal.show();
        }

        function returnResource(circulationId, fine) {
            document.getElementById('returnCirculationId').value = circulationId;
            document.getElementById('fineAmount').value = fine;
            document.getElementById('returnInfo').textContent = `Fine amount: $${fine.toFixed(2)}`;
            const modal = new bootstrap.Modal(document.getElementById('returnModal'));
            modal.show();
        }

        // Patron search functionality
        let searchTimeout;
        document.getElementById('patronSearch').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                document.getElementById('patronResults').innerHTML = '';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                fetch(`api/search-patrons.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayPatronResults(data.patrons);
                        }
                    });
            }, 300);
        });

        function displayPatronResults(patrons) {
            const resultsDiv = document.getElementById('patronResults');
            
            if (patrons.length === 0) {
                resultsDiv.innerHTML = '<div class="alert alert-warning">No patrons found.</div>';
                return;
            }
            
            let html = '<div class="list-group">';
            patrons.forEach(patron => {
                html += `
                    <button type="button" class="list-group-item list-group-item-action" 
                            onclick="selectPatron(${patron.id}, '${patron.name}', '${patron.id_number}')">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">${patron.name}</h6>
                            <small>${patron.id_number}</small>
                        </div>
                        <p class="mb-1">${patron.course_department || ''}</p>
                        <small>${patron.email || ''}</small>
                    </button>
                `;
            });
            html += '</div>';
            
            resultsDiv.innerHTML = html;
        }

        function selectPatron(id, name, idNumber) {
            document.getElementById('selectedPatronId').value = id;
            document.getElementById('patronSearch').value = `${name} (${idNumber})`;
            document.getElementById('patronResults').innerHTML = '';
        }

        // Form submissions
        document.getElementById('borrowForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'borrow');

            fetch('api/circulation.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error processing borrow: ' + data.message);
                }
            });
        });

        document.getElementById('returnForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'return');

            fetch('api/circulation.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error processing return: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>