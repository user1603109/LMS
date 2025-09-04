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
    $requiredFields = ['name', 'id_number', 'gender'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }

    // Check if ID number already exists
    $checkStmt = $db->prepare("SELECT id FROM patrons WHERE id_number = ?");
    $checkStmt->execute([sanitizeInput($_POST['id_number'])]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'ID number already exists']);
        return;
    }

    // Check if email already exists (if provided)
    if (!empty($_POST['email'])) {
        $emailCheckStmt = $db->prepare("SELECT id FROM patrons WHERE email = ?");
        $emailCheckStmt->execute([sanitizeInput($_POST['email'])]);
        if ($emailCheckStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email address already exists']);
            return;
        }
    }

    // Prepare data
    $data = [
        'name' => sanitizeInput($_POST['name']),
        'id_number' => sanitizeInput($_POST['id_number']),
        'gender' => sanitizeInput($_POST['gender']),
        'group' => sanitizeInput($_POST['group'] ?? ''),
        'course_department' => sanitizeInput($_POST['course_department'] ?? ''),
        'year_level' => sanitizeInput($_POST['year_level'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'contact_number' => sanitizeInput($_POST['contact_number'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'status' => sanitizeInput($_POST['status'] ?? 'active'),
        'fine' => 0.00,
        'payment' => 0.00
    ];

    // Insert patron
    $sql = "INSERT INTO patrons (name, id_number, gender, group, course_department, year_level, 
            address, contact_number, email, status, fine, payment) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $success = $stmt->execute([
        $data['name'], $data['id_number'], $data['gender'], $data['group'], 
        $data['course_department'], $data['year_level'], $data['address'], 
        $data['contact_number'], $data['email'], $data['status'], 
        $data['fine'], $data['payment']
    ]);

    if ($success) {
        $patronId = $db->lastInsertId();
        
        // Log the action
        logAudit('CREATE', 'patrons', $patronId, null, $data);
        
        echo json_encode(['success' => true, 'message' => 'Patron added successfully', 'id' => $patronId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add patron']);
    }
}

function handleUpdate() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Patron ID is required']);
        return;
    }

    // Get current data for audit log
    $currentStmt = $db->prepare("SELECT * FROM patrons WHERE id = ?");
    $currentStmt->execute([$id]);
    $currentData = $currentStmt->fetch();
    
    if (!$currentData) {
        echo json_encode(['success' => false, 'message' => 'Patron not found']);
        return;
    }

    // Prepare update data
    $data = [
        'name' => sanitizeInput($input['name'] ?? $currentData['name']),
        'id_number' => sanitizeInput($input['id_number'] ?? $currentData['id_number']),
        'gender' => sanitizeInput($input['gender'] ?? $currentData['gender']),
        'group' => sanitizeInput($input['group'] ?? $currentData['group']),
        'course_department' => sanitizeInput($input['course_department'] ?? $currentData['course_department']),
        'year_level' => sanitizeInput($input['year_level'] ?? $currentData['year_level']),
        'address' => sanitizeInput($input['address'] ?? $currentData['address']),
        'contact_number' => sanitizeInput($input['contact_number'] ?? $currentData['contact_number']),
        'email' => sanitizeInput($input['email'] ?? $currentData['email']),
        'status' => sanitizeInput($input['status'] ?? $currentData['status'])
    ];

    // Check if ID number already exists (excluding current record)
    if ($data['id_number'] !== $currentData['id_number']) {
        $checkStmt = $db->prepare("SELECT id FROM patrons WHERE id_number = ? AND id != ?");
        $checkStmt->execute([$data['id_number'], $id]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'ID number already exists']);
            return;
        }
    }

    // Check if email already exists (excluding current record)
    if (!empty($data['email']) && $data['email'] !== $currentData['email']) {
        $emailCheckStmt = $db->prepare("SELECT id FROM patrons WHERE email = ? AND id != ?");
        $emailCheckStmt->execute([$data['email'], $id]);
        if ($emailCheckStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email address already exists']);
            return;
        }
    }

    // Update patron
    $sql = "UPDATE patrons SET name = ?, id_number = ?, gender = ?, group = ?, course_department = ?, 
            year_level = ?, address = ?, contact_number = ?, email = ?, status = ?, updated_at = NOW() 
            WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    $success = $stmt->execute([
        $data['name'], $data['id_number'], $data['gender'], $data['group'], 
        $data['course_department'], $data['year_level'], $data['address'], 
        $data['contact_number'], $data['email'], $data['status'], $id
    ]);

    if ($success) {
        // Log the action
        logAudit('UPDATE', 'patrons', $id, $currentData, $data);
        
        echo json_encode(['success' => true, 'message' => 'Patron updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update patron']);
    }
}

function handleDelete() {
    global $db;
    
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Patron ID is required']);
        return;
    }

    // Get current data for audit log
    $currentStmt = $db->prepare("SELECT * FROM patrons WHERE id = ?");
    $currentStmt->execute([$id]);
    $currentData = $currentStmt->fetch();
    
    if (!$currentData) {
        echo json_encode(['success' => false, 'message' => 'Patron not found']);
        return;
    }

    // Check if patron has active borrows
    $circulationStmt = $db->prepare("SELECT COUNT(*) as count FROM circulation WHERE patron_id = ? AND status = 'borrowed'");
    $circulationStmt->execute([$id]);
    $borrowedCount = $circulationStmt->fetch()['count'];
    
    if ($borrowedCount > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete patron with active borrows']);
        return;
    }

    // Check if patron has outstanding fines
    if ($currentData['fine'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete patron with outstanding fines']);
        return;
    }

    // Delete patron
    $stmt = $db->prepare("DELETE FROM patrons WHERE id = ?");
    $success = $stmt->execute([$id]);

    if ($success) {
        // Log the action
        logAudit('DELETE', 'patrons', $id, $currentData, null);
        
        echo json_encode(['success' => true, 'message' => 'Patron deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete patron']);
    }
}

function handleGet() {
    global $db;
    
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Patron ID is required']);
        return;
    }

    $stmt = $db->prepare("SELECT * FROM patrons WHERE id = ?");
    $stmt->execute([$id]);
    $patron = $stmt->fetch();
    
    if ($patron) {
        echo json_encode(['success' => true, 'patron' => $patron]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Patron not found']);
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