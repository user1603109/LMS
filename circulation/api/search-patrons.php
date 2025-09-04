<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

requireRoles(['admin', 'librarian']);

$query = sanitizeInput($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'patrons' => []]);
    exit();
}

try {
    $searchTerm = '%' . $query . '%';
    
    $stmt = $db->prepare("
        SELECT id, name, id_number, email, course_department, status
        FROM patrons 
        WHERE (name LIKE ? OR id_number LIKE ? OR email LIKE ?) 
        AND status = 'active'
        ORDER BY name
        LIMIT 10
    ");
    
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $patrons = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'patrons' => $patrons]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>