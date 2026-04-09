<?php
/**
 * PAYMENT MANAGEMENT MODULE
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
    $where .= " AND p.payment_status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Get total
$count_sql = "SELECT COUNT(*) as total FROM payments p $where";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);
$stmt->close();

// Get payments
$sql = "SELECT p.*, b.booking_code, b.total_price as booking_total,
               u.full_name as customer_name
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN customers c ON b.customer_id = c.id
        JOIN users u ON c.user_id = u.id
        $where
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$payments = $stmt->get_result();
$stmt->close();

// Get summary
$summary_result = $conn->query("
    SELECT 
        COUNT(*) as total_payments,
        COALESCE(SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END), 0) as total_completed,
        COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END), 0) as total_pending
    FROM payments
");
$summary = $summary_result->fetch_assoc();
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-credit-card"></i></div>
        <div class="stat-number"><?php echo $summary['total_payments']; ?></div>
        <div class="stat-label">Total Payments</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-number"><?php echo formatCurrency($summary['total_completed']); ?></div>
        <div class="stat-label">Completed Payments</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-number"><?php echo formatCurrency($summary['total_pending']); ?></div>
        <div class="stat-label">Pending Payments</div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Payment Transactions</h3>
    </div>
    <div class="admin-card-body">
        <form method="GET" class="filter-form mb-3">
            <input type="hidden" name="module" value="payments">
            <div class="filter-row">
                <div class="filter-group">
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Payments</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo $status_filter == 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="refunded" <?php echo $status_filter == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?module=payments" class="btn btn-secondary">Reset</a>
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
                        <th>Booking Code</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Transaction Code</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($payment = $payments->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $payment['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($payment['booking_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                        <td><?php echo formatCurrency($payment['amount']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                        <td><?php echo getStatusBadge($payment['payment_status'], 'payment'); ?></td>
                        <td><?php echo htmlspecialchars($payment['transaction_code'] ?: '-'); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?module=payments&status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>" 
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