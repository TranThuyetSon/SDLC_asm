<?php
/**
 * MENU ITEMS MANAGEMENT MODULE
 * ============================================
 */

$category_filter = $_GET['category'] ?? 'all';

$where = "WHERE is_available = 1";
if ($category_filter !== 'all') {
    $where .= " AND category = '" . $conn->real_escape_string($category_filter) . "'";
}

$menu_items = $conn->query("SELECT * FROM menu_items $where ORDER BY category, name");
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Menu Items Management</h3>
        <button class="btn btn-primary btn-sm" onclick="showComingSoon()">
            <i class="fas fa-plus"></i> Add Menu Item
        </button>
    </div>
    <div class="admin-card-body">
        <form method="GET" class="filter-form mb-3">
            <input type="hidden" name="module" value="menu-items">
            <div class="filter-row">
                <div class="filter-group">
                    <select name="category" class="form-select">
                        <option value="all" <?php echo $category_filter == 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <option value="appetizer" <?php echo $category_filter == 'appetizer' ? 'selected' : ''; ?>>Appetizers</option>
                        <option value="main_course" <?php echo $category_filter == 'main_course' ? 'selected' : ''; ?>>Main Course</option>
                        <option value="dessert" <?php echo $category_filter == 'dessert' ? 'selected' : ''; ?>>Desserts</option>
                        <option value="beverage" <?php echo $category_filter == 'beverage' ? 'selected' : ''; ?>>Beverages</option>
                        <option value="breakfast" <?php echo $category_filter == 'breakfast' ? 'selected' : ''; ?>>Breakfast</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?module=menu-items" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="menu-items-grid">
    <?php while ($item = $menu_items->fetch_assoc()): ?>
    <div class="menu-item-card">
        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="menu-item-image">
        <div class="menu-item-content">
            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
            <span class="menu-category"><?php echo ucfirst(str_replace('_', ' ', $item['category'])); ?></span>
            <p class="menu-description"><?php echo htmlspecialchars(substr($item['description'], 0, 80)); ?>...</p>
            <div class="menu-price"><?php echo formatCurrency($item['price']); ?></div>
            <div class="menu-actions">
                <button class="btn btn-outline-primary btn-sm" onclick="showComingSoon()">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-outline-danger btn-sm" onclick="showComingSoon()">
                    <i class="fas fa-toggle-on"></i> Available
                </button>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<script>
function showComingSoon() {
    showToast('This feature is coming soon!', 'info');
}
</script>

<style>
.mb-3 { margin-bottom: 1rem; }
.menu-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}
.menu-item-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;
}
.menu-item-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.menu-item-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
}
.menu-item-content {
    padding: 1.25rem;
}
.menu-item-content h4 {
    margin-bottom: 0.25rem;
}
.menu-category {
    display: inline-block;
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
}
.menu-description {
    font-size: 0.85rem;
    color: #64748b;
    margin-bottom: 0.75rem;
}
.menu-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0a192f;
    margin-bottom: 1rem;
}
.menu-actions {
    display: flex;
    gap: 0.5rem;
}
</style>