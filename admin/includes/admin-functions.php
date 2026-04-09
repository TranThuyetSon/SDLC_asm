<?php
/**
 * ADMIN HELPER FUNCTIONS
 * ============================================
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/security.php';

/**
 * Check if current user is admin (strict)
 */
function requireAdmin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }
    
    $user_role = $_SESSION['user_role'] ?? '';
    if ($user_role !== 'admin') {
        header('Location: ../index.php');
        exit();
    }
}

/**
 * Check if current user is staff (admin or receptionist)
 */
function requireStaff() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }
    
    $user_role = $_SESSION['user_role'] ?? '';
    if (!in_array($user_role, ['admin', 'receptionist'])) {
        header('Location: ../index.php');
        exit();
    }
}

/**
 * Check if user has permission for specific action
 */
function hasAdminPermission($permission) {
    $user_role = $_SESSION['user_role'] ?? '';
    
    // Admin has all permissions
    if ($user_role === 'admin') {
        return true;
    }
    
    // Receptionist permissions
    if ($user_role === 'receptionist') {
        $allowed = [
            'view_dashboard',
            'view_bookings',
            'manage_bookings',
            'view_rooms',
            'view_customers',
            'view_payments'
        ];
        return in_array($permission, $allowed);
    }
    
    return false;
}

/**
 * Get statistics for dashboard
 */
function getDashboardStats($conn) {
    $stats = [];
    
    // Total counts
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM customers");
    $stats['total_customers'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM rooms");
    $stats['total_rooms'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM bookings");
    $stats['total_bookings'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM services");
    $stats['total_services'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM payments");
    $stats['total_payments'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Available rooms
    $result = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'available'");
    $stats['available_rooms'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Occupied rooms
    $result = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'occupied'");
    $stats['occupied_rooms'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Active bookings
    $result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status IN ('pending', 'confirmed', 'checked_in')");
    $stats['active_bookings'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Today check-ins
    $today = date('Y-m-d');
    $result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE check_in_date = '$today' AND status IN ('confirmed', 'pending')");
    $stats['today_checkins'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Today check-outs
    $result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE check_out_date = '$today' AND status = 'checked_in'");
    $stats['today_checkouts'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Unread contacts
    $result = $conn->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'unread'");
    $stats['unread_contacts'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Revenue stats
    $thisMonth = date('Y-m-01');
    $result = $conn->query("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE payment_status = 'completed' 
        AND created_at >= '$thisMonth'
    ");
    $stats['monthly_revenue'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    $result = $conn->query("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE payment_status = 'completed' 
        AND DATE(created_at) = '$today'
    ");
    $stats['today_revenue'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // Pending payments
    $result = $conn->query("
        SELECT COALESCE(SUM(total_price), 0) as total 
        FROM bookings 
        WHERE status IN ('confirmed', 'checked_in')
    ");
    $stats['pending_payments'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    return $stats;
}

/**
 * Get recent bookings
 */
function getRecentBookings($conn, $limit = 10) {
    $sql = "SELECT b.*, r.room_number, rt.name as room_type, 
                   u.full_name as customer_name, u.email as customer_email,
                   p.payment_status, p.amount as paid_amount
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN room_types rt ON r.room_type_id = rt.id
            JOIN customers c ON b.customer_id = c.id
            JOIN users u ON c.user_id = u.id
            LEFT JOIN payments p ON b.id = p.booking_id
            ORDER BY b.created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    $stmt->close();
    
    return $bookings;
}

/**
 * Get recent activities (audit logs)
 */
function getRecentActivities($conn, $limit = 20) {
    $sql = "SELECT a.*, u.username, u.full_name 
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            ORDER BY a.created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    $stmt->close();
    
    return $activities;
}

/**
 * Get chart data for revenue (last 12 months)
 */
function getRevenueChartData($conn) {
    $data = [];
    
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $sql = "SELECT COALESCE(SUM(amount), 0) as revenue 
                FROM payments 
                WHERE payment_status = 'completed' 
                AND DATE(created_at) BETWEEN ? AND ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $revenue = $result->fetch_assoc()['revenue'];
        $stmt->close();
        
        $data[] = [
            'month' => date('M Y', strtotime($startDate)),
            'revenue' => (float)$revenue
        ];
    }
    
    return $data;
}

/**
 * Get booking statistics by status
 */
function getBookingStatsByStatus($conn) {
    $sql = "SELECT status, COUNT(*) as count FROM bookings GROUP BY status";
    $result = $conn->query($sql);
    $stats = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats[$row['status']] = $row['count'];
        }
    }
    
    return $stats;
}

/**
 * Get room occupancy rate
 */
function getOccupancyRate($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM rooms");
    $totalRooms = $result ? $result->fetch_assoc()['count'] : 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'occupied'");
    $occupiedRooms = $result ? $result->fetch_assoc()['count'] : 0;
    
    if ($totalRooms == 0) {
        return 0;
    }
    
    return round(($occupiedRooms / $totalRooms) * 100, 1);
}

/**
 * Get popular room types
 */
function getPopularRoomTypes($conn, $limit = 5) {
    $sql = "SELECT rt.id, rt.name, COUNT(b.id) as booking_count, 
                   COALESCE(SUM(b.total_price), 0) as total_revenue
            FROM room_types rt
            JOIN rooms r ON rt.id = r.room_type_id
            LEFT JOIN bookings b ON r.id = b.room_id
            GROUP BY rt.id
            ORDER BY booking_count DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $popular = [];
    while ($row = $result->fetch_assoc()) {
        $popular[] = $row;
    }
    $stmt->close();
    
    return $popular;
}

/**
 * Get top customers by spending
 */
function getTopCustomers($conn, $limit = 10) {
    $sql = "SELECT u.id, u.full_name, u.email, u.phone,
                   COUNT(b.id) as booking_count,
                   COALESCE(SUM(b.total_price), 0) as total_spent,
                   MAX(b.created_at) as last_booking
            FROM users u
            JOIN customers c ON u.id = c.user_id
            LEFT JOIN bookings b ON c.id = b.customer_id
            WHERE u.role_id = 3
            GROUP BY u.id
            ORDER BY total_spent DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    $stmt->close();
    
    return $customers;
}

/**
 * Format currency for display
 */
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' VND';
}

/**
 * Format date for display
 */
function formatDateTime($date) {
    return date('d/m/Y H:i', strtotime($date));
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status, $type = 'booking') {
    $badges = [
        'booking' => [
            'pending' => ['bg' => '#fef3c7', 'color' => '#d97706', 'text' => 'Pending'],
            'confirmed' => ['bg' => '#d1fae5', 'color' => '#059669', 'text' => 'Confirmed'],
            'checked_in' => ['bg' => '#dbeafe', 'color' => '#2563eb', 'text' => 'Checked In'],
            'checked_out' => ['bg' => '#e2e8f0', 'color' => '#64748b', 'text' => 'Checked Out'],
            'cancelled' => ['bg' => '#fee2e2', 'color' => '#dc2626', 'text' => 'Cancelled']
        ],
        'room' => [
            'available' => ['bg' => '#d1fae5', 'color' => '#059669', 'text' => 'Available'],
            'booked' => ['bg' => '#fef3c7', 'color' => '#d97706', 'text' => 'Booked'],
            'occupied' => ['bg' => '#dbeafe', 'color' => '#2563eb', 'text' => 'Occupied'],
            'maintenance' => ['bg' => '#fee2e2', 'color' => '#dc2626', 'text' => 'Maintenance']
        ],
        'payment' => [
            'pending' => ['bg' => '#fef3c7', 'color' => '#d97706', 'text' => 'Pending'],
            'completed' => ['bg' => '#d1fae5', 'color' => '#059669', 'text' => 'Paid'],
            'failed' => ['bg' => '#fee2e2', 'color' => '#dc2626', 'text' => 'Failed'],
            'refunded' => ['bg' => '#e2e8f0', 'color' => '#64748b', 'text' => 'Refunded']
        ],
        'contact' => [
            'unread' => ['bg' => '#fef3c7', 'color' => '#d97706', 'text' => 'Unread'],
            'read' => ['bg' => '#dbeafe', 'color' => '#2563eb', 'text' => 'Read'],
            'replied' => ['bg' => '#d1fae5', 'color' => '#059669', 'text' => 'Replied']
        ]
    ];
    
    $config = $badges[$type][$status] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'text' => ucfirst($status)];
    
    return sprintf(
        '<span style="display: inline-block; padding: 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 600; border-radius: 9999px; background: %s; color: %s;">%s</span>',
        $config['bg'],
        $config['color'],
        $config['text']
    );
}