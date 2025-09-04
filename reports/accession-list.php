<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
requireRoles(['admin', 'librarian']);

$format = $_GET['format'] ?? '';
$resourceType = $_GET['resource_type'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

try {
    // Build query based on filters
    $whereClause = '';
    $params = [];
    
    if ($resourceType !== 'all') {
        $whereClause = "WHERE resource_type = ?";
        $params[] = $resourceType;
    }
    
    if ($dateFrom) {
        $whereClause .= empty($whereClause) ? 'WHERE ' : ' AND ';
        $whereClause .= "date_entered >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $whereClause .= empty($whereClause) ? 'WHERE ' : ' AND ';
        $whereClause .= "date_entered <= ?";
        $params[] = $dateTo . ' 23:59:59';
    }

    // Get books
    $booksStmt = $db->prepare("
        SELECT 
            'book' as resource_type,
            id,
            title,
            main_creator as creator,
            accession_number as identifier,
            date_of_publication as date,
            publisher,
            isbn,
            prefix,
            on_shelf,
            date_entered
        FROM books
        " . ($resourceType === 'all' || $resourceType === 'book' ? $whereClause : 'WHERE 1=0') . "
        ORDER BY date_entered DESC
    ");
    $booksStmt->execute($params);
    $books = $booksStmt->fetchAll();

    // Get academic coursework
    $academicStmt = $db->prepare("
        SELECT 
            'academic_coursework' as resource_type,
            id,
            title,
            creator,
            accession as identifier,
            date_year as date,
            institution as publisher,
            '' as isbn,
            '' as prefix,
            on_shelf,
            date_entered
        FROM academic_coursework
        " . ($resourceType === 'all' || $resourceType === 'academic_coursework' ? $whereClause : 'WHERE 1=0') . "
        ORDER BY date_entered DESC
    ");
    $academicStmt->execute($params);
    $academic = $academicStmt->fetchAll();

    // Get electronic resources
    $electronicStmt = $db->prepare("
        SELECT 
            'electronic_resource' as resource_type,
            id,
            title,
            creator,
            identifier,
            date,
            publisher,
            '' as isbn,
            '' as prefix,
            1 as on_shelf,
            date_entered
        FROM electronic_resources
        " . ($resourceType === 'all' || $resourceType === 'electronic_resource' ? $whereClause : 'WHERE 1=0') . "
        ORDER BY date_entered DESC
    ");
    $electronicStmt->execute($params);
    $electronic = $electronicStmt->fetchAll();

    // Combine all resources
    $allResources = array_merge($books, $academic, $electronic);
    
    // Sort by date entered
    usort($allResources, function($a, $b) {
        return strtotime($b['date_entered']) - strtotime($a['date_entered']);
    });

    // Handle export
    if ($format && in_array($format, ['csv', 'excel', 'pdf'])) {
        exportAccessionList($allResources, $format, $resourceType, $dateFrom, $dateTo);
        exit();
    }

} catch (PDOException $e) {
    $allResources = [];
}

$resourceTypes = [
    'all' => 'All Resources',
    'book' => 'Books',
    'academic_coursework' => 'Academic Coursework',
    'electronic_resource' => 'Electronic Resources'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accession List - ASC Library Management System</title>
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
        
        .search-filters {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .export-buttons {
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
                                <i class="fas fa-list me-2"></i>Accession List Report
                            </h1>
                            <p class="page-subtitle">Generate and export library resource accession reports</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Buttons -->
            <div class="export-buttons">
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="mb-3">
                            <i class="fas fa-download me-2"></i>Export Options
                        </h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-success" onclick="exportReport('csv')">
                                <i class="fas fa-file-csv me-2"></i>Export CSV
                            </button>
                            <button type="button" class="btn btn-warning" onclick="exportReport('excel')">
                                <i class="fas fa-file-excel me-2"></i>Export Excel
                            </button>
                            <button type="button" class="btn btn-danger" onclick="exportReport('pdf')">
                                <i class="fas fa-file-pdf me-2"></i>Export PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="search-filters">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select class="form-select" id="resource_type" name="resource_type">
                                <?php foreach ($resourceTypes as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $resourceType === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="resource_type">Resource Type</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?php echo htmlspecialchars($dateFrom); ?>">
                            <label for="date_from">From Date</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?php echo htmlspecialchars($dateTo); ?>">
                            <label for="date_to">To Date</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Accession List Table -->
            <div class="table-container">
                <div class="table-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Accession List
                        <span class="badge bg-light text-dark ms-2"><?php echo number_format(count($allResources)); ?> items</span>
                    </h5>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Creator</th>
                                <th>Identifier</th>
                                <th>Date</th>
                                <th>Publisher</th>
                                <th>ISBN</th>
                                <th>Status</th>
                                <th>Date Entered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allResources)): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <i class="fas fa-list fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No resources found for the selected criteria.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allResources as $index => $resource): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo ucfirst(str_replace('_', ' ', $resource['resource_type'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($resource['title']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($resource['creator']); ?></td>
                                        <td>
                                            <code><?php echo htmlspecialchars($resource['identifier']); ?></code>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($resource['date']) {
                                                if ($resource['resource_type'] === 'academic_coursework') {
                                                    echo $resource['date']; // Year only
                                                } else {
                                                    echo date('Y', strtotime($resource['date']));
                                                }
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($resource['publisher']); ?></td>
                                        <td><?php echo htmlspecialchars($resource['isbn']); ?></td>
                                        <td>
                                            <?php if ($resource['on_shelf']): ?>
                                                <span class="badge bg-success">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Checked Out</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($resource['date_entered'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function exportReport(format) {
            const params = new URLSearchParams(window.location.search);
            params.set('format', format);
            window.open(`accession-list.php?${params.toString()}`, '_blank');
        }
    </script>
</body>
</html>

<?php
function exportAccessionList($resources, $format, $resourceType, $dateFrom, $dateTo) {
    $filename = 'accession_list_' . date('Y-m-d_H-i-s') . '.' . $format;
    
    switch ($format) {
        case 'csv':
            exportCSV($resources, $filename);
            break;
        case 'excel':
            exportExcel($resources, $filename);
            break;
        case 'pdf':
            exportPDF($resources, $filename);
            break;
    }
}

function exportCSV($resources, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'No.',
        'Resource Type',
        'Title',
        'Creator',
        'Identifier',
        'Date',
        'Publisher',
        'ISBN',
        'Status',
        'Date Entered'
    ]);
    
    // CSV data
    foreach ($resources as $index => $resource) {
        fputcsv($output, [
            $index + 1,
            ucfirst(str_replace('_', ' ', $resource['resource_type'])),
            $resource['title'],
            $resource['creator'],
            $resource['identifier'],
            $resource['date'] ? ($resource['resource_type'] === 'academic_coursework' ? $resource['date'] : date('Y', strtotime($resource['date']))) : '',
            $resource['publisher'],
            $resource['isbn'],
            $resource['on_shelf'] ? 'Available' : 'Checked Out',
            $resource['date_entered']
        ]);
    }
    
    fclose($output);
}

function exportExcel($resources, $filename) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Excel headers
    fputcsv($output, [
        'No.',
        'Resource Type',
        'Title',
        'Creator',
        'Identifier',
        'Date',
        'Publisher',
        'ISBN',
        'Status',
        'Date Entered'
    ]);
    
    // Excel data
    foreach ($resources as $index => $resource) {
        fputcsv($output, [
            $index + 1,
            ucfirst(str_replace('_', ' ', $resource['resource_type'])),
            $resource['title'],
            $resource['creator'],
            $resource['identifier'],
            $resource['date'] ? ($resource['resource_type'] === 'academic_coursework' ? $resource['date'] : date('Y', strtotime($resource['date']))) : '',
            $resource['publisher'],
            $resource['isbn'],
            $resource['on_shelf'] ? 'Available' : 'Checked Out',
            $resource['date_entered']
        ]);
    }
    
    fclose($output);
}

function exportPDF($resources, $filename) {
    // Simple PDF export using basic HTML to PDF conversion
    // In a real application, you would use a proper PDF library like TCPDF or FPDF
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $html = '<html><head><title>Accession List</title></head><body>';
    $html .= '<h1>ASC Library - Accession List</h1>';
    $html .= '<p>Generated on: ' . date('F j, Y g:i A') . '</p>';
    $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">';
    $html .= '<tr style="background-color:#f0f0f0;">';
    $html .= '<th>No.</th><th>Type</th><th>Title</th><th>Creator</th><th>Identifier</th><th>Date</th><th>Publisher</th><th>Status</th>';
    $html .= '</tr>';
    
    foreach ($resources as $index => $resource) {
        $html .= '<tr>';
        $html .= '<td>' . ($index + 1) . '</td>';
        $html .= '<td>' . ucfirst(str_replace('_', ' ', $resource['resource_type'])) . '</td>';
        $html .= '<td>' . htmlspecialchars($resource['title']) . '</td>';
        $html .= '<td>' . htmlspecialchars($resource['creator']) . '</td>';
        $html .= '<td>' . htmlspecialchars($resource['identifier']) . '</td>';
        $html .= '<td>' . ($resource['date'] ? ($resource['resource_type'] === 'academic_coursework' ? $resource['date'] : date('Y', strtotime($resource['date']))) : '') . '</td>';
        $html .= '<td>' . htmlspecialchars($resource['publisher']) . '</td>';
        $html .= '<td>' . ($resource['on_shelf'] ? 'Available' : 'Checked Out') . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table></body></html>';
    
    // For demonstration, we'll output HTML. In production, use a proper PDF library
    echo $html;
}
?>