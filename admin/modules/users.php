<?php
/**
 * USER MANAGEMENT MODULE (Admin Only)
 * ============================================
 */

if (!isAdmin()) {
    echo '<div class="alert alert-error">Access denied. Admin privileges required.</div>';
    return;
}

$role_filter = $_GET['role'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where = ["1=1"];
$params = [];
$types = "";

if ($role_filter !== 'all') {
    $where[] = "u.role_id = ?";
    $params[] = (int)$role_filter;
    $types .= "i";
}

if ($status_filter !== 'all') {
    $where[] = "u.is_active = ?";
    $params[] = $status_filter == 'active' ? 1 : 0;
    $types .= "i";
}

if (!empty($search)) {
    $where[] = "(u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$where_clause = implode(" AND ", $where);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM users u WHERE $where_clause";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users / $per_page);
$stmt->close();

// Get users
$users_sql = "SELECT u.*, r.name as role_name,
                     (SELECT COUNT(*) FROM bookings b JOIN customers c ON b.customer_id = c.id WHERE c.user_id = u.id) as booking_count,
                     (SELECT COALESCE(SUM(b.total_price), 0) FROM bookings b JOIN customers c ON b.customer_id = c.id WHERE c.user_id = u.id) as total_spent
              FROM users u
              JOIN roles r ON u.role_id = r.id
              WHERE $where_clause
              ORDER BY u.created_at DESC
              LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($users_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result();
$stmt->close();

// Get roles for filter
$roles = $conn->query("SELECT id, name FROM roles ORDER BY id");

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action == 'add_user') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $full_name = trim($_POST['full_name']);
            $phone = trim($_POST['phone']);
            $role_id = (int)$_POST['role_id'];
            
            $errors = [];
            
            if (!Security::validateUsername($username)) {
                $errors[] = 'Invalid username format.';
            }
            if (!Security::validateEmail($email)) {
                $errors[] = 'Invalid email address.';
            }
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            }
            
            if (empty($errors)) {
                $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $check->bind_param("ss", $username, $email);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $error = 'Username or email already exists.';
                } else {
                    $hashed = Security::hashPassword($password);
                    
                    $conn->begin_transaction();
                    try {
                        $insert = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, phone, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
                        $insert->bind_param("sssssi", $username, $email, $hashed, $full_name, $phone, $role_id);
                        $insert->execute();
                        $user_id = $insert->insert_id;
                        
                        if ($role_id == ROLE_CUSTOMER) {
                            $cust = $conn->prepare("INSERT INTO customers (user_id, loyalty_points) VALUES (?, 0)");
                            $cust->bind_param("i", $user_id);
                            $cust->execute();
                            $cust->close();
                        }
                        
                        $conn->commit();
                        logActivity($conn, $_SESSION['user_id'], "Created user: $username", 'users', $user_id);
                        $success = 'User created successfully.';
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = 'Failed to create user: ' . $e->getMessage();
                    }
                }
                $check->close();
            } else {
                $error = implode('<br>', $errors);
            }
        } elseif ($action == 'edit_user') {
            $user_id = (int)$_POST['user_id'];
            $full_name = trim($_POST['full_name']);
            $phone = trim($_POST['phone']);
            $role_id = (int)$_POST['role_id'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($user_id == $_SESSION['user_id'] && !$is_active) {
                $error = 'You cannot deactivate your own account.';
            } else {
                $update = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, role_id = ?, is_active = ? WHERE id = ?");
                $update->bind_param("ssiii", $full_name, $phone, $role_id, $is_active, $user_id);
                
                if ($update->execute()) {
                    logActivity($conn, $_SESSION['user_id'], "Updated user #$user_id", 'users', $user_id);
                    $success = 'User updated successfully.';
                } else {
                    $error = 'Failed to update user.';
                }
                $update->close();
            }
        } elseif ($action == 'reset_password') {
            $user_id = (int)$_POST['user_id'];
            $new_password = $_POST['new_password'];
            
            if (strlen($new_password) < 8) {
                $error = 'Password must be at least 8 characters.';
            } else {
                $hashed = Security::hashPassword($new_password);
                $update = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $update->bind_param("si", $hashed, $user_id);
                
                if ($update->execute()) {
                    logActivity($conn, $_SESSION['user_id'], "Reset password for user #$user_id", 'users', $user_id);
                    $success = 'Password reset successfully.';
                } else {
                    $error = 'Failed to reset password.';
                }
                $update->close();
            }
        } elseif ($action == 'delete_user') {
            $user_id = (int)$_POST['user_id'];
            
            if ($user_id == $_SESSION['user_id']) {
                $error = 'You cannot delete your own account.';
            } else {
                $check = $conn->prepare("SELECT COUNT(*) as count FROM bookings b JOIN customers c ON b.customer_id = c.id WHERE c.user_id = ?");
                $check->bind_param("i", $user_id);
                $check->execute();
                $has_bookings = $check->get_result()->fetch_assoc()['count'] > 0;
                $check->close();
                
                if ($has_bookings) {
                    $error = 'Cannot delete user with existing bookings. Deactivate instead.';
                } else {
                    $conn->begin_transaction();
                    try {
                        $conn->query("DELETE FROM customers WHERE user_id = $user_id");
                        $delete = $conn->prepare("DELETE FROM users WHERE id = ?");
                        $delete->bind_param("i", $user_id);
                        $delete->execute();
                        $conn->commit();
                        logActivity($conn, $_SESSION['user_id'], "Deleted user #$user_id", 'users', $user_id);
                        $success = 'User deleted successfully.';
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = 'Failed to delete user.';
                    }
                }
            }
        }
    }
}

$csrf_token = Security::generateCSRFToken();
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Filters -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>User Management</h3>
        <button class="btn btn-primary btn-sm" onclick="openModal('addUserModal')">
            <i class="fas fa-plus"></i> Add User
        </button>
    </div>
    <div class="admin-card-body">
        <form method="GET" class="filter-form">
            <input type="hidden" name="module" value="users">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Role</label>
                    <select name="role" class="form-select">
                        <option value="all" <?php echo $role_filter == 'all' ? 'selected' : ''; ?>>All Roles</option>
                        <?php while ($role = $roles->fetch_assoc()): ?>
                            <option value="<?php echo $role['id']; ?>" <?php echo $role_filter == $role['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($role['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="filter-group flex-1">
                    <label>Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Username, email or name..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?module=users" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="admin-card">
    <div class="admin-card-body p-0">
        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Bookings</th>
                        <th>Total Spent</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $user['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></td>
                        <td><span class="role-badge role-<?php echo strtolower($user['role_name']); ?>"><?php echo $user['role_name']; ?></span></td>
                        <td>
                            <?php if ($user['is_active']): ?>
                                <span class="status-badge status-success">Active</span>
                            <?php else: ?>
                                <span class="status-badge status-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $user['booking_count']; ?></td>
                        <td><?php echo formatCurrency($user['total_spent']); ?></td>
                        <td><?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon btn-outline-primary" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>', '<?php echo htmlspecialchars($user['phone']); ?>', <?php echo $user['role_id']; ?>, <?php echo $user['is_active']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon btn-outline-warning" onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                    <i class="fas fa-key"></i>
                                </button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <button class="btn-icon btn-outline-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?module=users&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>" 
                   class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Add New User</h3>
            <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="add_user">
            
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role_id" class="form-select" required>
                            <?php 
                            $roles->data_seek(0);
                            while ($role = $roles->fetch_assoc()): ?>
                                <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit User</h3>
            <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" id="edit_full_name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" id="edit_phone" class="form-control">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role_id" id="edit_role_id" class="form-select">
                        <?php 
                        $roles->data_seek(0);
                        while ($role = $roles->fetch_assoc()): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="checkbox">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                        <span>Active Account</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reset Password</h3>
            <button class="modal-close" onclick="closeModal('resetPasswordModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="reset_user_id">
            
            <div class="modal-body">
                <p>Resetting password for: <strong id="reset_username_display"></strong></p>
                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('resetPasswordModal')">Cancel</button>
                <button type="submit" class="btn btn-warning">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete User Form -->
<form id="deleteUserForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="action" value="delete_user">
    <input type="hidden" name="user_id" id="delete_user_id">
</form>

<script>
function editUser(id, fullName, phone, roleId, isActive) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_full_name').value = fullName;
    document.getElementById('edit_phone').value = phone;
    document.getElementById('edit_role_id').value = roleId;
    document.getElementById('edit_is_active').checked = isActive == 1;
    openModal('editUserModal');
}

function resetPassword(id, username) {
    document.getElementById('reset_user_id').value = id;
    document.getElementById('reset_username_display').textContent = username;
    openModal('resetPasswordModal');
}

function deleteUser(id, username) {
    if (confirm('Are you sure you want to delete user "' + username + '"?\nThis action cannot be undone.')) {
        document.getElementById('delete_user_id').value = id;
        document.getElementById('deleteUserForm').submit();
    }
}
</script>

<style>
.modal-lg {
    max-width: 600px;
}
.role-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 9999px;
}
.role-badge.role-admin {
    background: #dbeafe;
    color: #1e40af;
}
.role-badge.role-receptionist {
    background: #d1fae5;
    color: #065f46;
}
.role-badge.role-customer {
    background: #f3e8ff;
    color: #6b21a8;
}
.status-badge.status-danger {
    background: #fee2e2;
    color: #dc2626;
}
.btn-warning {
    background: #d97706;
    color: white;
}
.btn-warning:hover {
    background: #b45309;
}
</style>