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
    $requiredFields = ['title', 'accession'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }

    // Check if accession number already exists
    $checkStmt = $db->prepare("SELECT id FROM academic_coursework WHERE accession = ?");
    $checkStmt->execute([sanitizeInput($_POST['accession'])]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Accession number already exists']);
        return;
    }

    // Prepare data
    $data = [
        'title' => sanitizeInput($_POST['title']),
        'creator' => sanitizeInput($_POST['creator'] ?? ''),
        'institution' => sanitizeInput($_POST['institution'] ?? ''),
        'program_course' => sanitizeInput($_POST['program_course'] ?? ''),
        'date_year' => $_POST['date_year'] ?: null,
        'call_number' => sanitizeInput($_POST['call_number'] ?? ''),
        'accession' => sanitizeInput($_POST['accession']),
        'language' => sanitizeInput($_POST['language'] ?? ''),
        'location' => sanitizeInput($_POST['location'] ?? ''),
        'type_of_research_study' => sanitizeInput($_POST['type_of_research_study'] ?? ''),
        'abstract' => sanitizeInput($_POST['abstract'] ?? ''),
        'entered_by' => $_SESSION['name'],
        'on_shelf' => 1,
        'out' => 0
    ];

    // Insert academic coursework
    $sql = "INSERT INTO academic_coursework (title, creator, institution, program_course, date_year, 
            call_number, accession, language, location, type_of_research_study, abstract, 
            entered_by, on_shelf, out) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $success = $stmt->execute([
        $data['title'], $data['creator'], $data['institution'], $data['program_course'], 
        $data['date_year'], $data['call_number'], $data['accession'], $data['language'], 
        $data['location'], $data['type_of_research_study'], $data['abstract'], 
        $data['entered_by'], $data['on_shelf'], $data['out']
    ]);

    if ($success) {
        $itemId = $db->lastInsertId();
        
        // Log the action
        logAudit('CREATE', 'academic_coursework', $itemId, null, $data);
        
        echo json_encode(['success' => true, 'message' => 'Academic coursework added successfully', 'id' => $itemId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add academic coursework']);
    }
}

function handleUpdate() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Item ID is required']);
        return;
    }

    // Get current data for audit log
    $currentStmt = $db->prepare("SELECT * FROM academic_coursework WHERE id = ?");
    $currentStmt->execute([$id]);
    $currentData = $currentStmt->fetch();
    
    if (!$currentData) {
        echo json_encode(['success' => false, 'message' => 'Academic coursework not found']);
        return;
    }

    // Prepare update data
    $data = [
        'title' => sanitizeInput($input['title'] ?? $currentData['title']),
        'creator' => sanitizeInput($input['creator'] ?? $currentData['creator']),
        'institution' => sanitizeInput($input['institution'] ?? $currentData['institution']),
        'program_course' => sanitizeInput($input['program_course'] ?? $currentData['program_course']),
        'date_year' => $input['date_year'] ?? $currentData['date_year'],
        'call_number' => sanitizeInput($input['call_number'] ?? $currentData['call_number']),
        'accession' => sanitizeInput($input['accession'] ?? $currentData['accession']),
        'language' => sanitizeInput($input['language'] ?? $currentData['language']),
        'location' => sanitizeInput($input['location'] ?? $currentData['location']),
        'type_of_research_study' => sanitizeInput($input['type_of_research_study'] ?? $currentData['type_of_research_study']),
        'abstract' => sanitizeInput($input['abstract'] ?? $currentData['abstract']),
        'updated_by' => $_SESSION['name']
    ];

    // Check if accession number already exists (excluding current record)
    if ($data['accession'] !== $currentData['accession']) {
        $checkStmt = $db->prepare("SELECT id FROM academic_coursework WHERE accession = ? AND id != ?");
        $checkStmt->execute([$data['accession'], $id]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Accession number already exists']);
            return;
        }
    }

    // Update academic coursework
    $sql = "UPDATE academic_coursework SET title = ?, creator = ?, institution = ?, program_course = ?, 
            date_year = ?, call_number = ?, accession = ?, language = ?, location = ?, 
            type_of_research_study = ?, abstract = ?, updated_by = ?, date_updated = NOW() 
            WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    $success = $stmt->execute([
        $data['title'], $data['creator'], $data['institution'], $data['program_course'], 
        $data['date_year'], $data['call_number'], $data['accession'], $data['language'], 
        $data['location'], $data['type_of_research_study'], $data['abstract'], 
        $data['updated_by'], $id
    ]);

    if ($success) {
        // Log the action
        logAudit('UPDATE', 'academic_coursework', $id, $currentData, $data);
        
        echo json_encode(['success' => true, 'message' => 'Academic coursework updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update academic coursework']);
    }
}

function handleDelete() {
    global $db;
    
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Item ID is required']);
        return;
    }

    // Get current data for audit log
    $currentStmt = $db->prepare("SELECT * FROM academic_coursework WHERE id = ?");
    $currentStmt->execute([$id]);
    $currentData = $currentStmt->fetch();
    
    if (!$currentData) {
        echo json_encode(['success' => false, 'message' => 'Academic coursework not found']);
        return;
    }

    // Check if item is currently borrowed
    $circulationStmt = $db->prepare("SELECT COUNT(*) as count FROM circulation WHERE resource_type = 'academic_coursework' AND resource_id = ? AND status = 'borrowed'");
    $circulationStmt->execute([$id]);
    $borrowedCount = $circulationStmt->fetch()['count'];
    
    if ($borrowedCount > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete item that is currently borrowed']);
        return;
    }

    // Delete academic coursework
    $stmt = $db->prepare("DELETE FROM academic_coursework WHERE id = ?");
    $success = $stmt->execute([$id]);

    if ($success) {
        // Log the action
        logAudit('DELETE', 'academic_coursework', $id, $currentData, null);
        
        echo json_encode(['success' => true, 'message' => 'Academic coursework deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete academic coursework']);
    }
}

function handleGet() {
    global $db;
    
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Item ID is required']);
        return;
    }

    $stmt = $db->prepare("SELECT * FROM academic_coursework WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    
    if ($item) {
        echo json_encode(['success' => true, 'item' => $item]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Academic coursework not found']);
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