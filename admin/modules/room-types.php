<?php
/**
 * ROOM TYPE MANAGEMENT MODULE
 * ============================================
 */
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Room Type Management</h3>
        <button class="btn btn-primary btn-sm" onclick="showComingSoon()">
            <i class="fas fa-plus"></i> Add Room Type
        </button>
    </div>
    <div class="admin-card-body">
        <div class="coming-soon">
            <i class="fas fa-layer-group" style="font-size: 4rem; color: var(--gray-300); margin-bottom: 1rem;"></i>
            <h4>Room Type Management</h4>
            <p>This module allows you to manage room types, base prices, and amenities.</p>
            <p><strong>Status:</strong> Coming Soon</p>
        </div>
    </div>
</div>

<script>
function showComingSoon() {
    showToast('This feature is coming soon!', 'info');
}
</script>