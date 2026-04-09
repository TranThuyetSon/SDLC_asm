<?php
/**
 * ARIA HOTEL - ROOM DETAIL PAGE
 * ============================================
 */

require_once 'includes/header.php';

$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($room_id == 0) {
    header('Location: rooms.php');
    exit();
}

// Lấy thông tin phòng
$room = null;
$sql = "SELECT r.id, r.room_number, r.floor, r.status,
               rt.id as room_type_id, rt.name as room_type, 
               rt.description, rt.base_price, 
               rt.max_adults, rt.max_children
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE r.id = ? AND r.status = 'available'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    header('Location: rooms.php');
    exit();
}
$room = $result->fetch_assoc();
$stmt->close();

// Lấy customer_id nếu đã đăng nhập
$customer_id = null;
if ($is_logged_in) {
    $cust_stmt = $conn->prepare("SELECT id FROM customers WHERE user_id = ?");
    $cust_stmt->bind_param("i", $_SESSION['user_id']);
    $cust_stmt->execute();
    $cust_result = $cust_stmt->get_result();
    if ($cust_row = $cust_result->fetch_assoc()) {
        $customer_id = $cust_row['id'];
    }
    $cust_stmt->close();
}

// Xử lý đặt phòng
$booking_error = '';
$booking_success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_room'])) {
    // Verify CSRF
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $booking_error = 'Invalid security token. Please try again.';
    } elseif (!$is_logged_in) {
        $booking_error = 'Please login to book a room.';
    } else {
        $check_in = $_POST['check_in'];
        $check_out = $_POST['check_out'];
        $number_of_guests = (int)$_POST['guests'];
        $special_requests = trim($_POST['special_requests'] ?? '');
        
        $today = date('Y-m-d');
        if ($check_in < $today) {
            $booking_error = 'Check-in date cannot be in the past.';
        } elseif ($check_in >= $check_out) {
            $booking_error = 'Check-out date must be after check-in date.';
        } elseif ($number_of_guests > $room['max_adults'] + $room['max_children']) {
            $booking_error = 'Number of guests exceeds maximum capacity.';
        } else {
            // Kiểm tra phòng trống
            $check_sql = "SELECT COUNT(*) as count FROM bookings 
                          WHERE room_id = ? 
                          AND status IN ('pending', 'confirmed', 'checked_in')
                          AND check_in_date < ? 
                          AND check_out_date > ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("iss", $room_id, $check_out, $check_in);
            $stmt->execute();
            $check_result = $stmt->get_result();
            $conflict = $check_result->fetch_assoc();
            $stmt->close();
            
            if ($conflict['count'] > 0) {
                $booking_error = 'This room is not available for the selected dates.';
            } else {
                $date1 = new DateTime($check_in);
                $date2 = new DateTime($check_out);
                $nights = $date1->diff($date2)->days;
                $total_price = $room['base_price'] * $nights;
                $booking_code = 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));
                
                $conn->begin_transaction();
                
                try {
                    if (!$customer_id) {
                        $cust_stmt = $conn->prepare("INSERT INTO customers (user_id, loyalty_points, nationality, address) VALUES (?, 0, 'Vietnam', '')");
                        $cust_stmt->bind_param("i", $_SESSION['user_id']);
                        $cust_stmt->execute();
                        $customer_id = $conn->insert_id;
                        $cust_stmt->close();
                    }
                    
                    $insert_sql = "INSERT INTO bookings (booking_code, customer_id, room_id, check_in_date, check_out_date, 
                                  total_price, status, number_of_guests, special_requests, created_by)
                                  VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)";
                    $stmt = $conn->prepare($insert_sql);
                    $stmt->bind_param("siissdiii", $booking_code, $customer_id, $room_id, $check_in, $check_out, 
                                      $total_price, $number_of_guests, $special_requests, $_SESSION['user_id']);
                    
                    if (!$stmt->execute()) {
                        throw new Exception('Failed to create booking');
                    }
                    
                    $conn->commit();
                    $booking_success = "Booking created successfully! Your booking code: " . $booking_code;
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $booking_error = 'Failed to create booking. Please try again.';
                }
                $stmt->close();
            }
        }
    }
}

$csrf_token = Security::generateCSRFToken();

// Ảnh phòng
$room_images = [
    1 => 'https://images.pexels.com/photos/271618/pexels-photo-271618.jpeg?w=800',
    2 => 'https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?w=800',
    3 => 'https://images.pexels.com/photos/164595/pexels-photo-164595.jpeg?w=800',
    4 => 'https://images.pexels.com/photos/279746/pexels-photo-279746.jpeg?w=800',
];
$room_image = $room_images[$room['room_type_id']] ?? $room_images[1];

$page_title = $room['room_type'] . ' - Aria Hotel';
?>

<!-- Breadcrumb -->
<section class="breadcrumb">
    <div class="container">
        <div class="breadcrumb-nav">
            <a href="index.php">Home</a>
            <span>/</span>
            <a href="rooms.php">Rooms</a>
            <span>/</span>
            <span class="current"><?php echo htmlspecialchars($room['room_type']); ?></span>
        </div>
    </div>
</section>

<!-- Room Detail -->
<section class="room-detail-section">
    <div class="container">
        <div class="room-detail-grid">
            <div class="room-gallery">
                <img src="<?php echo $room_image; ?>" alt="<?php echo htmlspecialchars($room['room_type']); ?>" class="room-detail-image">
            </div>
            
            <div class="room-info">
                <h1 class="room-detail-title"><?php echo htmlspecialchars($room['room_type']); ?></h1>
                <div class="room-detail-number">Room <?php echo htmlspecialchars($room['room_number']); ?> • Floor <?php echo $room['floor']; ?></div>
                
                <div class="room-detail-price">
                    <?php echo number_format($room['base_price'], 0, ',', '.'); ?> VND
                    <span class="per-night">/ night</span>
                </div>
                
                <p class="room-detail-description"><?php echo htmlspecialchars($room['description']); ?></p>
                
                <div class="room-detail-features">
                    <div class="feature-item">
                        <i class="fas fa-user"></i>
                        <span>Max <?php echo $room['max_adults']; ?> Adults</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-child"></i>
                        <span>Max <?php echo $room['max_children']; ?> Children</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-wifi"></i>
                        <span>Free Wi-Fi</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-utensils"></i>
                        <span>Breakfast Included</span>
                    </div>
                </div>
                
                <div class="booking-card">
                    <h3>Book This Room</h3>
                    
                    <?php if ($booking_error): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($booking_error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($booking_success): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($booking_success); ?>
                            <a href="my-bookings.php" style="margin-left: 10px;">View My Bookings →</a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$is_logged_in): ?>
                        <div class="alert alert-warning">
                            Please <a href="login.php?redirect=room-detail.php?id=<?php echo $room_id; ?>">login</a> to book this room.
                        </div>
                    <?php else: ?>
                        <form method="POST" action="" id="bookingForm" data-validate>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Check-in Date</label>
                                    <input type="date" name="check_in" id="check_in" class="form-control" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Check-out Date</label>
                                    <input type="date" name="check_out" id="check_out" class="form-control" 
                                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Number of Guests</label>
                                <select name="guests" class="form-select" required>
                                    <?php for ($i = 1; $i <= $room['max_adults'] + $room['max_children']; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?> Guest<?php echo $i > 1 ? 's' : ''; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Special Requests (Optional)</label>
                                <textarea name="special_requests" class="form-textarea" rows="3" 
                                          placeholder="Any special requests?"></textarea>
                            </div>
                            
                            <div class="total-price-box">
                                Total: <span id="totalPrice">0</span> VND
                            </div>
                            
                            <button type="submit" name="book_room" class="btn btn-primary btn-block">
                                Confirm Booking <i class="fas fa-arrow-right"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$extra_css = '
<style>
.room-detail-section {
    padding: 3rem 0;
}
.room-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
}
.room-gallery {
    position: sticky;
    top: 100px;
}
.room-detail-image {
    width: 100%;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
}
.room-detail-title {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}
.room-detail-number {
    display: inline-block;
    padding: 0.25rem 1rem;
    background: var(--gray-100);
    border-radius: var(--radius-full);
    font-size: 0.85rem;
    color: var(--gray-600);
    margin-bottom: 1rem;
}
.room-detail-price {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 1rem;
}
.room-detail-price .per-night {
    font-size: 1rem;
    font-weight: 400;
    color: var(--gray-500);
}
.room-detail-description {
    color: var(--gray-600);
    line-height: 1.7;
    margin-bottom: 1.5rem;
}
.room-detail-features {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    padding: 1.5rem 0;
    border-top: 1px solid var(--gray-200);
    border-bottom: 1px solid var(--gray-200);
    margin-bottom: 1.5rem;
}
.room-detail-features .feature-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}
.room-detail-features .feature-item i {
    font-size: 1.5rem;
    color: var(--primary);
}
.booking-card {
    background: var(--gray-50);
    padding: 1.5rem;
    border-radius: var(--radius-lg);
    border: 1px solid var(--gray-200);
}
.booking-card h3 {
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
}
.total-price-box {
    background: white;
    padding: 1rem;
    text-align: center;
    font-size: 1.25rem;
    font-weight: 600;
    border-radius: var(--radius-md);
    margin: 1rem 0;
}
.total-price-box span {
    color: var(--primary);
    font-size: 1.5rem;
}
@media (max-width: 768px) {
    .room-detail-grid {
        grid-template-columns: 1fr;
    }
    .room-gallery {
        position: static;
    }
}
</style>
';

$extra_js = '
<script>
const basePrice = ' . $room['base_price'] . ';
const checkIn = document.getElementById("check_in");
const checkOut = document.getElementById("check_out");
const totalPriceSpan = document.getElementById("totalPrice");

function calculateTotal() {
    if (checkIn.value && checkOut.value) {
        const start = new Date(checkIn.value);
        const end = new Date(checkOut.value);
        const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        if (nights > 0) {
            totalPriceSpan.textContent = (basePrice * nights).toLocaleString("vi-VN");
        } else {
            totalPriceSpan.textContent = "0";
        }
    } else {
        totalPriceSpan.textContent = "0";
    }
}

checkIn.addEventListener("change", calculateTotal);
checkOut.addEventListener("change", calculateTotal);
</script>
';

require_once 'includes/footer.php';
?>