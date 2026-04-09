<?php
/**
 * ARIA HOTEL - HOME PAGE
 * ============================================
 */

$page_title = 'Aria Hotel | Luxury Stay in Ho Chi Minh City';
require_once 'includes/header.php';

// Lấy thống kê
$total_rooms = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'available'")->fetch_assoc()['count'] ?? 0;
$total_room_types = $conn->query("SELECT COUNT(*) as count FROM room_types")->fetch_assoc()['count'] ?? 0;
$total_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status IN ('confirmed', 'checked_in')")->fetch_assoc()['count'] ?? 0;

// Lấy danh sách room types
$room_types = [];
$sql = "SELECT id, name, description, base_price, max_adults, max_children FROM room_types ORDER BY id";
$result = $conn->query($sql);
$room_images = [
    1 => 'https://images.pexels.com/photos/271618/pexels-photo-271618.jpeg?w=600',
    2 => 'https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?w=600',
    3 => 'https://images.pexels.com/photos/164595/pexels-photo-164595.jpeg?w=600',
    4 => 'https://images.pexels.com/photos/279746/pexels-photo-279746.jpeg?w=600',
];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['image'] = $room_images[$row['id']] ?? $room_images[1];
        $room_types[] = $row;
    }
}
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-grid">
            <div class="hero-content">
                <h1 class="hero-title">Welcome to<br>Aria Hotel</h1>
                <p class="hero-subtitle">Experience luxury and comfort in the heart of Ho Chi Minh City. Book your stay with us today.</p>
                <div class="hero-buttons">
                    <a href="rooms.php" class="btn btn-primary btn-lg">Explore Rooms <i class="fas fa-arrow-right"></i></a>
                    <a href="contact.php" class="btn btn-outline-light">Contact Us</a>
                </div>
            </div>
            <div class="hero-image-wrapper">
                <img data-src="https://images.pexels.com/photos/189296/pexels-photo-189296.jpeg?w=600" 
                     alt="Aria Hotel Luxury Lobby" 
                     class="hero-image lazy">
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-bed"></i></div>
                <div class="stat-number"><?php echo $total_rooms; ?></div>
                <div class="stat-label">Available Rooms</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                <div class="stat-number"><?php echo $total_room_types; ?></div>
                <div class="stat-label">Room Types</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-number"><?php echo $total_bookings; ?></div>
                <div class="stat-label">Active Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-concierge-bell"></i></div>
                <div class="stat-number">24/7</div>
                <div class="stat-label">Concierge Service</div>
            </div>
        </div>
    </div>
</section>

<!-- Room Types Section -->
<section class="room-types-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Luxury Rooms & Suites</h2>
            <p class="section-subtitle">Choose from our carefully curated selection of premium accommodations</p>
        </div>
        
        <div class="rooms-grid">
            <?php foreach ($room_types as $room): ?>
            <div class="room-card">
                <div class="room-image-wrapper">
                    <img data-src="<?php echo htmlspecialchars($room['image']); ?>" 
                         alt="<?php echo htmlspecialchars($room['name']); ?>" 
                         class="room-image lazy">
                </div>
                <div class="room-content">
                    <h3 class="room-name"><?php echo htmlspecialchars($room['name']); ?></h3>
                    <p class="room-description"><?php echo htmlspecialchars(substr($room['description'], 0, 80)); ?>...</p>
                    
                    <div class="room-features">
                        <span class="feature"><i class="fas fa-user"></i> <?php echo $room['max_adults']; ?> Adults</span>
                        <span class="feature"><i class="fas fa-child"></i> <?php echo $room['max_children']; ?> Children</span>
                        <span class="feature"><i class="fas fa-wifi"></i> Free WiFi</span>
                        <span class="feature"><i class="fas fa-utensils"></i> Breakfast</span>
                    </div>
                    
                    <div class="room-footer">
                        <div class="room-price">
                            <span class="price"><?php echo number_format($room['base_price'], 0, ',', '.'); ?></span>
                            <span class="currency">VND</span>
                            <span class="per-night">/ night</span>
                        </div>
                        <a href="room-detail.php?id=<?php echo $room['id']; ?>" class="btn btn-primary btn-sm">
                            View Details <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="rooms.php" class="btn btn-outline-primary">View All Rooms <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="features-grid">
            <div class="feature-item">
                <i class="fas fa-swimming-pool"></i>
                <h4>Infinity Pool</h4>
                <p>Enjoy panoramic city views from our rooftop infinity pool</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-spa"></i>
                <h4>Luxury Spa</h4>
                <p>Rejuvenate with our world-class spa treatments</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-utensils"></i>
                <h4>Fine Dining</h4>
                <p>Experience exquisite cuisine at Horizon Restaurant</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-dumbbell"></i>
                <h4>Fitness Center</h4>
                <p>Stay fit with our 24/7 state-of-the-art gym</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Ready for an Unforgettable Stay?</h2>
            <p>Book your room today and experience the best of Ho Chi Minh City</p>
            <div class="cta-buttons">
                <a href="rooms.php" class="btn btn-primary btn-lg">Book Now</a>
                <a href="contact.php" class="btn btn-outline-light">Contact Us</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>