<?php
/**
 * ADMIN HEADER
 * ============================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/admin-functions.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=admin/dashboard.php');
    exit();
}

// Check permission
$user_role = $_SESSION['user_role'] ?? '';
if (!in_array($user_role, ['admin', 'receptionist'])) {
    header('Location: ../index.php');
    exit();
}

$user_fullname = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin';
$user_role_display = ucfirst($user_role);
$initials = strtoupper(substr($user_fullname, 0, 1));
$user_avatar = "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&background=0a192f&color=ffffff&size=128&bold=true";

$current_module = $_GET['module'] ?? 'overview';

// Page titles mapping
$page_titles = [
    'overview' => 'Dashboard Overview',
    'bookings' => 'Booking Management',
    'rooms' => 'Room Management',
    'room-types' => 'Room Type Management',
    'users' => 'User Management',
    'customers' => 'Customer Management',
    'payments' => 'Payment Management',
    'services' => 'Service Management',
    'restaurant' => 'Restaurant Reservations',
    'menu-items' => 'Menu Management',
    'contacts' => 'Contact Messages',
    'reports' => 'Reports & Analytics',
    'audit-logs' => 'Audit Logs',
    'settings' => 'System Settings'
];

$page_title = $page_titles[$current_module] ?? 'Admin Dashboard';

// Get pending counts
$pending_result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
$pending_count = $pending_result ? $pending_result->fetch_assoc()['count'] : 0;

$unread_result = $conn->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'unread'");
$unread_count = $unread_result ? $unread_result->fetch_assoc()['count'] : 0;

$total_notifications = $unread_count + $pending_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Aria Hotel Admin</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Chart.js for graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Main Styles -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }
        .admin-layout { display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .admin-sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0a192f 0%, #05101e 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
        }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { color: white; font-size: 1.5rem; margin-bottom: 0.25rem; }
        .sidebar-header p { color: rgba(255,255,255,0.6); font-size: 0.8rem; }
        .sidebar-nav { padding: 1rem 0; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        .nav-item i { width: 20px; }
        .nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .nav-item.active { background: rgba(255,255,255,0.1); color: white; border-left-color: white; }
        .nav-item .badge {
            margin-left: auto;
            background: #dc2626;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .nav-item .badge-warning { background: #d97706; }
        .sidebar-divider { height: 1px; background: rgba(255,255,255,0.1); margin: 1rem 1.5rem; }
        
        /* Main Content */
        .admin-main { flex: 1; margin-left: 280px; }
        .admin-topbar {
            background: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .topbar-left h1 { font-size: 1.5rem; margin-bottom: 0; color: #0a192f; }
        .topbar-right { display: flex; align-items: center; gap: 1rem; }
        .user-menu { position: relative; }
        .user-trigger {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
        }
        .user-avatar { width: 36px; height: 36px; border-radius: 50%; }
        .user-name { font-weight: 500; }
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            min-width: 200px;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s;
            z-index: 1000;
            margin-top: 8px;
            border: 1px solid #e2e8f0;
        }
        .user-menu.active .dropdown-menu { opacity: 1; visibility: visible; }
        .dropdown-header { padding: 12px 16px; border-bottom: 1px solid #e2e8f0; }
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            color: #334155;
            text-decoration: none;
            transition: background 0.2s;
        }
        .dropdown-item:hover { background: #f8fafc; }
        .dropdown-item i { width: 20px; }
        .dropdown-divider { height: 1px; background: #e2e8f0; }
        
        /* Notifications */
        .notifications-dropdown { position: relative; }
        .notification-btn {
            position: relative;
            width: 40px;
            height: 40px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc2626;
            color: white;
            font-size: 0.7rem;
            padding: 0.15rem 0.4rem;
            border-radius: 9999px;
        }
        .notifications-menu {
            position: absolute;
            top: 100%;
            right: 0;
            width: 320px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: none;
            margin-top: 8px;
            border: 1px solid #e2e8f0;
            z-index: 1000;
        }
        .notifications-menu.show { display: block; }
        .notifications-header {
            display: flex;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .notification-item {
            display: flex;
            gap: 12px;
            padding: 1rem;
            text-decoration: none;
            color: inherit;
            border-bottom: 1px solid #f1f5f9;
        }
        .notification-item:hover { background: #f8fafc; }
        .text-warning { color: #d97706; }
        .text-info { color: #2563eb; }
        .text-center { text-align: center; }
        .p-3 { padding: 1rem; }
        
        /* Content */
        .admin-content { padding: 1.5rem; }
        
        /* Alert */
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-error { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-success { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0; }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            background: #f8fafc;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            color: #0a192f;
        }
        .stat-number { font-size: 1.75rem; font-weight: 700; color: #0a192f; }
        .stat-label { color: #64748b; font-size: 0.85rem; }
        
        /* Cards */
        .admin-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }
        .admin-card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-card-body { padding: 1.5rem; }
        .admin-card-body.p-0 { padding: 0; }
        
        /* Tables */
        .table-wrapper { overflow-x: auto; }
        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th {
            background: #f8fafc;
            padding: 0.75rem 1rem;
            font-weight: 600;
            font-size: 0.8rem;
            color: #64748b;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }
        .admin-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .admin-table tbody tr:hover { background: #f8fafc; }
        
        /* Two Column */
        .two-column-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-primary { background: #0a192f; color: white; }
        .btn-primary:hover { background: #1e3a5f; }
        .btn-outline-primary {
            background: transparent;
            color: #0a192f;
            border: 1px solid #0a192f;
        }
        .btn-outline-primary:hover { background: #0a192f; color: white; }
        .btn-sm { padding: 0.25rem 0.75rem; font-size: 0.75rem; }
        
        /* Activity List */
        .activity-list { max-height: 400px; overflow-y: auto; }
        .activity-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .activity-icon {
            width: 32px;
            height: 32px;
            background: #f8fafc;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a192f;
        }
        .activity-content { flex: 1; }
        .activity-time { font-size: 0.75rem; color: #64748b; }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
        }
        
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .two-column-row { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .admin-sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-main { margin-left: 0; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="admin-layout">
    
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <h2>Aria Hotel</h2>
            <p>Admin Panel v2.0</p>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php?module=overview" class="nav-item <?php echo $current_module == 'overview' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
            </a>
            <a href="dashboard.php?module=bookings" class="nav-item <?php echo $current_module == 'bookings' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i><span>Bookings</span>
                <?php if ($pending_count > 0): ?>
                    <span class="badge badge-warning"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="dashboard.php?module=rooms" class="nav-item <?php echo $current_module == 'rooms' ? 'active' : ''; ?>">
                <i class="fas fa-bed"></i><span>Rooms</span>
            </a>
            <a href="dashboard.php?module=room-types" class="nav-item <?php echo $current_module == 'room-types' ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i><span>Room Types</span>
            </a>
            <?php if ($user_role === 'admin'): ?>
            <a href="dashboard.php?module=users" class="nav-item <?php echo $current_module == 'users' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i><span>Users</span>
            </a>
            <?php endif; ?>
            <a href="dashboard.php?module=customers" class="nav-item <?php echo $current_module == 'customers' ? 'active' : ''; ?>">
                <i class="fas fa-user-friends"></i><span>Customers</span>
            </a>
            <a href="dashboard.php?module=payments" class="nav-item <?php echo $current_module == 'payments' ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i><span>Payments</span>
            </a>
            <a href="dashboard.php?module=services" class="nav-item <?php echo $current_module == 'services' ? 'active' : ''; ?>">
                <i class="fas fa-concierge-bell"></i><span>Services</span>
            </a>
            <a href="dashboard.php?module=restaurant" class="nav-item <?php echo $current_module == 'restaurant' ? 'active' : ''; ?>">
                <i class="fas fa-utensils"></i><span>Restaurant</span>
            </a>
            <a href="dashboard.php?module=menu-items" class="nav-item <?php echo $current_module == 'menu-items' ? 'active' : ''; ?>">
                <i class="fas fa-list-ul"></i><span>Menu Items</span>
            </a>
            <a href="dashboard.php?module=contacts" class="nav-item <?php echo $current_module == 'contacts' ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i><span>Contacts</span>
                <?php if ($unread_count > 0): ?>
                    <span class="badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <?php if ($user_role === 'admin'): ?>
            <a href="dashboard.php?module=reports" class="nav-item <?php echo $current_module == 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i><span>Reports</span>
            </a>
            <a href="dashboard.php?module=audit-logs" class="nav-item <?php echo $current_module == 'audit-logs' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i><span>Audit Logs</span>
            </a>
            <a href="dashboard.php?module=settings" class="nav-item <?php echo $current_module == 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i><span>Settings</span>
            </a>
            <?php endif; ?>
            <div class="sidebar-divider"></div>
            <a href="../index.php" class="nav-item"><i class="fas fa-globe"></i><span>View Website</span></a>
            <a href="../logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <h1><?php echo $page_title; ?></h1>
            </div>
            <div class="topbar-right">
                <!-- Notifications -->
                <div class="notifications-dropdown">
                    <button class="notification-btn" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <?php if ($total_notifications > 0): ?>
                            <span class="notification-badge"><?php echo $total_notifications; ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="notifications-menu" id="notificationsMenu">
                        <div class="notifications-header">
                            <h4>Notifications</h4>
                        </div>
                        <div class="notifications-list">
                            <?php if ($pending_count > 0): ?>
                                <a href="?module=bookings&status=pending" class="notification-item">
                                    <i class="fas fa-calendar-check text-warning"></i>
                                    <div><strong><?php echo $pending_count; ?> pending bookings</strong></div>
                                </a>
                            <?php endif; ?>
                            <?php if ($unread_count > 0): ?>
                                <a href="?module=contacts" class="notification-item">
                                    <i class="fas fa-envelope text-info"></i>
                                    <div><strong><?php echo $unread_count; ?> unread messages</strong></div>
                                </a>
                            <?php endif; ?>
                            <?php if ($total_notifications == 0): ?>
                                <p class="text-center p-3">No new notifications</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="user-menu">
                    <div class="user-trigger">
                        <img src="<?php echo $user_avatar; ?>" alt="Avatar" class="user-avatar">
                        <span class="user-name"><?php echo htmlspecialchars(explode(' ', $user_fullname)[0]); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu">
                        <div class="dropdown-header">
                            <div><strong><?php echo htmlspecialchars($user_fullname); ?></strong></div>
                            <small><?php echo $user_role_display; ?></small>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="../profile.php" class="dropdown-item"><i class="fas fa-user"></i>My Profile</a>
                        <a href="?module=settings" class="dropdown-item"><i class="fas fa-cog"></i>Settings</a>
                        <div class="dropdown-divider"></div>
                        <a href="../logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i>Logout</a>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="admin-content">