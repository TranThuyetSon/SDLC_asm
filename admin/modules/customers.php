<?php
/**
 * CUSTOMER MANAGEMENT MODULE
 * ============================================
 */

// Get customers list
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;
$search = $_GET['search'] ?? '';

$where = "WHERE u.role_id = 3";
$params = [];
$types = "";

if (!empty($search)) {
    $where .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
    $types = "sss";
}

// Get total
$count_sql = "SELECT COUNT(*) as total FROM users u $where";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);
$stmt->close();

// Get customers
$sql = "SELECT u.*, c.id as customer_id, c.loyalty_points,
               (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id) as booking_count,
               (SELECT COALESCE(SUM(b.total_price), 0) FROM bookings b WHERE b.customer_id = c.id) as total_spent
        FROM users u
        JOIN customers c ON u.id = c.user_id
        $where
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$customers = $stmt->get_result();
$stmt->close();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Customer Management</h3>
    </div>
    <div class="admin-card-body">
        <form method="GET" class="filter-form mb-3">
            <input type="hidden" name="module" value="customers">
            <div class="filter-row">
                <div class="filter-group flex-1">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, email or phone..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="?module=customers" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-body p-0">
        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Bookings</th>
                        <th>Total Spent</th>
                        <th>Loyalty Points</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($customer = $customers->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $customer['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($customer['full_name'] ?: $customer['username']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                        <td><?php echo htmlspecialchars($customer['phone'] ?: '-'); ?></td>
                        <td><?php echo $customer['booking_count']; ?></td>
                        <td><?php echo formatCurrency($customer['total_spent']); ?></td>
                        <td><?php echo number_format($customer['loyalty_points']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon btn-outline-primary" onclick="viewCustomer(<?php echo $customer['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?module=customers&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>" 
                   class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function viewCustomer(id) {
    showToast('Customer details coming soon!', 'info');
}
</script>

<style>
.mb-3 { margin-bottom: 1rem; }
</style>