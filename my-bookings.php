<?php
/**
 * ARIA HOTEL - MY BOOKINGS PAGE
 * ============================================
 */

$page_title = 'My Bookings - Aria Hotel';
require_once 'includes/header.php';

// Kiểm tra đăng nhập
if (!$is_logged_in) {
    header('Location: login.php?redirect=my-bookings.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$customer_id = null;
$cancel_message = '';
$cancel_error = '';

// Lấy customer_id
$stmt = $conn->prepare("SELECT id FROM customers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $customer_id = $row['id'];
}
$stmt->close();

// Xử lý hủy booking
if (isset($_GET['cancel']) && isset($_GET['id'])) {
    $booking_id = (int)$_GET['id'];
    
    if (!Security::verifyCSRFToken($_GET['token'] ?? '')) {
        $cancel_error = 'Invalid security token.';
    } else {
        $check_sql = "SELECT b.id FROM bookings b
                      JOIN customers c ON b.customer_id = c.id
                      WHERE b.id = ? AND c.user_id = ? AND b.status = 'pending'";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ii", $booking_id, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $update_stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $update_stmt->bind_param("i", $booking_id);
            if ($update_stmt->execute()) {
                $cancel_message = 'Booking cancelled successfully.';
            } else {
                $cancel_error = 'Failed to cancel booking.';
            }
            $update_stmt->close();
        } else {
            $cancel_error = 'Cannot cancel this booking.';
        }
        $stmt->close();
    }
}

// Lấy danh sách bookings
$bookings = [];
if ($customer_id) {
    $sql = "SELECT b.id, b.booking_code, b.check_in_date, b.check_out_date, 
                   b.total_price, b.status, b.number_of_guests, b.special_requests,
                   b.created_at, b.room_id,
                   r.room_number, r.floor,
                   rt.name as room_type, rt.base_price,
                   p.payment_status, p.payment_method, p.amount as paid_amount
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN room_types rt ON r.room_type_id = rt.id
            LEFT JOIN payments p ON b.id = p.booking_id
            WHERE b.customer_id = ?
            ORDER BY b.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $date1 = new DateTime($row['check_in_date']);
        $date2 = new DateTime($row['check_out_date']);
        $row['nights'] = $date1->diff($date2)->days;
        
        // Status badge
        $status_badges = [
            'pending' => ['bg' => 'var(--warning-light)', 'color' => 'var(--warning)', 'text' => 'Pending'],
            'confirmed' => ['bg' => 'var(--success-light)', 'color' => 'var(--success)', 'text' => 'Confirmed'],
            'checked_in' => ['bg' => 'var(--info-light)', 'color' => 'var(--info)', 'text' => 'Checked In'],
            'checked_out' => ['bg' => 'var(--gray-200)', 'color' => 'var(--gray-600)', 'text' => 'Checked Out'],
            'cancelled' => ['bg' => 'var(--danger-light)', 'color' => 'var(--danger)', 'text' => 'Cancelled'],
        ];
        $row['status_badge'] = $status_badges[$row['status']] ?? ['bg' => 'var(--gray-100)', 'color' => 'var(--gray-500)', 'text' => ucfirst($row['status'])];
        
        // Payment badge
        $payment_badges = [
            'completed' => ['icon' => 'check-circle', 'color' => 'var(--success)', 'text' => 'Paid'],
            'pending' => ['icon' => 'clock', 'color' => 'var(--warning)', 'text' => 'Pending'],
            'failed' => ['icon' => 'times-circle', 'color' => 'var(--danger)', 'text' => 'Failed'],
        ];
        $row['payment_badge'] = $payment_badges[$row['payment_status']] ?? ['icon' => 'minus-circle', 'color' => 'var(--gray-500)', 'text' => 'Not paid'];
        
        $bookings[] = $row;
    }
    $stmt->close();
}

// Thống kê
$total_spent = 0;
$active_bookings = 0;
foreach ($bookings as $b) {
    if ($b['status'] != 'cancelled') {
        $total_spent += $b['total_price'];
    }
    if (in_array($b['status'], ['pending', 'confirmed', 'checked_in'])) {
        $active_bookings++;
    }
}

$csrf_token = Security::generateCSRFToken();
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1>My Bookings</h1>
        <p>Manage and track your reservations</p>
    </div>
</section>

<!-- Messages -->
<?php if ($cancel_message): ?>
    <div class="container">
        <div class="alert alert-success"><?php echo htmlspecialchars($cancel_message); ?></div>
    </div>
<?php endif; ?>

<?php if ($cancel_error): ?>
    <div class="container">
        <div class="alert alert-error"><?php echo htmlspecialchars($cancel_error); ?></div>
    </div>
<?php endif; ?>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-number"><?php echo count($bookings); ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                <div class="stat-number"><?php echo $active_bookings; ?></div>
                <div class="stat-label">Active Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-number"><?php echo number_format($total_spent, 0, ',', '.'); ?></div>
                <div class="stat-label">Total Spent (VND)</div>
            </div>
        </div>
    </div>
</section>

<!-- Bookings List -->
<section class="bookings-section">
    <div class="container">
        <h2 class="section-title">Your Reservations</h2>
        
        <?php if (empty($bookings)): ?>
            <div class="no-bookings">
                <i class="fas fa-calendar-times"></i>
                <h3>No bookings yet</h3>
                <p>You haven't made any bookings. Start planning your stay!</p>
                <a href="rooms.php" class="btn btn-primary">Browse Rooms <i class="fas fa-arrow-right"></i></a>
            </div>
        <?php else: ?>
            <div class="bookings-list">
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-card-item">
                        <div class="booking-header">
                            <div class="booking-info">
                                <span class="booking-code"><?php echo htmlspecialchars($booking['booking_code']); ?></span>
                                <span class="booking-date">Booked: <?php echo date('d M Y', strtotime($booking['created_at'])); ?></span>
                            </div>
                            <span class="status-badge" style="background: <?php echo $booking['status_badge']['bg']; ?>; color: <?php echo $booking['status_badge']['color']; ?>;">
                                <?php echo $booking['status_badge']['text']; ?>
                            </span>
                        </div>
                        
                        <div class="booking-body">
                            <div class="booking-room">
                                <h3><?php echo htmlspecialchars($booking['room_type']); ?></h3>
                                <p>Room <?php echo htmlspecialchars($booking['room_number']); ?> • Floor <?php echo $booking['floor']; ?></p>
                            </div>
                            
                            <div class="booking-dates">
                                <div class="date-item">
                                    <span class="date-label">Check-in</span>
                                    <span class="date-value"><?php echo date('d M Y', strtotime($booking['check_in_date'])); ?></span>
                                </div>
                                <div class="date-arrow"><i class="fas fa-arrow-right"></i></div>
                                <div class="date-item">
                                    <span class="date-label">Check-out</span>
                                    <span class="date-value"><?php echo date('d M Y', strtotime($booking['check_out_date'])); ?></span>
                                </div>
                                <div class="date-item">
                                    <span class="date-label">Nights</span>
                                    <span class="date-value"><?php echo $booking['nights']; ?></span>
                                </div>
                            </div>
                            
                            <div class="booking-guests">
                                <i class="fas fa-user"></i> <?php echo $booking['number_of_guests']; ?> guest(s)
                            </div>
                            
                            <?php if (!empty($booking['special_requests'])): ?>
                                <div class="booking-requests">
                                    <i class="fas fa-comment"></i> "<?php echo htmlspecialchars($booking['special_requests']); ?>"
                                </div>
                            <?php endif; ?>
                            
                            <div class="booking-price-info">
                                <div class="price-row">
                                    <span>Room rate:</span>
                                    <span><?php echo number_format($booking['base_price'], 0, ',', '.'); ?> VND / night</span>
                                </div>
                                <div class="price-row total">
                                    <span>Total:</span>
                                    <span><?php echo number_format($booking['total_price'], 0, ',', '.'); ?> VND</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="booking-footer">
                            <div class="payment-status">
                                <i class="fas fa-<?php echo $booking['payment_badge']['icon']; ?>" style="color: <?php echo $booking['payment_badge']['color']; ?>;"></i>
                                <span><?php echo $booking['payment_badge']['text']; ?></span>
                            </div>
                            
                            <div class="booking-actions">
                                <?php if ($booking['payment_status'] != 'completed' && $booking['status'] == 'confirmed'): ?>
                                    <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-success btn-sm">Pay Now</a>
                                <?php endif; ?>
                                
                                <?php if ($booking['status'] == 'pending'): ?>
                                    <a href="?cancel=1&id=<?php echo $booking['id']; ?>&token=<?php echo $csrf_token; ?>" 
                                       class="btn btn-outline-danger btn-sm" 
                                       onclick="return confirm('Are you sure you want to cancel this booking?')">
                                        Cancel Booking
                                    </a>
                                <?php endif; ?>
                                
                                <a href="room-detail.php?id=<?php echo $booking['room_id']; ?>" class="btn btn-outline-primary btn-sm">View Room</a>
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
.bookings-section {
    padding: 3rem 0;
}
.bookings-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.booking-card-item {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: var(--transition-base);
}
.booking-card-item:hover {
    box-shadow: var(--shadow-md);
}
.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}
.booking-code {
    font-weight: 700;
    color: var(--primary);
}
.booking-date {
    font-size: 0.85rem;
    color: var(--gray-500);
    margin-left: 1rem;
}
.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: var(--radius-full);
}
.booking-body {
    padding: 1.5rem;
}
.booking-room h3 {
    font-size: 1.25rem;
    margin-bottom: 0.25rem;
}
.booking-room p {
    color: var(--gray-500);
    font-size: 0.9rem;
    margin-bottom: 1rem;
}
.booking-dates {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--radius-md);
    margin-bottom: 1rem;
}
.date-item {
    text-align: center;
}
.date-label {
    display: block;
    font-size: 0.7rem;
    text-transform: uppercase;
    color: var(--gray-500);
}
.date-value {
    font-weight: 600;
}
.date-arrow {
    color: var(--gray-400);
}
.booking-guests {
    margin-bottom: 0.5rem;
    color: var(--gray-600);
    font-size: 0.9rem;
}
.booking-requests {
    padding: 0.75rem;
    background: var(--warning-light);
    border-radius: var(--radius-md);
    font-size: 0.85rem;
    color: var(--warning);
    font-style: italic;
    margin: 1rem 0;
}
.booking-price-info {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--gray-200);
}
.price-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
    color: var(--gray-600);
}
.price-row.total {
    font-weight: 700;
    color: var(--primary);
    font-size: 1.1rem;
    margin-top: 0.5rem;
}
.booking-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-200);
}
.payment-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
}
.booking-actions {
    display: flex;
    gap: 0.5rem;
}
.btn-outline-danger {
    color: var(--danger);
    border-color: var(--danger);
}
.btn-outline-danger:hover {
    background: var(--danger);
    color: white;
}
.no-bookings {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--gray-50);
    border-radius: var(--radius-lg);
    border: 1px solid var(--gray-200);
}
.no-bookings i {
    font-size: 4rem;
    color: var(--gray-300);
    margin-bottom: 1rem;
}
.no-bookings h3 {
    margin-bottom: 0.5rem;
}
.no-bookings p {
    color: var(--gray-500);
    margin-bottom: 1.5rem;
}
@media (max-width: 768px) {
    .booking-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    .booking-dates {
        flex-wrap: wrap;
    }
    .booking-footer {
        flex-direction: column;
        gap: 1rem;
    }
    .booking-actions {
        width: 100%;
        justify-content: center;
    }
}
</style>
';

require_once 'includes/footer.php';
?>