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
    if ($method === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'borrow':
                handleBorrow();
                break;
            case 'return':
                handleReturn();
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleBorrow() {
    global $db;
    
    $patronId = (int)($_POST['patron_id'] ?? 0);
    $resourceType = sanitizeInput($_POST['resource_type'] ?? '');
    $resourceId = (int)($_POST['resource_id'] ?? 0);
    
    if (!$patronId || !$resourceType || !$resourceId) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    // Validate patron exists and is active
    $patronStmt = $db->prepare("SELECT * FROM patrons WHERE id = ? AND status = 'active'");
    $patronStmt->execute([$patronId]);
    $patron = $patronStmt->fetch();
    
    if (!$patron) {
        echo json_encode(['success' => false, 'message' => 'Patron not found or inactive']);
        return;
    }
    
    // Check if resource exists and is available
    $resourceTable = $resourceType === 'book' ? 'books' : 'academic_coursework';
    $resourceStmt = $db->prepare("SELECT * FROM $resourceTable WHERE id = ? AND on_shelf = 1");
    $resourceStmt->execute([$resourceId]);
    $resource = $resourceStmt->fetch();
    
    if (!$resource) {
        echo json_encode(['success' => false, 'message' => 'Resource not found or not available']);
        return;
    }
    
    // Check if patron already has this resource borrowed
    $existingStmt = $db->prepare("SELECT id FROM circulation WHERE patron_id = ? AND resource_type = ? AND resource_id = ? AND status = 'borrowed'");
    $existingStmt->execute([$patronId, $resourceType, $resourceId]);
    if ($existingStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Patron already has this resource borrowed']);
        return;
    }
    
    // Get system settings
    $settingsStmt = $db->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key = 'max_borrow_days'");
    $settingsStmt->execute();
    $maxBorrowDays = 14; // default
    while ($row = $settingsStmt->fetch()) {
        if ($row['setting_key'] === 'max_borrow_days') {
            $maxBorrowDays = (int)$row['setting_value'];
        }
    }
    
    // Calculate due date
    $borrowDate = date('Y-m-d');
    $dueDate = date('Y-m-d', strtotime("+$maxBorrowDays days"));
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Insert circulation record
        $circulationStmt = $db->prepare("
            INSERT INTO circulation (patron_id, resource_type, resource_id, borrow_date, due_date, status) 
            VALUES (?, ?, ?, ?, ?, 'borrowed')
        ");
        $circulationStmt->execute([$patronId, $resourceType, $resourceId, $borrowDate, $dueDate]);
        
        // Update resource availability
        $updateStmt = $db->prepare("UPDATE $resourceTable SET on_shelf = 0 WHERE id = ?");
        $updateStmt->execute([$resourceId]);
        
        // Log the action
        logAudit('BORROW', 'circulation', $db->lastInsertId(), null, [
            'patron_id' => $patronId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'borrow_date' => $borrowDate,
            'due_date' => $dueDate
        ]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Resource borrowed successfully',
            'due_date' => $dueDate
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to process borrow: ' . $e->getMessage()]);
    }
}

function handleReturn() {
    global $db;
    
    $circulationId = (int)($_POST['circulation_id'] ?? 0);
    $fineAmount = (float)($_POST['fine_amount'] ?? 0);
    $paymentAmount = (float)($_POST['payment_amount'] ?? 0);
    $receiptNumber = sanitizeInput($_POST['receipt_number'] ?? '');
    
    if (!$circulationId) {
        echo json_encode(['success' => false, 'message' => 'Circulation ID is required']);
        return;
    }
    
    // Get circulation record
    $circulationStmt = $db->prepare("
        SELECT c.*, p.name as patron_name, p.id_number
        FROM circulation c
        JOIN patrons p ON c.patron_id = p.id
        WHERE c.id = ? AND c.status = 'borrowed'
    ");
    $circulationStmt->execute([$circulationId]);
    $circulation = $circulationStmt->fetch();
    
    if (!$circulation) {
        echo json_encode(['success' => false, 'message' => 'Circulation record not found']);
        return;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        $returnDate = date('Y-m-d');
        
        // Update circulation record
        $updateStmt = $db->prepare("
            UPDATE circulation 
            SET actual_return = ?, fine = ?, payment = ?, receipt_number = ?, status = 'returned' 
            WHERE id = ?
        ");
        $updateStmt->execute([$returnDate, $fineAmount, $paymentAmount, $receiptNumber, $circulationId]);
        
        // Update resource availability
        $resourceTable = $circulation['resource_type'] === 'book' ? 'books' : 'academic_coursework';
        $resourceStmt = $db->prepare("UPDATE $resourceTable SET on_shelf = 1 WHERE id = ?");
        $resourceStmt->execute([$circulation['resource_id']]);
        
        // Update patron's fine balance if payment was made
        if ($paymentAmount > 0) {
            $patronStmt = $db->prepare("
                UPDATE patrons 
                SET fine = GREATEST(0, fine - ?), payment = payment + ? 
                WHERE id = ?
            ");
            $patronStmt->execute([$paymentAmount, $paymentAmount, $circulation['patron_id']]);
        }
        
        // Log the action
        logAudit('RETURN', 'circulation', $circulationId, $circulation, [
            'actual_return' => $returnDate,
            'fine' => $fineAmount,
            'payment' => $paymentAmount,
            'receipt_number' => $receiptNumber,
            'status' => 'returned'
        ]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Resource returned successfully',
            'receipt_number' => $receiptNumber
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to process return: ' . $e->getMessage()]);
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