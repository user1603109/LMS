<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();
requireRoles(['admin', 'librarian']);

$format = $_GET['format'] ?? '';
$status = $_GET['status'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

try {
    // Build query based on filters
    $whereClause = '';
    $params = [];
    
    if ($status !== 'all') {
        $whereClause = "WHERE status = ?";
        $params[] = $status;
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

    // Get patrons with additional statistics
    $patronsStmt = $db->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM circulation WHERE patron_id = p.id AND status = 'borrowed') as active_borrows,
               (SELECT COUNT(*) FROM circulation WHERE patron_id = p.id) as total_borrows,
               (SELECT SUM(fine) FROM circulation WHERE patron_id = p.id AND status = 'borrowed' AND due_date < CURDATE()) as outstanding_fines,
               (SELECT SUM(payment) FROM circulation WHERE patron_id = p.id) as total_payments
        FROM patrons p 
        $whereClause 
        ORDER BY date_entered DESC
    ");
    $patronsStmt->execute($params);
    $patrons = $patronsStmt->fetchAll();

    // Handle export
    if ($format && in_array($format, ['csv', 'excel', 'pdf'])) {
        exportPatronMasterlist($patrons, $format, $status, $dateFrom, $dateTo);
        exit();
    }

} catch (PDOException $e) {
    $patrons = [];
}

$statusOptions = [
    'all' => 'All Status',
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
    <title>Patron Masterlist - ASC Library Management System</title>
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
                                <i class="fas fa-users me-2"></i>Patron Masterlist Report
                            </h1>
                            <p class="page-subtitle">Generate and export patron directory reports</p>
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
                            <select class="form-select" id="status" name="status">
                                <?php foreach ($statusOptions as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $status === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="status">Status</label>
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

            <!-- Patron Masterlist Table -->
            <div class="table-container">
                <div class="table-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Patron Masterlist
                        <span class="badge bg-light text-dark ms-2"><?php echo number_format(count($patrons)); ?> patrons</span>
                    </h5>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Avatar</th>
                                <th>Name</th>
                                <th>ID Number</th>
                                <th>Gender</th>
                                <th>Course/Department</th>
                                <th>Year Level</th>
                                <th>Contact</th>
                                <th>Active Borrows</th>
                                <th>Total Borrows</th>
                                <th>Outstanding Fines</th>
                                <th>Total Payments</th>
                                <th>Status</th>
                                <th>Date Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($patrons)): ?>
                                <tr>
                                    <td colspan="14" class="text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No patrons found for the selected criteria.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($patrons as $index => $patron): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <div class="patron-avatar">
                                                <?php echo strtoupper(substr($patron['name'], 0, 2)); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($patron['name']); ?></strong>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($patron['id_number']); ?></code>
                                        </td>
                                        <td><?php echo ucfirst($patron['gender']); ?></td>
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
                                            <span class="badge bg-secondary"><?php echo $patron['total_borrows']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($patron['outstanding_fines'] > 0): ?>
                                                <span class="fine-amount">$<?php echo number_format($patron['outstanding_fines'], 2); ?></span>
                                            <?php else: ?>
                                                <span class="text-success">$0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="text-success">$<?php echo number_format($patron['total_payments'], 2); ?></span>
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
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($patron['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($patron['date_entered'])); ?></td>
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
            window.open(`patron-masterlist.php?${params.toString()}`, '_blank');
        }
    </script>
</body>
</html>

<?php
function exportPatronMasterlist($patrons, $format, $status, $dateFrom, $dateTo) {
    $filename = 'patron_masterlist_' . date('Y-m-d_H-i-s') . '.' . $format;
    
    switch ($format) {
        case 'csv':
            exportCSV($patrons, $filename);
            break;
        case 'excel':
            exportExcel($patrons, $filename);
            break;
        case 'pdf':
            exportPDF($patrons, $filename);
            break;
    }
}

function exportCSV($patrons, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'No.',
        'Name',
        'ID Number',
        'Gender',
        'Group',
        'Course/Department',
        'Year Level',
        'Email',
        'Contact Number',
        'Address',
        'Active Borrows',
        'Total Borrows',
        'Outstanding Fines',
        'Total Payments',
        'Status',
        'Date Registered'
    ]);
    
    // CSV data
    foreach ($patrons as $index => $patron) {
        fputcsv($output, [
            $index + 1,
            $patron['name'],
            $patron['id_number'],
            $patron['gender'],
            $patron['group'],
            $patron['course_department'],
            $patron['year_level'],
            $patron['email'],
            $patron['contact_number'],
            $patron['address'],
            $patron['active_borrows'],
            $patron['total_borrows'],
            $patron['outstanding_fines'],
            $patron['total_payments'],
            ucfirst($patron['status']),
            $patron['date_entered']
        ]);
    }
    
    fclose($output);
}

function exportExcel($patrons, $filename) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Excel headers
    fputcsv($output, [
        'No.',
        'Name',
        'ID Number',
        'Gender',
        'Group',
        'Course/Department',
        'Year Level',
        'Email',
        'Contact Number',
        'Address',
        'Active Borrows',
        'Total Borrows',
        'Outstanding Fines',
        'Total Payments',
        'Status',
        'Date Registered'
    ]);
    
    // Excel data
    foreach ($patrons as $index => $patron) {
        fputcsv($output, [
            $index + 1,
            $patron['name'],
            $patron['id_number'],
            $patron['gender'],
            $patron['group'],
            $patron['course_department'],
            $patron['year_level'],
            $patron['email'],
            $patron['contact_number'],
            $patron['address'],
            $patron['active_borrows'],
            $patron['total_borrows'],
            $patron['outstanding_fines'],
            $patron['total_payments'],
            ucfirst($patron['status']),
            $patron['date_entered']
        ]);
    }
    
    fclose($output);
}

function exportPDF($patrons, $filename) {
    // Simple PDF export using basic HTML to PDF conversion
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $html = '<html><head><title>Patron Masterlist</title></head><body>';
    $html .= '<h1>ASC Library - Patron Masterlist</h1>';
    $html .= '<p>Generated on: ' . date('F j, Y g:i A') . '</p>';
    $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:10px;">';
    $html .= '<tr style="background-color:#f0f0f0;">';
    $html .= '<th>No.</th><th>Name</th><th>ID Number</th><th>Gender</th><th>Course/Department</th><th>Email</th><th>Active Borrows</th><th>Status</th>';
    $html .= '</tr>';
    
    foreach ($patrons as $index => $patron) {
        $html .= '<tr>';
        $html .= '<td>' . ($index + 1) . '</td>';
        $html .= '<td>' . htmlspecialchars($patron['name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($patron['id_number']) . '</td>';
        $html .= '<td>' . htmlspecialchars($patron['gender']) . '</td>';
        $html .= '<td>' . htmlspecialchars($patron['course_department']) . '</td>';
        $html .= '<td>' . htmlspecialchars($patron['email']) . '</td>';
        $html .= '<td>' . $patron['active_borrows'] . '</td>';
        $html .= '<td>' . ucfirst($patron['status']) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table></body></html>';
    
    // For demonstration, we'll output HTML. In production, use a proper PDF library
    echo $html;
}
?>