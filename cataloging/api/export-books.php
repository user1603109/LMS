<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../../index.php');
    exit();
}

requireRoles(['admin', 'librarian']);

$format = $_GET['format'] ?? 'csv';
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

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

    // Get books
    $stmt = $db->prepare("
        SELECT 
            accession_number,
            title,
            main_creator,
            date_of_publication,
            publisher,
            isbn,
            call_number,
            language,
            location,
            prefix,
            abstract_summary,
            on_shelf,
            date_entered
        FROM books 
        $whereClause 
        ORDER BY date_entered DESC
    ");
    $stmt->execute($params);
    $books = $stmt->fetchAll();

    switch ($format) {
        case 'csv':
            exportCSV($books);
            break;
        case 'excel':
            exportExcel($books);
            break;
        case 'pdf':
            exportPDF($books);
            break;
        default:
            exportCSV($books);
    }

} catch (PDOException $e) {
    header('Location: ../books.php?error=export_failed');
    exit();
}

function exportCSV($books) {
    $filename = 'books_export_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'Accession Number',
        'Title',
        'Main Creator',
        'Publication Date',
        'Publisher',
        'ISBN',
        'Call Number',
        'Language',
        'Location',
        'Prefix',
        'Abstract/Summary',
        'Status',
        'Date Entered'
    ]);
    
    // CSV data
    foreach ($books as $book) {
        fputcsv($output, [
            $book['accession_number'],
            $book['title'],
            $book['main_creator'],
            $book['date_of_publication'],
            $book['publisher'],
            $book['isbn'],
            $book['call_number'],
            $book['language'],
            $book['location'],
            $book['prefix'],
            $book['abstract_summary'],
            $book['on_shelf'] ? 'Available' : 'Checked Out',
            $book['date_entered']
        ]);
    }
    
    fclose($output);
}

function exportExcel($books) {
    $filename = 'books_export_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    // Simple Excel export using CSV with Excel MIME type
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Excel headers
    fputcsv($output, [
        'Accession Number',
        'Title',
        'Main Creator',
        'Publication Date',
        'Publisher',
        'ISBN',
        'Call Number',
        'Language',
        'Location',
        'Prefix',
        'Abstract/Summary',
        'Status',
        'Date Entered'
    ]);
    
    // Excel data
    foreach ($books as $book) {
        fputcsv($output, [
            $book['accession_number'],
            $book['title'],
            $book['main_creator'],
            $book['date_of_publication'],
            $book['publisher'],
            $book['isbn'],
            $book['call_number'],
            $book['language'],
            $book['location'],
            $book['prefix'],
            $book['abstract_summary'],
            $book['on_shelf'] ? 'Available' : 'Checked Out',
            $book['date_entered']
        ]);
    }
    
    fclose($output);
}

function exportPDF($books) {
    require_once '../../vendor/autoload.php'; // Assuming TCPDF or similar library
    
    $filename = 'books_export_' . date('Y-m-d_H-i-s') . '.pdf';
    
    // Create PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('ASC Library Management System');
    $pdf->SetAuthor('ASC Library');
    $pdf->SetTitle('Books Export');
    $pdf->SetSubject('Library Books Catalog');
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 15, 'ASC Library - Books Catalog', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Set font for table
    $pdf->SetFont('helvetica', '', 8);
    
    // Table headers
    $headers = [
        'Accession #',
        'Title',
        'Creator',
        'Publisher',
        'Date',
        'Status'
    ];
    
    $colWidths = [25, 60, 30, 30, 15, 20];
    
    // Print headers
    foreach ($headers as $i => $header) {
        $pdf->Cell($colWidths[$i], 7, $header, 1, 0, 'C');
    }
    $pdf->Ln();
    
    // Print data
    foreach ($books as $book) {
        $pdf->Cell($colWidths[0], 6, $book['accession_number'], 1, 0, 'L');
        $pdf->Cell($colWidths[1], 6, substr($book['title'], 0, 40), 1, 0, 'L');
        $pdf->Cell($colWidths[2], 6, substr($book['main_creator'], 0, 20), 1, 0, 'L');
        $pdf->Cell($colWidths[3], 6, substr($book['publisher'], 0, 20), 1, 0, 'L');
        $pdf->Cell($colWidths[4], 6, $book['date_of_publication'] ? date('Y', strtotime($book['date_of_publication'])) : '', 1, 0, 'C');
        $pdf->Cell($colWidths[5], 6, $book['on_shelf'] ? 'Available' : 'Out', 1, 0, 'C');
        $pdf->Ln();
    }
    
    // Output PDF
    $pdf->Output($filename, 'D');
}
?>