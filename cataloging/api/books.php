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

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            handleCreate();
            break;
        case 'PUT':
            handleUpdate();
            break;
        case 'DELETE':
            handleDelete();
            break;
        case 'GET':
            handleGet();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleCreate() {
    global $db;
    
    $action = $_POST['action'] ?? '';
    if ($action !== 'create') {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        return;
    }

    // Validate required fields
    $requiredFields = ['title', 'accession_number'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }

    // Check if accession number already exists
    $checkStmt = $db->prepare("SELECT id FROM books WHERE accession_number = ?");
    $checkStmt->execute([sanitizeInput($_POST['accession_number'])]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Accession number already exists']);
        return;
    }

    // Prepare data
    $data = [
        'title' => sanitizeInput($_POST['title']),
        'main_creator' => sanitizeInput($_POST['main_creator'] ?? ''),
        'date_of_publication' => $_POST['date_of_publication'] ?: null,
        'publisher' => sanitizeInput($_POST['publisher'] ?? ''),
        'call_number' => sanitizeInput($_POST['call_number'] ?? ''),
        'accession_number' => sanitizeInput($_POST['accession_number']),
        'language' => sanitizeInput($_POST['language'] ?? ''),
        'location' => sanitizeInput($_POST['location'] ?? ''),
        'isbn' => sanitizeInput($_POST['isbn'] ?? ''),
        'prefix' => sanitizeInput($_POST['prefix'] ?? ''),
        'abstract_summary' => sanitizeInput($_POST['abstract_summary'] ?? ''),
        'entered_by' => $_SESSION['name'],
        'on_shelf' => 1
    ];

    // Insert book
    $sql = "INSERT INTO books (title, main_creator, date_of_publication, publisher, call_number, 
            accession_number, language, location, isbn, prefix, abstract_summary, entered_by, on_shelf) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $success = $stmt->execute([
        $data['title'], $data['main_creator'], $data['date_of_publication'], 
        $data['publisher'], $data['call_number'], $data['accession_number'],
        $data['language'], $data['location'], $data['isbn'], $data['prefix'],
        $data['abstract_summary'], $data['entered_by'], $data['on_shelf']
    ]);

    if ($success) {
        $bookId = $db->lastInsertId();
        
        // Log the action
        logAudit('CREATE', 'books', $bookId, null, $data);
        
        echo json_encode(['success' => true, 'message' => 'Book added successfully', 'id' => $bookId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add book']);
    }
}

function handleUpdate() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Book ID is required']);
        return;
    }

    // Get current data for audit log
    $currentStmt = $db->prepare("SELECT * FROM books WHERE id = ?");
    $currentStmt->execute([$id]);
    $currentData = $currentStmt->fetch();
    
    if (!$currentData) {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        return;
    }

    // Prepare update data
    $data = [
        'title' => sanitizeInput($input['title'] ?? $currentData['title']),
        'main_creator' => sanitizeInput($input['main_creator'] ?? $currentData['main_creator']),
        'date_of_publication' => $input['date_of_publication'] ?? $currentData['date_of_publication'],
        'publisher' => sanitizeInput($input['publisher'] ?? $currentData['publisher']),
        'call_number' => sanitizeInput($input['call_number'] ?? $currentData['call_number']),
        'accession_number' => sanitizeInput($input['accession_number'] ?? $currentData['accession_number']),
        'language' => sanitizeInput($input['language'] ?? $currentData['language']),
        'location' => sanitizeInput($input['location'] ?? $currentData['location']),
        'isbn' => sanitizeInput($input['isbn'] ?? $currentData['isbn']),
        'prefix' => sanitizeInput($input['prefix'] ?? $currentData['prefix']),
        'abstract_summary' => sanitizeInput($input['abstract_summary'] ?? $currentData['abstract_summary']),
        'updated_by' => $_SESSION['name']
    ];

    // Check if accession number already exists (excluding current record)
    if ($data['accession_number'] !== $currentData['accession_number']) {
        $checkStmt = $db->prepare("SELECT id FROM books WHERE accession_number = ? AND id != ?");
        $checkStmt->execute([$data['accession_number'], $id]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Accession number already exists']);
            return;
        }
    }

    // Update book
    $sql = "UPDATE books SET title = ?, main_creator = ?, date_of_publication = ?, publisher = ?, 
            call_number = ?, accession_number = ?, language = ?, location = ?, isbn = ?, 
            prefix = ?, abstract_summary = ?, updated_by = ?, date_updated = NOW() 
            WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    $success = $stmt->execute([
        $data['title'], $data['main_creator'], $data['date_of_publication'], 
        $data['publisher'], $data['call_number'], $data['accession_number'],
        $data['language'], $data['location'], $data['isbn'], $data['prefix'],
        $data['abstract_summary'], $data['updated_by'], $id
    ]);

    if ($success) {
        // Log the action
        logAudit('UPDATE', 'books', $id, $currentData, $data);
        
        echo json_encode(['success' => true, 'message' => 'Book updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update book']);
    }
}

function handleDelete() {
    global $db;
    
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Book ID is required']);
        return;
    }

    // Get current data for audit log
    $currentStmt = $db->prepare("SELECT * FROM books WHERE id = ?");
    $currentStmt->execute([$id]);
    $currentData = $currentStmt->fetch();
    
    if (!$currentData) {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        return;
    }

    // Check if book is currently borrowed
    $circulationStmt = $db->prepare("SELECT COUNT(*) as count FROM circulation WHERE resource_type = 'book' AND resource_id = ? AND status = 'borrowed'");
    $circulationStmt->execute([$id]);
    $borrowedCount = $circulationStmt->fetch()['count'];
    
    if ($borrowedCount > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete book that is currently borrowed']);
        return;
    }

    // Delete book
    $stmt = $db->prepare("DELETE FROM books WHERE id = ?");
    $success = $stmt->execute([$id]);

    if ($success) {
        // Log the action
        logAudit('DELETE', 'books', $id, $currentData, null);
        
        echo json_encode(['success' => true, 'message' => 'Book deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete book']);
    }
}

function handleGet() {
    global $db;
    
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Book ID is required']);
        return;
    }

    $stmt = $db->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$id]);
    $book = $stmt->fetch();
    
    if ($book) {
        echo json_encode(['success' => true, 'book' => $book]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
    }
}

function logAudit($action, $table, $recordId, $oldValues, $newValues) {
    global $db;
    
    $stmt = $db->prepare("
        INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $action,
        $table,
        $recordId,
        $oldValues ? json_encode($oldValues) : null,
        $newValues ? json_encode($newValues) : null,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}
?>