<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$query = sanitizeInput($input['query'] ?? '');
$type = sanitizeInput($input['type'] ?? 'global');

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Search query is required']);
    exit();
}

try {
    $results = [];
    $searchTerm = '%' . $query . '%';

    // Search books
    $booksStmt = $db->prepare("
        SELECT 
            'book' as type,
            title,
            main_creator as creator,
            date_entered as date,
            CONCAT(LEFT(abstract_summary, 100), '...') as description,
            CONCAT('cataloging/books.php?id=', id) as url
        FROM books 
        WHERE title LIKE ? OR main_creator LIKE ? OR isbn LIKE ?
        ORDER BY date_entered DESC
        LIMIT 5
    ");
    $booksStmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $books = $booksStmt->fetchAll();

    // Search academic coursework
    $academicStmt = $db->prepare("
        SELECT 
            'academic_coursework' as type,
            title,
            creator,
            date_entered as date,
            CONCAT(LEFT(abstract, 100), '...') as description,
            CONCAT('cataloging/academic-coursework.php?id=', id) as url
        FROM academic_coursework 
        WHERE title LIKE ? OR creator LIKE ? OR institution LIKE ?
        ORDER BY date_entered DESC
        LIMIT 5
    ");
    $academicStmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $academic = $academicStmt->fetchAll();

    // Search electronic resources
    $electronicStmt = $db->prepare("
        SELECT 
            'electronic_resource' as type,
            title,
            creator,
            date_entered as date,
            CONCAT(LEFT(description, 100), '...') as description,
            CONCAT('cataloging/electronic-resources.php?id=', id) as url
        FROM electronic_resources 
        WHERE title LIKE ? OR creator LIKE ? OR publisher LIKE ?
        ORDER BY date_entered DESC
        LIMIT 5
    ");
    $electronicStmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $electronic = $electronicStmt->fetchAll();

    // Search patrons
    $patronsStmt = $db->prepare("
        SELECT 
            'patron' as type,
            name as title,
            id_number as creator,
            date_entered as date,
            CONCAT(course_department, ' - ', year_level) as description,
            CONCAT('patrons/view.php?id=', id) as url
        FROM patrons 
        WHERE name LIKE ? OR id_number LIKE ? OR email LIKE ?
        ORDER BY date_entered DESC
        LIMIT 5
    ");
    $patronsStmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $patrons = $patronsStmt->fetchAll();

    // Combine results
    $results = array_merge($books, $academic, $electronic, $patrons);

    // Sort by date
    usort($results, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    // Limit total results
    $results = array_slice($results, 0, 20);

    echo json_encode([
        'success' => true,
        'results' => $results,
        'total' => count($results)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>