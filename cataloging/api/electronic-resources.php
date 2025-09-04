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
    $requiredFields = ['title'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }

    // Prepare data
    $data = [
        'title' => sanitizeInput($_POST['title']),
        'creator' => sanitizeInput($_POST['creator'] ?? ''),
        'publisher' => sanitizeInput($_POST['publisher'] ?? ''),
        'date' => $_POST['date'] ?: null,
        'identifier' => sanitizeInput($_POST['identifier'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'source' => sanitizeInput($_POST['source'] ?? ''),
        'format_language' => sanitizeInput($_POST['format_language'] ?? ''),
        'contributor' => sanitizeInput($_POST['contributor'] ?? ''),
        'electronic_access' => sanitizeInput($_POST['electronic_access'] ?? ''),
        'type_of_material' => sanitizeInput($_POST['type_of_material'] ?? ''),
        'relation' => sanitizeInput($_POST['relation'] ?? ''),
        'rights' => sanitizeInput($_POST['rights'] ?? ''),
        'coverage' => sanitizeInput($_POST['coverage'] ?? ''),
        'subjects' => sanitizeInput($_POST['subjects'] ?? ''),
        'entered_by' => $_SESSION['name']
    ];

    // Insert electronic resource
    $sql = "INSERT INTO electronic_resources (title, creator, publisher, date, identifier, description, 
            source, format_language, contributor, electronic_access, type_of_material, relation, 
            rights, coverage, subjects, entered_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $success = $stmt->execute([
        $data['title'], $data['creator'], $data['publisher'], $data['date'], $data['identifier'],
        $data['description'], $data['source'], $data['format_language'], $data['contributor'],
        $data['electronic_access'], $data['type_of_material'], $data['relation'], $data['rights'],
        $data['coverage'], $data['subjects'], $data['entered_by']
    ]);

    if ($success) {
        $resourceId = $db->lastInsertId();
        
        // Log the action
        logAudit('CREATE', 'electronic_resources', $resourceId, null, $data);
        
        echo json_encode(['success' => true, 'message' => 'Electronic resource added successfully', 'id' => $resourceId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add electronic resource']);
    }
}

function handleUpdate() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Resource ID is required']);
        return;
    }

    // Get current data for audit log
    $currentStmt = $db->prepare("SELECT * FROM electronic_resources WHERE id = ?");
    $currentStmt->execute([$id]);
    $currentData = $currentStmt->fetch();
    
    if (!$currentData) {
        echo json_encode(['success' => false, 'message' => 'Electronic resource not found']);
        return;
    }

    // Prepare update data
    $data = [
        'title' => sanitizeInput($input['title'] ?? $currentData['title']),
        'creator' => sanitizeInput($input['creator'] ?? $currentData['creator']),
        'publisher' => sanitizeInput($input['publisher'] ?? $currentData['publisher']),
        'date' => $input['date'] ?? $currentData['date'],
        'identifier' => sanitizeInput($input['identifier'] ?? $currentData['identifier']),
        'description' => sanitizeInput($input['description'] ?? $currentData['description']),
        'source' => sanitizeInput($input['source'] ?? $currentData['source']),
        'format_language' => sanitizeInput($input['format_language'] ?? $currentData['format_language']),
        'contributor' => sanitizeInput($input['contributor'] ?? $currentData['contributor']),
        'electronic_access' => sanitizeInput($input['electronic_access'] ?? $currentData['electronic_access']),
        'type_of_material' => sanitizeInput($input['type_of_material'] ?? $currentData['type_of_material']),
        'relation' => sanitizeInput($input['relation'] ?? $currentData['relation']),
        'rights' => sanitizeInput($input['rights'] ?? $currentData['rights']),
        'coverage' => sanitizeInput($input['coverage'] ?? $currentData['coverage']),
        'subjects' => sanitizeInput($input['subjects'] ?? $currentData['subjects']),
        'updated_by' => $_SESSION['name']
    ];

    // Update electronic resource
    $sql = "UPDATE electronic_resources SET title = ?, creator = ?, publisher = ?, date = ?, 
            identifier = ?, description = ?, source = ?, format_language = ?, contributor = ?, 
            electronic_access = ?, type_of_material = ?, relation = ?, rights = ?, coverage = ?, 
            subjects = ?, updated_by = ?, date_updated = NOW() 
            WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    $success = $stmt->execute([
        $data['title'], $data['creator'], $data['publisher'], $data['date'], $data['identifier'],
        $data['description'], $data['source'], $data['format_language'], $data['contributor'],
        $data['electronic_access'], $data['type_of_material'], $data['relation'], $data['rights'],
        $data['coverage'], $data['subjects'], $data['updated_by'], $id
    ]);

    if ($success) {
        // Log the action
        logAudit('UPDATE', 'electronic_resources', $id, $currentData, $data);
        
        echo json_encode(['success' => true, 'message' => 'Electronic resource updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update electronic resource']);
    }
}

function handleDelete() {
    global $db;
    
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Resource ID is required']);
        return;
    }

    // Get current data for audit log
    $currentStmt = $db->prepare("SELECT * FROM electronic_resources WHERE id = ?");
    $currentStmt->execute([$id]);
    $currentData = $currentStmt->fetch();
    
    if (!$currentData) {
        echo json_encode(['success' => false, 'message' => 'Electronic resource not found']);
        return;
    }

    // Delete electronic resource
    $stmt = $db->prepare("DELETE FROM electronic_resources WHERE id = ?");
    $success = $stmt->execute([$id]);

    if ($success) {
        // Log the action
        logAudit('DELETE', 'electronic_resources', $id, $currentData, null);
        
        echo json_encode(['success' => true, 'message' => 'Electronic resource deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete electronic resource']);
    }
}

function handleGet() {
    global $db;
    
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Resource ID is required']);
        return;
    }

    $stmt = $db->prepare("SELECT * FROM electronic_resources WHERE id = ?");
    $stmt->execute([$id]);
    $resource = $stmt->fetch();
    
    if ($resource) {
        echo json_encode(['success' => true, 'resource' => $resource]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Electronic resource not found']);
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