<?php
/**
 * ADMIN SIDEBAR
 * ============================================
 */

$current_page = basename($_SERVER['PHP_SELF']);
$current_module = $_GET['module'] ?? 'overview';

$user_role = $_SESSION['user_role'] ?? '';
$is_admin = ($user_role === 'admin');
$is_receptionist = ($user_role === 'receptionist');

// Get pending counts with error handling
$pending_result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
$pending_count = $pending_result ? $pending_result->fetch_assoc()['count'] : 0;

$unread_result = $conn->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'unread'");
$unread_count = $unread_result ? $unread_result->fetch_assoc()['count'] : 0;
?>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <h2>Aria Hotel</h2>
        <p>Admin Panel v2.0</p>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Dashboard Overview -->
        <a href="dashboard.php?module=overview" class="nav-item <?php echo $current_module == 'overview' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        
        <!-- Bookings -->
        <a href="dashboard.php?module=bookings" class="nav-item <?php echo $current_module == 'bookings' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i>
            <span>Bookings</span>
            <?php if ($pending_count > 0): ?>
                <span class="badge badge-warning"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>
        
        <!-- Rooms -->
        <a href="dashboard.php?module=rooms" class="nav-item <?php echo $current_module == 'rooms' ? 'active' : ''; ?>">
            <i class="fas fa-bed"></i>
            <span>Rooms</span>
        </a>
        
        <!-- Room Types -->
        <a href="dashboard.php?module=room-types" class="nav-item <?php echo $current_module == 'room-types' ? 'active' : ''; ?>">
            <i class="fas fa-layer-group"></i>
            <span>Room Types</span>
        </a>
        
        <?php if ($is_admin): ?>
        <!-- Users -->
        <a href="dashboard.php?module=users" class="nav-item <?php echo $current_module == 'users' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Users</span>
        </a>
        <?php endif; ?>
        
        <!-- Customers -->
        <a href="dashboard.php?module=customers" class="nav-item <?php echo $current_module == 'customers' ? 'active' : ''; ?>">
            <i class="fas fa-user-friends"></i>
            <span>Customers</span>
        </a>
        
        <!-- Payments -->
        <a href="dashboard.php?module=payments" class="nav-item <?php echo $current_module == 'payments' ? 'active' : ''; ?>">
            <i class="fas fa-credit-card"></i>
            <span>Payments</span>
        </a>
        
        <!-- Services -->
        <a href="dashboard.php?module=services" class="nav-item <?php echo $current_module == 'services' ? 'active' : ''; ?>">
            <i class="fas fa-concierge-bell"></i>
            <span>Services</span>
        </a>
        
        <!-- Restaurant -->
        <a href="dashboard.php?module=restaurant" class="nav-item <?php echo $current_module == 'restaurant' ? 'active' : ''; ?>">
            <i class="fas fa-utensils"></i>
            <span>Restaurant</span>
        </a>
        
        <!-- Menu Items -->
        <a href="dashboard.php?module=menu-items" class="nav-item <?php echo $current_module == 'menu-items' ? 'active' : ''; ?>">
            <i class="fas fa-list-ul"></i>
            <span>Menu Items</span>
        </a>
        
        <!-- Contacts -->
        <a href="dashboard.php?module=contacts" class="nav-item <?php echo $current_module == 'contacts' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i>
            <span>Contacts</span>
            <?php if ($unread_count > 0): ?>
                <span class="badge badge-danger"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </a>
        
        <?php if ($is_admin): ?>
        <!-- Reports -->
        <a href="dashboard.php?module=reports" class="nav-item <?php echo $current_module == 'reports' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </a>
        
        <!-- Audit Logs -->
        <a href="dashboard.php?module=audit-logs" class="nav-item <?php echo $current_module == 'audit-logs' ? 'active' : ''; ?>">
            <i class="fas fa-history"></i>
            <span>Audit Logs</span>
        </a>
        
        <!-- Settings -->
        <a href="dashboard.php?module=settings" class="nav-item <?php echo $current_module == 'settings' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <?php endif; ?>
        
        <div class="sidebar-divider"></div>
        
        <!-- Frontend Links -->
        <a href="../index.php" class="nav-item">
            <i class="fas fa-globe"></i>
            <span>View Website</span>
        </a>
        
        <a href="../logout.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>