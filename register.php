<?php
/**
 * ARIA HOTEL - REGISTER PAGE
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
$success = '';
$username = '';
$email = '';
$full_name = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Rate limiting
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!Security::checkRateLimit('register_' . $ip, 5, 3600)) {
            $error = 'Too many registration attempts. Please try again later.';
        } else {
            $username = Security::sanitize($_POST['username']);
            $email = Security::sanitize($_POST['email']);
            $full_name = Security::sanitize($_POST['full_name']);
            $phone = Security::sanitize($_POST['phone']);
            $password = $_POST['password'];
            $confirm = $_POST['confirm_password'];
            
            // Validation
            if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
                $error = 'Username must be 3-50 characters (letters, numbers, underscore)';
            } elseif (!Security::validateEmail($email)) {
                $error = 'Invalid email address';
            } elseif (!Security::validatePhone($phone)) {
                $error = 'Phone number must be 10-11 digits';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters';
            } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
                $error = 'Password must contain at least one uppercase letter and one number';
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match';
            } else {
                // Check if user exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows > 0) {
                    $error = 'Username or email already exists';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $role_id = 3;
                    
                    $conn->begin_transaction();
                    
                    try {
                        $stmt = $conn->prepare("
                            INSERT INTO users (username, email, password_hash, full_name, phone, role_id, is_active) 
                            VALUES (?, ?, ?, ?, ?, ?, 1)
                        ");
                        $stmt->bind_param("sssssi", $username, $email, $hashed_password, $full_name, $phone, $role_id);
                        
                        if (!$stmt->execute()) {
                            throw new Exception('Could not create account');
                        }
                        
                        $user_id = $conn->insert_id;
                        
                        $custStmt = $conn->prepare("
                            INSERT INTO customers (user_id, nationality, address, loyalty_points) 
                            VALUES (?, 'Vietnam', '', 0)
                        ");
                        $custStmt->bind_param("i", $user_id);
                        $custStmt->execute();
                        $custStmt->close();
                        
                        $conn->commit();
                        
                        $success = 'Registration successful! You can now login.';
                        $username = $email = $full_name = $phone = '';
                        
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = 'Registration failed. Please try again.';
                    }
                }
                $stmt->close();
            }
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
    <title>Sign Up - Aria Hotel</title>
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
            <h2>Join Us</h2>
            <p>Create your account and start your luxury journey</p>
        </div>
    </div>
    
    <div class="auth-form">
        <div class="auth-form-container">
            <div class="auth-form-header">
                <h1>Sign Up</h1>
                <p>Create your account to get started</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <a href="login.php" style="margin-left: 10px; font-weight: 600;">Login now →</a>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label class="form-label">Username <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" class="form-control" 
                               value="<?php echo htmlspecialchars($username); ?>" 
                               placeholder="Choose a username" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Address <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($email); ?>" 
                               placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Full Name <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-user-circle"></i>
                        <input type="text" name="full_name" class="form-control" 
                               value="<?php echo htmlspecialchars($full_name); ?>" 
                               placeholder="Enter your full name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone Number <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone"></i>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($phone); ?>" 
                               placeholder="Enter your phone number" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" class="form-control" 
                               placeholder="Minimum 8 characters" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm Password <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" class="form-control" 
                               placeholder="Re-enter your password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
                
                <div class="auth-form-footer">
                    <p>Already have an account? <a href="login.php">Sign In</a></p>
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