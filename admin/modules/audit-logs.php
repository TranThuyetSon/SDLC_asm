<?php
/**
 * AUDIT LOGS MODULE
 * ============================================
 */

if (!isAdmin()) {
    echo '<div class="alert alert-error">Access denied. Admin privileges required.</div>';
    return;
}

$action_filter = $_GET['action'] ?? '';
$user_filter = $_GET['user'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

$where = ["1=1"];
$params = [];
$types = "";

if (!empty($action_filter)) {
    $where[] = "a.action LIKE ?";
    $params[] = "%$action_filter%";
    $types .= "s";
}

if (!empty($user_filter)) {
    $where[] = "u.username LIKE ? OR u.full_name LIKE ?";
    $params[] = "%$user_filter%";
    $params[] = "%$user_filter%";
    $types .= "ss";
}

$where_clause = implode(" AND ", $where);

// Get total
$count_sql = "SELECT COUNT(*) as total FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id WHERE $where_clause";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);
$stmt->close();

// Get logs
$sql = "SELECT a.*, u.username, u.full_name 
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE $where_clause
        ORDER BY a.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$logs = $stmt->get_result();
$stmt->close();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Audit Logs</h3>
    </div>
    <div class="admin-card-body">
        <form method="GET" class="filter-form mb-3">
            <input type="hidden" name="module" value="audit-logs">
            <div class="filter-row">
                <div class="filter-group">
                    <input type="text" name="action" class="form-control" placeholder="Filter by action..." value="<?php echo htmlspecialchars($action_filter); ?>">
                </div>
                <div class="filter-group">
                    <input type="text" name="user" class="form-control" placeholder="Filter by user..." value="<?php echo htmlspecialchars($user_filter); ?>">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?module=audit-logs" class="btn btn-secondary">Reset</a>
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
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record ID</th>
                        <th>IP Address</th>
                        <th>Date/Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($log = $logs->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $log['id']; ?></td>
                        <td>
                            <?php if ($log['username']): ?>
                                <strong><?php echo htmlspecialchars($log['full_name'] ?: $log['username']); ?></strong>
                            <?php else: ?>
                                <em>System</em>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                        <td><?php echo htmlspecialchars($log['table_name'] ?: '-'); ?></td>
                        <td><?php echo $log['record_id'] ? '#' . $log['record_id'] : '-'; ?></td>
                        <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?module=audit-logs&action=<?php echo urlencode($action_filter); ?>&user=<?php echo urlencode($user_filter); ?>&page=<?php echo $i; ?>" 
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
</style>