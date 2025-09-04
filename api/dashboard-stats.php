<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Get all statistics
    $stats = [];

    // Books count
    $booksStmt = $db->prepare("SELECT COUNT(*) as count FROM books");
    $booksStmt->execute();
    $stats['books'] = $booksStmt->fetch()['count'];

    // Patrons count
    $patronsStmt = $db->prepare("SELECT COUNT(*) as count FROM patrons WHERE status = 'active'");
    $patronsStmt->execute();
    $stats['patrons'] = $patronsStmt->fetch()['count'];

    // Active circulations
    $circulationStmt = $db->prepare("SELECT COUNT(*) as count FROM circulation WHERE status = 'borrowed'");
    $circulationStmt->execute();
    $stats['circulations'] = $circulationStmt->fetch()['count'];

    // Overdue items
    $overdueStmt = $db->prepare("SELECT COUNT(*) as count FROM circulation WHERE status = 'borrowed' AND due_date < CURDATE()");
    $overdueStmt->execute();
    $stats['overdue'] = $overdueStmt->fetch()['count'];

    // Academic coursework count
    $academicStmt = $db->prepare("SELECT COUNT(*) as count FROM academic_coursework");
    $academicStmt->execute();
    $stats['academic'] = $academicStmt->fetch()['count'];

    // Electronic resources count
    $electronicStmt = $db->prepare("SELECT COUNT(*) as count FROM electronic_resources");
    $electronicStmt->execute();
    $stats['electronic'] = $electronicStmt->fetch()['count'];

    // Pending reservations
    $reservationsStmt = $db->prepare("SELECT COUNT(*) as count FROM reservations WHERE status = 'pending'");
    $reservationsStmt->execute();
    $stats['reservations'] = $reservationsStmt->fetch()['count'];

    // Total fines
    $finesStmt = $db->prepare("SELECT SUM(fine) as total FROM circulation WHERE status = 'borrowed' AND due_date < CURDATE()");
    $finesStmt->execute();
    $stats['total_fines'] = $finesStmt->fetch()['total'] ?? 0;

    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>