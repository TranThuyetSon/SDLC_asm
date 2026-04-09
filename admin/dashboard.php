<?php
/**
 * ADMIN DASHBOARD - MAIN ROUTER
 * ============================================
 */

require_once '../config/db.php';
require_once '../config/security.php';
require_once 'includes/admin-functions.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=admin/dashboard.php');
    exit();
}

// Check permission - allow both admin and receptionist
$user_role = $_SESSION['user_role'] ?? '';
if (!in_array($user_role, ['admin', 'receptionist'])) {
    header('Location: ../index.php');
    exit();
}

$is_admin = ($user_role === 'admin');

$module = $_GET['module'] ?? 'overview';

// Security: Validate module name
$allowed_modules = [
    'overview', 'bookings', 'rooms', 'room-types', 'users', 
    'customers', 'payments', 'services', 'restaurant', 'menu-items',
    'contacts', 'reports', 'audit-logs', 'settings'
];

// Receptionist only has access to some modules
if (!$is_admin) {
    $allowed_modules = ['overview', 'bookings', 'rooms', 'customers', 'payments'];
}

if (!in_array($module, $allowed_modules)) {
    $module = 'overview';
}

// Include header
require_once 'includes/admin-header.php';

// Route to appropriate module
$module_file = "modules/{$module}.php";

if (file_exists($module_file)) {
    require_once $module_file;
} else {
    echo '<div class="alert alert-error">Module not found: ' . htmlspecialchars($module) . '</div>';
}

// Include footer
require_once 'includes/admin-footer.php';