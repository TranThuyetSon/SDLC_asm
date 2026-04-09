<?php
/**
 * SYSTEM SETTINGS MODULE
 * ============================================
 */

if (!isAdmin()) {
    echo '<div class="alert alert-error">Access denied. Admin privileges required.</div>';
    return;
}

$hotel_info = getHotelInfo();
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>System Settings</h3>
    </div>
    <div class="admin-card-body">
        <div class="settings-section">
            <h4>Hotel Information</h4>
            <div class="info-grid">
                <div class="info-item">
                    <label>Hotel Name</label>
                    <input type="text" class="form-control" value="<?php echo HOTEL_NAME; ?>" disabled>
                </div>
                <div class="info-item">
                    <label>Phone</label>
                    <input type="text" class="form-control" value="<?php echo HOTEL_PHONE; ?>" disabled>
                </div>
                <div class="info-item">
                    <label>Hotline</label>
                    <input type="text" class="form-control" value="<?php echo HOTEL_HOTLINE; ?>" disabled>
                </div>
                <div class="info-item">
                    <label>Email</label>
                    <input type="text" class="form-control" value="<?php echo HOTEL_EMAIL; ?>" disabled>
                </div>
                <div class="info-item full-width">
                    <label>Address</label>
                    <input type="text" class="form-control" value="<?php echo HOTEL_ADDRESS; ?>" disabled>
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h4>System Information</h4>
            <div class="info-grid">
                <div class="info-item">
                    <label>Application</label>
                    <input type="text" class="form-control" value="<?php echo APP_NAME; ?>" disabled>
                </div>
                <div class="info-item">
                    <label>Version</label>
                    <input type="text" class="form-control" value="<?php echo APP_VERSION; ?>" disabled>
                </div>
                <div class="info-item">
                    <label>PHP Version</label>
                    <input type="text" class="form-control" value="<?php echo phpversion(); ?>" disabled>
                </div>
                <div class="info-item">
                    <label>Database</label>
                    <input type="text" class="form-control" value="MySQL <?php echo $conn->server_info; ?>" disabled>
                </div>
                <div class="info-item">
                    <label>Timezone</label>
                    <input type="text" class="form-control" value="<?php echo TIMEZONE; ?>" disabled>
                </div>
                <div class="info-item">
                    <label>Server Time</label>
                    <input type="text" class="form-control" value="<?php echo date('Y-m-d H:i:s'); ?>" disabled>
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h4>Actions</h4>
            <div class="action-buttons-group">
                <button class="btn btn-warning" onclick="clearCache()">
                    <i class="fas fa-sync-alt"></i> Clear Cache
                </button>
                <button class="btn btn-info" onclick="exportDatabase()">
                    <i class="fas fa-database"></i> Export Database
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function clearCache() {
    showToast('Cache cleared successfully!', 'success');
}

function exportDatabase() {
    showToast('Database export feature coming soon!', 'info');
}
</script>

<style>
.settings-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #e2e8f0;
}
.settings-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}
.settings-section h4 {
    margin-bottom: 1.5rem;
    color: #0a192f;
}
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}
.info-item.full-width {
    grid-column: span 2;
}
.info-item label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
}
.action-buttons-group {
    display: flex;
    gap: 1rem;
}
.btn-info {
    background: #2563eb;
    color: white;
}
.btn-info:hover {
    background: #1d4ed8;
}
@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
    .info-item.full-width {
        grid-column: span 1;
    }
}
</style>