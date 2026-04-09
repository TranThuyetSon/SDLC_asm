<?php
/**
 * ARIA HOTEL - LOGIN PAGE
 * ============================================
 */

session_start();
require_once 'config/db.php';
require_once 'config/security.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$identifier = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

// Validate redirect to prevent open redirect
$allowed_redirects = ['index.php', 'rooms.php', 'profile.php', 'my-bookings.php', 'restaurant.php', 'services.php'];
if (!in_array($redirect, $allowed_redirects)) {
    $redirect = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Rate limiting
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!Security::checkRateLimit('login_' . $ip, 10, 900)) {
            $error = 'Too many login attempts. Please try again in 15 minutes.';
        } else {
            $identifier = Security::sanitize($_POST['identifier']);
            $password = $_POST['password'];
            
            $stmt = $conn->prepare("
                SELECT id, username, email, password_hash, role_id, is_active, full_name
                FROM users 
                WHERE (username = ? OR email = ?)
            ");
            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                if (!$row['is_active']) {
                    $error = 'Account is deactivated. Please contact support.';
                } elseif (password_verify($password, $row['password_hash'])) {
                    // Login successful
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['full_name'] = $row['full_name'];
                    $_SESSION['role_id'] = $row['role_id'];
                    
                    // Get role name
                    $roleStmt = $conn->prepare("SELECT name FROM roles WHERE id = ?");
                    $roleStmt->bind_param("i", $row['role_id']);
                    $roleStmt->execute();
                    $roleResult = $roleStmt->get_result();
                    if ($roleRow = $roleResult->fetch_assoc()) {
                        $_SESSION['user_role'] = $roleRow['name'];
                    }
                    $roleStmt->close();
                    
                    // Update last login
                    $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $updateStmt->bind_param("i", $row['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    // Clear rate limit
                    unset($_SESSION['rate_limit']['login_' . $ip]);
                    
                    header('Location: ' . $redirect);
                    exit();
                } else {
                    $error = 'Invalid password';
                }
            } else {
                $error = 'Account not found';
            }
            $stmt->close();
        }
    }
}

$csrf_token = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Aria Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
<div class="auth-container">
    <div class="auth-image">
        <div class="auth-logo">Aria Hotel</div>
        <div class="auth-image-text">
            <h2>Welcome Back</h2>
            <p>Sign in to continue your luxury experience</p>
        </div>
    </div>
    
    <div class="auth-form">
        <div class="auth-form-container">
            <div class="auth-form-header">
                <h1>Sign In</h1>
                <p>Enter your credentials to access your account</p>
            </div>
            
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                
                <div class="form-group">
                    <label class="form-label">Username or Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="identifier" class="form-control" 
                               value="<?php echo htmlspecialchars($identifier); ?>" 
                               placeholder="Enter your username or email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" class="form-control" 
                               placeholder="Enter your password" required>
                    </div>
                </div>
                
                <div class="auth-form-options">
                    <label class="form-check">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                
                <div class="auth-form-footer">
                    <p>Don't have an account? <a href="register.php">Sign Up</a></p>
                    <p style="margin-top: 1rem;">
                        <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>