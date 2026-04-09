<?php
/**
 * ARIA HOTEL - DATABASE CONFIGURATION
 * ============================================
 */

// Chỉ gọi session_start() nếu chưa có session active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database credentials
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'hotel_booking_system';

// Kết nối database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối database thất bại: " . $conn->connect_error);
}

// Thiết lập charset UTF-8
$conn->set_charset("utf8mb4");

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

/**
 * Lấy thông tin user hiện tại
 * @param mysqli $conn
 * @return array|null
 */
function getCurrentUser($conn) {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $stmt = $conn->prepare("
        SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Kiểm tra quyền của user
 * @param mysqli $conn
 * @param string $permission_name
 * @return bool
 */
function hasPermission($conn, $permission_name) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'] ?? '';
    
    // Admin có tất cả quyền
    if ($user_role === 'admin') {
        return true;
    }
    
    // Receptionist có quyền giới hạn
    if ($user_role === 'receptionist') {
        $allowed_permissions = [
            'view_booking', 
            'create_booking', 
            'edit_booking', 
            'checkin_booking', 
            'checkout_booking', 
            'view_room'
        ];
        return in_array($permission_name, $allowed_permissions);
    }
    
    // Customer chỉ có quyền xem và tạo booking
    if ($user_role === 'customer') {
        $allowed_permissions = ['view_booking', 'create_booking'];
        return in_array($permission_name, $allowed_permissions);
    }
    
    return false;
}

/**
 * Kiểm tra user có phải admin không
 * @return bool
 */
function isAdmin() {
    return ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * Kiểm tra user có phải receptionist không
 * @return bool
 */
function isReceptionist() {
    return ($_SESSION['user_role'] ?? '') === 'receptionist';
}

/**
 * Kiểm tra user có phải customer không
 * @return bool
 */
function isCustomer() {
    return ($_SESSION['user_role'] ?? '') === 'customer';
}

/**
 * Lấy thông tin khách sạn
 * @return array
 */
function getHotelInfo() {
    return [
        'name' => 'Aria Hotel',
        'address' => '123 Luxury Street, District 1, Ho Chi Minh City, Vietnam',
        'phone' => '+84 28 1234 5678',
        'hotline' => '+84 903 123 456',
        'email' => 'reservations@ariahotel.com',
        'support_email' => 'support@ariahotel.com',
        'facebook' => 'https://facebook.com/ariahotel',
        'instagram' => 'https://instagram.com/ariahotel',
        'twitter' => 'https://twitter.com/ariahotel'
    ];
}

/**
 * Log hoạt động vào audit_logs
 * @param mysqli $conn
 * @param int|null $user_id
 * @param string $action
 * @param string $table_name
 * @param int|null $record_id
 * @param string|null $old_data
 * @param string|null $new_data
 * @return bool
 */
function logActivity($conn, $user_id, $action, $table_name = null, $record_id = null, $old_data = null, $new_data = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    $stmt = $conn->prepare("
        INSERT INTO audit_logs (user_id, action, table_name, record_id, old_data, new_data, ip_address) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ississs", $user_id, $action, $table_name, $record_id, $old_data, $new_data, $ip);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Escape dữ liệu để hiển thị an toàn
 * @param string $data
 * @return string
 */
function e($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Format tiền tệ VND
 * @param float $amount
 * @return string
 */
function formatMoney($amount) {
    return number_format($amount, 0, ',', '.') . ' VND';
}

/**
 * Format ngày tháng
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

/**
 * Tính số đêm giữa 2 ngày
 * @param string $check_in
 * @param string $check_out
 * @return int
 */
function calculateNights($check_in, $check_out) {
    $date1 = new DateTime($check_in);
    $date2 = new DateTime($check_out);
    return $date1->diff($date2)->days;
}

/**
 * Tạo mã booking ngẫu nhiên
 * @param string $prefix
 * @return string
 */
function generateBookingCode($prefix = 'BK') {
    return $prefix . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

/**
 * Validate số điện thoại Việt Nam
 * @param string $phone
 * @return bool
 */
function validatePhone($phone) {
    return preg_match('/^[0-9]{10,11}$/', $phone);
}

/**
 * Validate email
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}