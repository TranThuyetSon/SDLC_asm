<?php
/**
 * AJAX BOOKING ACTIONS
 * ============================================
 */

session_start();
require_once '../../config/db.php';
require_once '../../config/security.php';
require_once '../includes/admin-functions.php';

// Set JSON header
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

// Verify CSRF
if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    die(json_encode(['success' => false, 'message' => 'Invalid security token']));
}

$action = $_POST['action'] ?? '';

if ($action == 'quick_booking') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $room_id = (int)($_POST['room_id'] ?? 0);
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';
    $guests = (int)($_POST['guests'] ?? 2);
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $special_requests = trim($_POST['special_requests'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($customer_name)) $errors[] = 'Customer name is required';
    if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address';
    if (empty($customer_phone)) $errors[] = 'Phone number is required';
    if ($room_id <= 0) $errors[] = 'Please select a room';
    if (empty($check_in) || empty($check_out)) $errors[] = 'Check-in and check-out dates are required';
    if ($check_in < date('Y-m-d')) $errors[] = 'Check-in date cannot be in the past';
    if ($check_in >= $check_out) $errors[] = 'Check-out date must be after check-in date';
    
    if (!empty($errors)) {
        die(json_encode(['success' => false, 'message' => implode(', ', $errors)]));
    }
    
    // Check room availability
    $check_sql = "SELECT COUNT(*) as count FROM bookings 
                  WHERE room_id = ? 
                  AND status IN ('pending', 'confirmed', 'checked_in')
                  AND check_in_date < ? 
                  AND check_out_date > ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("iss", $room_id, $check_out, $check_in);
    $stmt->execute();
    $conflict = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($conflict['count'] > 0) {
        die(json_encode(['success' => false, 'message' => 'Room is not available for selected dates']));
    }
    
    $conn->begin_transaction();
    
    try {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $customer_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $user_id = $row['id'];
        } else {
            // Create new user
            $random_pass = bin2hex(random_bytes(8));
            $hashed = password_hash($random_pass, PASSWORD_DEFAULT);
            
            $username = explode('@', $customer_email)[0] . rand(100, 999);
            
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, phone, role_id, is_active) VALUES (?, ?, ?, ?, ?, 3, 1)");
            $stmt->bind_param("sssss", $username, $customer_email, $hashed, $customer_name, $customer_phone);
            $stmt->execute();
            $user_id = $conn->insert_id;
        }
        $stmt->close();
        
        // Check/create customer
        $stmt = $conn->prepare("SELECT id FROM customers WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $customer_id = $row['id'];
        } else {
            $stmt = $conn->prepare("INSERT INTO customers (user_id, loyalty_points, nationality, address) VALUES (?, 0, 'Vietnam', '')");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $customer_id = $conn->insert_id;
        }
        $stmt->close();
        
        // Get room price
        $stmt = $conn->prepare("SELECT rt.base_price FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $room = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$room) {
            throw new Exception('Room not found');
        }
        
        // Calculate total
        $date1 = new DateTime($check_in);
        $date2 = new DateTime($check_out);
        $nights = $date1->diff($date2)->days;
        $total_price = $room['base_price'] * $nights;
        
        // Create booking
        $booking_code = 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        
        $stmt = $conn->prepare("INSERT INTO bookings (booking_code, customer_id, room_id, check_in_date, check_out_date, total_price, status, number_of_guests, special_requests, created_by) VALUES (?, ?, ?, ?, ?, ?, 'confirmed', ?, ?, ?)");
        $stmt->bind_param("siissdiii", $booking_code, $customer_id, $room_id, $check_in, $check_out, $total_price, $guests, $special_requests, $_SESSION['user_id']);
        $stmt->execute();
        $booking_id = $conn->insert_id;
        $stmt->close();
        
        // Add payment if not cash
        if ($payment_method != 'cash') {
            $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, payment_status, payment_date) VALUES (?, ?, ?, 'completed', NOW())");
            $stmt->bind_param("ids", $booking_id, $total_price, $payment_method);
            $stmt->execute();
            $stmt->close();
        }
        
        // Update room status
        $stmt = $conn->prepare("UPDATE rooms SET status = 'booked' WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        logActivity($conn, $_SESSION['user_id'], "Quick booking created: $booking_code", 'bookings', $booking_id);
        
        echo json_encode(['success' => true, 'message' => 'Booking created successfully! Code: ' . $booking_code, 'booking_code' => $booking_code]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to create booking: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}