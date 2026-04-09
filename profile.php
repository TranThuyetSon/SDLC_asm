<?php
/**
 * ARIA HOTEL - PROFILE PAGE
 * ============================================
 */

$page_title = 'My Profile - Aria Hotel';
require_once 'includes/header.php';

// Kiểm tra đăng nhập
if (!$is_logged_in) {
    header('Location: login.php?redirect=profile.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Lấy thông tin user
$stmt = $conn->prepare("
    SELECT u.id, u.username, u.email, u.full_name, u.phone, u.password_hash,
           u.created_at, u.last_login, r.name as role_name,
           c.id as customer_id, c.identity_card, c.passport_number, 
           c.nationality, c.address, c.loyalty_points
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    LEFT JOIN customers c ON u.id = c.user_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$customer_id = $user['customer_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token.';
    } elseif (isset($_POST['update_profile'])) {
        $full_name = Security::sanitize($_POST['full_name']);
        $phone = Security::sanitize($_POST['phone']);
        $address = Security::sanitize($_POST['address']);
        $nationality = Security::sanitize($_POST['nationality']);
        $identity_card = Security::sanitize($_POST['identity_card']);
        $passport_number = Security::sanitize($_POST['passport_number']);
        
        if (empty($full_name)) {
            $error_message = 'Full name is required.';
        } else {
            $conn->begin_transaction();
            
            try {
                $update_user = $conn->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
                $update_user->bind_param("ssi", $full_name, $phone, $user_id);
                $update_user->execute();
                $update_user->close();
                
                if ($customer_id) {
                    $update_customer = $conn->prepare("UPDATE customers SET address = ?, nationality = ?, identity_card = ?, passport_number = ? WHERE id = ?");
                    $update_customer->bind_param("ssssi", $address, $nationality, $identity_card, $passport_number, $customer_id);
                    $update_customer->execute();
                    $update_customer->close();
                } else {
                    $insert_customer = $conn->prepare("INSERT INTO customers (user_id, address, nationality, identity_card, passport_number, loyalty_points) VALUES (?, ?, ?, ?, ?, 0)");
                    $insert_customer->bind_param("issss", $user_id, $address, $nationality, $identity_card, $passport_number);
                    $insert_customer->execute();
                    $customer_id = $conn->insert_id;
                    $insert_customer->close();
                }
                
                $conn->commit();
                $success_message = 'Profile updated successfully!';
                
                // Refresh user data
                $stmt = $conn->prepare("
                    SELECT u.full_name, u.phone, c.address, c.nationality, c.identity_card, c.passport_number
                    FROM users u
                    LEFT JOIN customers c ON u.id = c.user_id
                    WHERE u.id = ?
                ");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $updated = $stmt->get_result()->fetch_assoc();
                $user = array_merge($user, $updated);
                $stmt->close();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = 'Failed to update profile.';
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'Please fill in all password fields.';
        } elseif (!password_verify($current_password, $user['password_hash'])) {
            $error_message = 'Current password is incorrect.';
        } elseif (strlen($new_password) < 8) {
            $error_message = 'New password must be at least 8 characters.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New passwords do not match.';
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pass = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $update_pass->bind_param("si", $new_hash, $user_id);
            
            if ($update_pass->execute()) {
                $success_message = 'Password changed successfully!';
            } else {
                $error_message = 'Failed to change password.';
            }
            $update_pass->close();
        }
    }
}

$csrf_token = Security::generateCSRFToken();
$user_avatar = "https://ui-avatars.com/api/?name=" . urlencode(strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1))) . "&background=0a192f&color=ffffff&size=128&bold=true";
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1>My Profile</h1>
        <p>Manage your personal information and account settings</p>
    </div>
</section>

<!-- Messages -->
<?php if ($success_message): ?>
    <div class="container">
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="container">
        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
    </div>
<?php endif; ?>

<!-- Profile Section -->
<section class="profile-section">
    <div class="container">
        <div class="profile-grid">
            <div class="profile-sidebar">
                <div class="profile-avatar">
                    <img src="<?php echo $user_avatar; ?>" alt="Avatar">
                </div>
                <h3><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h3>
                <div class="profile-role"><?php echo ucfirst($user['role_name'] ?? 'Customer'); ?></div>
                <div class="profile-member-since">
                    Member since: <?php echo date('M Y', strtotime($user['created_at'])); ?>
                </div>
                
                <?php if ($customer_id): ?>
                <div class="loyalty-card">
                    <div class="loyalty-points"><?php echo number_format($user['loyalty_points'] ?? 0); ?></div>
                    <div class="loyalty-label">Loyalty Points</div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="profile-main">
                <div class="profile-tabs">
                    <button class="tab-btn active" data-tab="info">Personal Information</button>
                    <button class="tab-btn" data-tab="password">Change Password</button>
                </div>
                
                <div class="tab-content active" id="tab-info">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Full Name <span class="required">*</span></label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-textarea" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Nationality</label>
                                <input type="text" name="nationality" class="form-control" value="<?php echo htmlspecialchars($user['nationality'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Identity Card</label>
                                <input type="text" name="identity_card" class="form-control" value="<?php echo htmlspecialchars($user['identity_card'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Passport Number</label>
                            <input type="text" name="passport_number" class="form-control" value="<?php echo htmlspecialchars($user['passport_number'] ?? ''); ?>">
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
                
                <div class="tab-content" id="tab-password">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Minimum 8 characters" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter new password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$extra_css = '
<style>
.profile-section {
    padding: 3rem 0;
}
.profile-grid {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
}
.profile-sidebar {
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: 2rem;
    text-align: center;
    height: fit-content;
}
.profile-avatar img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 3px solid var(--primary);
    margin-bottom: 1rem;
}
.profile-sidebar h3 {
    font-size: 1.25rem;
    margin-bottom: 0.25rem;
}
.profile-role {
    font-size: 0.85rem;
    color: var(--gray-500);
    margin-bottom: 1rem;
}
.profile-member-since {
    font-size: 0.8rem;
    color: var(--gray-500);
    padding-top: 1rem;
    border-top: 1px solid var(--gray-200);
}
.loyalty-card {
    margin-top: 1.5rem;
    padding: 1rem;
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-md);
}
.loyalty-points {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
}
.loyalty-label {
    font-size: 0.75rem;
    color: var(--gray-500);
    text-transform: uppercase;
}
.profile-main {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    overflow: hidden;
}
.profile-tabs {
    display: flex;
    border-bottom: 1px solid var(--gray-200);
}
.tab-btn {
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    font-weight: 600;
    color: var(--gray-500);
    cursor: pointer;
    transition: var(--transition-base);
    border-bottom: 2px solid transparent;
}
.tab-btn.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}
.tab-content {
    display: none;
    padding: 2rem;
}
.tab-content.active {
    display: block;
}
@media (max-width: 768px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
}
</style>
';

$extra_js = '
<script>
document.querySelectorAll(".tab-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        const tabId = btn.dataset.tab;
        document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
        document.querySelectorAll(".tab-content").forEach(c => c.classList.remove("active"));
        btn.classList.add("active");
        document.getElementById("tab-" + tabId).classList.add("active");
    });
});
</script>
';

require_once 'includes/footer.php';
?>