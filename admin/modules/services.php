<?php
/**
 * SERVICES MANAGEMENT MODULE
 * ============================================
 */

$services = $conn->query("SELECT * FROM services ORDER BY service_name");
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Services Management</h3>
        <button class="btn btn-primary btn-sm" onclick="showComingSoon()">
            <i class="fas fa-plus"></i> Add Service
        </button>
    </div>
    <div class="admin-card-body p-0">
        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Service Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($service = $services->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $service['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($service['service_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars(substr($service['description'], 0, 50)); ?>...</td>
                        <td><?php echo formatCurrency($service['price']); ?></td>
                        <td>
                            <?php if ($service['is_active']): ?>
                                <span class="status-badge status-success">Active</span>
                            <?php else: ?>
                                <span class="status-badge status-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon btn-outline-primary" onclick="showComingSoon()">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon btn-outline-warning" onclick="showComingSoon()">
                                    <i class="fas fa-toggle-on"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function showComingSoon() {
    showToast('This feature is coming soon!', 'info');
}
</script>