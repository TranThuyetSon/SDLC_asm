<?php
/**
 * ARIA HOTEL - MAIN HEADER
 * ============================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/security.php';

// Get current user
$is_logged_in = isset($_SESSION['user_id']);
$user_fullname = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? '';
$user_email = $_SESSION['email'] ?? '';
$user_avatar = '';

if ($is_logged_in) {
    $initials = strtoupper(substr($user_fullname, 0, 1));
    $user_avatar = "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&background=0a192f&color=ffffff&size=128&bold=true";
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Aria Hotel - Luxury accommodation in Ho Chi Minh City">
    <meta name="theme-color" content="#0a192f">
    
    <title><?php echo isset($page_title) ? $page_title : 'Aria Hotel | Luxury Stay'; ?></title>
    
    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Extra CSS (if any) -->
    <?php if (isset($extra_css)): ?>
        <?php echo $extra_css; ?>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">Aria Hotel</a>
            
            <button class="mobile-menu-btn" aria-label="Menu">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="nav-links">
                <a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Home</a>
                <a href="rooms.php" class="nav-link <?php echo $current_page == 'rooms.php' ? 'active' : ''; ?>">Rooms</a>
                <a href="restaurant.php" class="nav-link <?php echo $current_page == 'restaurant.php' ? 'active' : ''; ?>">Restaurant</a>
                <a href="services.php" class="nav-link <?php echo $current_page == 'services.php' ? 'active' : ''; ?>">Services</a>
                <a href="contact.php" class="nav-link <?php echo $current_page == 'contact.php' ? 'active' : ''; ?>">Contact</a>
                
                <?php if ($is_logged_in && in_array($user_role, ['admin', 'receptionist'])): ?>
                    <a href="admin/dashboard.php" class="nav-link">Dashboard</a>
                <?php endif; ?>
                
                <?php if ($is_logged_in): ?>
                    <div class="user-menu">
                        <div class="user-trigger">
                            <img src="<?php echo $user_avatar; ?>" alt="Avatar" class="user-avatar" loading="lazy">
                            <span class="user-name"><?php echo htmlspecialchars(explode(' ', $user_fullname)[0]); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="dropdown-menu">
                            <div class="dropdown-header">
                                <div class="user-fullname"><?php echo htmlspecialchars($user_fullname); ?></div>
                                <div class="user-email"><?php echo htmlspecialchars($user_email); ?></div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>My Profile</span>
                            </a>
                            <a href="my-bookings.php" class="dropdown-item">
                                <i class="fas fa-calendar-check"></i>
                                <span>My Bookings</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="login.php" class="btn btn-outline-light">Sign In</a>
                        <a href="register.php" class="btn btn-primary">Sign Up</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main>