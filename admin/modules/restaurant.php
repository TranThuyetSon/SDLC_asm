<?php
/**
 * RESTAURANT RESERVATIONS MODULE
 * ============================================
 */

$status_filter = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = "WHERE 1=1";
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $where .= " AND tr.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Get total
$count_sql = "SELECT COUNT(*) as total FROM table_reservations tr $where";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);
$stmt->close();

// Get reservations
$sql = "SELECT tr.*, rt.table_number, rt.capacity, rt.location,
               u.full_name as customer_name, u.email, u.phone
        FROM table_reservations tr
        JOIN restaurant_tables rt ON tr.table_id = rt.id
        JOIN users u ON tr.user_id = u.id
        $where
        ORDER BY tr.reservation_date DESC, tr.reservation_time DESC
        LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$reservations = $stmt->get_result();
$stmt->close();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    if (Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $reservation_id = (int)$_POST['reservation_id'];
        $new_status = $_POST['new_status'];
        
        $update = $conn->prepare("UPDATE table_reservations SET status = ? WHERE id = ?");
        $update->bind_param("si", $new_status, $reservation_id);
        if ($update->execute()) {
            $success = 'Status updated successfully.';
        }
        $update->close();
    }
}

$csrf_token = Security::generateCSRFToken();
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Restaurant Reservations</h3>
    </div>
    <div class="admin-card-body">
        <form method="GET" class="filter-form mb-3">
            <input type="hidden" name="module" value="restaurant">
            <div class="filter-row">
                <div class="filter-group">
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Reservations</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?module=restaurant" class="btn btn-secondary">Reset</a>
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
                        <th>Code</th>
                        <th>Customer</th>
                        <th>Table</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Guests</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($res = $reservations->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($res['reservation_code']); ?></strong></td>
                        <td>
                            <div><?php echo htmlspecialchars($res['customer_name']); ?></div>
                            <small><?php echo htmlspecialchars($res['email']); ?></small>
                        </td>
                        <td>Table <?php echo htmlspecialchars($res['table_number']); ?> (<?php echo $res['capacity']; ?> seats)</td>
                        <td><?php echo date('d/m/Y', strtotime($res['reservation_date'])); ?></td>
                        <td><?php echo substr($res['reservation_time'], 0, 5); ?></td>
                        <td><?php echo $res['number_of_guests']; ?></td>
                        <td><?php echo getStatusBadge($res['status'], 'booking'); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                <select name="new_status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 130px;">
                                    <option value="">Update</option>
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirm</option>
                                    <option value="completed">Complete</option>
                                    <option value="cancelled">Cancel</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?module=restaurant&status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>" 
                   class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.mb-3 { margin-bottom: 1rem; }
.form-select-sm {
    width: auto;
    padding: 0.25rem 2rem 0.25rem 0.75rem;
    font-size: 0.85rem;
}
</style>