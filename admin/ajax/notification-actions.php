<?php
/**
 * AJAX NOTIFICATION ACTIONS
 * ============================================
 */

session_start();
require_once '../../config/db.php';
require_once '../../config/security.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$user_role = $_SESSION['user_role'] ?? '';
if (!in_array($user_role, ['admin', 'receptionist'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Forbidden']));
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

// Verify CSRF
if (!Security::verifyCSRFToken($input['csrf_token'] ?? '')) {
    die(json_encode(['success' => false, 'message' => 'Invalid security token']));
}

$action = $input['action'] ?? '';

if ($action == 'mark_all_read') {
    // Mark all unread contacts as read
    $conn->query("UPDATE contacts SET status = 'read' WHERE status = 'unread'");
    
    // Log activity
    logActivity($conn, $_SESSION['user_id'], 'Marked all notifications as read');
    
    echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
    
} elseif ($action == 'get_unread_count') {
    $result = $conn->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'unread'");
    $unread_contacts = $result ? $result->fetch_assoc()['count'] : 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
    $pending_bookings = $result ? $result->fetch_assoc()['count'] : 0;
    
    echo json_encode([
        'success' => true,
        'unread_contacts' => $unread_contacts,
        'pending_bookings' => $pending_bookings,
        'total' => $unread_contacts + $pending_bookings
    ]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}