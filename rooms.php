<?php
/**
 * ARIA HOTEL - ROOMS LISTING PAGE
 * ============================================
 */

$page_title = 'Rooms - Aria Hotel';
require_once 'includes/header.php';

// Lấy danh sách room types cho filter
$room_type_list = [];
$type_result = $conn->query("SELECT id, name FROM room_types ORDER BY name");
if ($type_result && $type_result->num_rows > 0) {
    while ($row = $type_result->fetch_assoc()) {
        $room_type_list[] = $row;
    }
}

// Xử lý bộ lọc
$room_type_filter = isset($_GET['room_type']) ? (int)$_GET['room_type'] : 0;
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 3000000;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Xây dựng SQL
$sql = "SELECT r.id, r.room_number, r.floor, r.status,
               rt.id as room_type_id, rt.name as room_type, 
               rt.description, rt.base_price, 
               rt.max_adults, rt.max_children
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE r.status = 'available'";

$params = [];
$types = "";

if ($room_type_filter > 0) {
    $sql .= " AND rt.id = ?";
    $params[] = $room_type_filter;
    $types .= "i";
}

if ($min_price > 0) {
    $sql .= " AND rt.base_price >= ?";
    $params[] = $min_price;
    $types .= "i";
}

if ($max_price < 3000000) {
    $sql .= " AND rt.base_price <= ?";
    $params[] = $max_price;
    $types .= "i";
}

if (!empty($search)) {
    $sql .= " AND (rt.name LIKE ? OR r.room_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$sql .= " ORDER BY rt.base_price ASC";

$rooms = [];
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
}
$stmt->close();

$room_images = [
    1 => 'https://images.pexels.com/photos/271618/pexels-photo-271618.jpeg?w=600',
    2 => 'https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?w=600',
    3 => 'https://images.pexels.com/photos/164595/pexels-photo-164595.jpeg?w=600',
    4 => 'https://images.pexels.com/photos/279746/pexels-photo-279746.jpeg?w=600',
];
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1>Our Rooms</h1>
        <p>Discover our luxurious rooms designed for your comfort</p>
    </div>
</section>

<!-- Filter Section -->
<section class="filter-section">
    <div class="container">
        <form method="GET" action="" class="filter-form">
            <div class="filter-group">
                <label class="form-label">Room Type</label>
                <select name="room_type" class="form-select">
                    <option value="0">All Types</option>
                    <?php foreach ($room_type_list as $type): ?>
                        <option value="<?php echo $type['id']; ?>" <?php echo $room_type_filter == $type['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="form-label">Price Range (VND)</label>
                <div class="price-range">
                    <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                    <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?php echo $max_price < 3000000 ? $max_price : ''; ?>">
                </div>
            </div>
            
            <div class="filter-group">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Room name or number..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="filter-group filter-actions">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="rooms.php" class="btn btn-outline-primary">Reset</a>
            </div>
        </form>
    </div>
</section>

<!-- Results Info -->
<section class="results-info">
    <div class="container">
        <p>Found <strong><?php echo count($rooms); ?></strong> room(s) available</p>
    </div>
</section>

<!-- Rooms Grid -->
<section class="rooms-listing">
    <div class="container">
        <?php if (empty($rooms)): ?>
            <div class="no-results text-center p-5">
                <i class="fas fa-bed" style="font-size: 3rem; color: var(--gray-300); margin-bottom: 1rem;"></i>
                <h3>No rooms found</h3>
                <p>Try adjusting your filters or check back later</p>
                <a href="rooms.php" class="btn btn-primary mt-3">Clear all filters</a>
            </div>
        <?php else: ?>
            <div class="rooms-grid">
                <?php foreach ($rooms as $room): ?>
                    <div class="room-card">
                        <div class="room-image-wrapper">
                            <img data-src="<?php echo $room_images[$room['room_type_id']] ?? $room_images[1]; ?>" 
                                 alt="<?php echo htmlspecialchars($room['room_type']); ?>" 
                                 class="room-image lazy">
                        </div>
                        <div class="room-content">
                            <span class="room-number">Room <?php echo htmlspecialchars($room['room_number']); ?> • Floor <?php echo $room['floor']; ?></span>
                            <h3 class="room-name"><?php echo htmlspecialchars($room['room_type']); ?></h3>
                            <p class="room-description"><?php echo htmlspecialchars(substr($room['description'], 0, 80)); ?>...</p>
                            
                            <div class="room-features">
                                <span class="feature"><i class="fas fa-user"></i> <?php echo $room['max_adults']; ?> Adults</span>
                                <span class="feature"><i class="fas fa-child"></i> <?php echo $room['max_children']; ?> Children</span>
                                <span class="feature"><i class="fas fa-wifi"></i> Free WiFi</span>
                            </div>
                            
                            <div class="room-footer">
                                <div class="room-price">
                                    <span class="price"><?php echo number_format($room['base_price'], 0, ',', '.'); ?></span>
                                    <span class="currency">VND</span>
                                    <span class="per-night">/ night</span>
                                </div>
                                <a href="room-detail.php?id=<?php echo $room['id']; ?>" class="btn btn-primary btn-sm">
                                    Book Now <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
$extra_css = '
<style>
.filter-section {
    padding: 2rem 0;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}
.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}
.filter-group {
    flex: 1;
    min-width: 180px;
}
.filter-actions {
    display: flex;
    gap: 0.5rem;
}
.price-range {
    display: flex;
    gap: 0.5rem;
}
.price-range input {
    width: 50%;
}
.results-info {
    padding: 1rem 0;
    background: white;
    border-bottom: 1px solid var(--gray-200);
}
.rooms-listing {
    padding: 3rem 0;
}
.room-number {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: var(--gray-100);
    font-size: 0.75rem;
    color: var(--gray-600);
    border-radius: var(--radius-full);
    margin-bottom: 0.5rem;
}
.no-results i {
    display: block;
}
@media (max-width: 768px) {
    .filter-form {
        flex-direction: column;
    }
    .filter-group {
        width: 100%;
    }
}
</style>
';

require_once 'includes/footer.php';
?>