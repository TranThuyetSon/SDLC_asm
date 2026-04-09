<?php
/**
 * ARIA HOTEL - SERVICES PAGE
 * ============================================
 */

$page_title = 'Services - Aria Hotel';
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'] ?? null;
$customer_id = null;

if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $customer_id = $row['id'];
    }
    $stmt->close();
}

// Lấy danh sách dịch vụ
$services = [];
$result = $conn->query("SELECT id, service_name, description, price, is_active FROM services WHERE is_active = 1 ORDER BY price ASC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

$service_images = [
    'Breakfast Buffet' => 'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg?w=400',
    'Lunch Buffet' => 'https://images.pexels.com/photos/1279330/pexels-photo-1279330.jpeg?w=400',
    'Dinner Buffet' => 'https://images.pexels.com/photos/262978/pexels-photo-262978.jpeg?w=400',
    'Spa Massage' => 'https://images.pexels.com/photos/2253836/pexels-photo-2253836.jpeg?w=400',
    'Airport Transfer' => 'https://images.pexels.com/photos/100582/pexels-photo-100582.jpeg?w=400',
    'Laundry Service' => 'https://images.pexels.com/photos/3952075/pexels-photo-3952075.jpeg?w=400',
];

// Xử lý đặt dịch vụ
$booking_error = '';
$booking_success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_service'])) {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $booking_error = 'Invalid security token.';
    } elseif (!$is_logged_in) {
        $booking_error = 'Please login to book a service.';
    } else {
        $service_id = (int)$_POST['service_id'];
        $booking_date = $_POST['booking_date'];
        $booking_time = $_POST['booking_time'];
        $quantity = (int)$_POST['quantity'];
        $special_requests = trim($_POST['special_requests'] ?? '');
        
        $service_stmt = $conn->prepare("SELECT service_name, price FROM services WHERE id = ?");
        $service_stmt->bind_param("i", $service_id);
        $service_stmt->execute();
        $service = $service_stmt->get_result()->fetch_assoc();
        $service_stmt->close();
        
        if (!$service) {
            $booking_error = 'Service not found.';
        } else {
            $total_price = $service['price'] * $quantity;
            $booking_code = 'SV' . date('Ymd') . strtoupper(substr(uniqid(), -6));
            
            $conn->begin_transaction();
            
            try {
                if (!$customer_id) {
                    $cust_stmt = $conn->prepare("INSERT INTO customers (user_id, loyalty_points, nationality, address) VALUES (?, 0, 'Vietnam', '')");
                    $cust_stmt->bind_param("i", $user_id);
                    $cust_stmt->execute();
                    $customer_id = $conn->insert_id;
                    $cust_stmt->close();
                }
                
                $insert_sql = "INSERT INTO service_bookings (booking_code, customer_id, user_id, service_id, booking_date, booking_time, quantity, total_price, special_requests, status)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("siiiissds", $booking_code, $customer_id, $user_id, $service_id, $booking_date, $booking_time, $quantity, $total_price, $special_requests);
                $stmt->execute();
                $stmt->close();
                
                $conn->commit();
                $booking_success = "Service booked successfully! Your booking code: " . $booking_code;
                
            } catch (Exception $e) {
                $conn->rollback();
                $booking_error = 'Failed to book service. Please try again.';
            }
        }
    }
}

$csrf_token = Security::generateCSRFToken();
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1>Hotel Services</h1>
        <p>Enhance your stay with our premium services</p>
    </div>
</section>

<!-- Services Hero -->
<section class="services-hero">
    <div class="container">
        <h2>Everything You Need, Right Here</h2>
        <p>From relaxing spa treatments to convenient transportation, we offer a wide range of services to make your stay unforgettable.</p>
    </div>
</section>

<!-- Messages -->
<?php if ($booking_error): ?>
    <div class="container">
        <div class="alert alert-error"><?php echo htmlspecialchars($booking_error); ?></div>
    </div>
<?php endif; ?>

<?php if ($booking_success): ?>
    <div class="container">
        <div class="alert alert-success"><?php echo htmlspecialchars($booking_success); ?></div>
    </div>
<?php endif; ?>

<!-- Services Grid -->
<section class="services-listing">
    <div class="container">
        <h2 class="section-title">Our Premium Services</h2>
        
        <div class="services-grid">
            <?php foreach ($services as $service): 
                $image = $service_images[$service['service_name']] ?? 'https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?w=400';
            ?>
                <div class="service-card">
                    <div class="service-image-wrapper">
                        <img data-src="<?php echo $image; ?>" alt="<?php echo htmlspecialchars($service['service_name']); ?>" class="service-image lazy">
                    </div>
                    <div class="service-content">
                        <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                        <p class="service-description"><?php echo htmlspecialchars(substr($service['description'], 0, 100)); ?>...</p>
                        <div class="service-price"><?php echo number_format($service['price'], 0, ',', '.'); ?> VND</div>
                        <button class="btn btn-primary btn-block" onclick="openServiceModal(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['service_name']); ?>', <?php echo $service['price']; ?>)">
                            Book Now <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Booking Modal -->
<div id="serviceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Book Service</h3>
            <button class="modal-close" onclick="closeServiceModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" id="serviceForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="service_id" id="service_id">
                
                <div class="form-group">
                    <label class="form-label">Service</label>
                    <input type="text" id="service_name_display" class="form-control" disabled>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Price per unit</label>
                    <input type="text" id="service_price_display" class="form-control" disabled>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="date" name="booking_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Time</label>
                        <select name="booking_time" class="form-select" required>
                            <option value="08:00">8:00 AM</option>
                            <option value="09:00">9:00 AM</option>
                            <option value="10:00">10:00 AM</option>
                            <option value="11:00">11:00 AM</option>
                            <option value="12:00">12:00 PM</option>
                            <option value="13:00">1:00 PM</option>
                            <option value="14:00">2:00 PM</option>
                            <option value="15:00">3:00 PM</option>
                            <option value="16:00">4:00 PM</option>
                            <option value="17:00">5:00 PM</option>
                            <option value="18:00">6:00 PM</option>
                            <option value="19:00">7:00 PM</option>
                            <option value="20:00">8:00 PM</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" id="service_quantity" class="form-control" min="1" value="1" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Special Requests</label>
                    <textarea name="special_requests" class="form-textarea" rows="2" placeholder="Any special requests?"></textarea>
                </div>
                
                <div class="total-price-box">
                    Total: <span id="totalServicePrice">0</span> VND
                </div>
                
                <button type="submit" name="book_service" class="btn btn-primary btn-block">Confirm Booking</button>
            </form>
        </div>
    </div>
</div>

<?php
$extra_css = '
<style>
.services-hero {
    padding: 2rem 0;
    background: var(--gray-50);
    text-align: center;
    border-bottom: 1px solid var(--gray-200);
}
.services-hero h2 {
    margin-bottom: 0.5rem;
}
.services-hero p {
    max-width: 600px;
    margin: 0 auto;
    color: var(--gray-600);
}
.services-listing {
    padding: 3rem 0;
}
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
}
.service-card {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: var(--transition-base);
}
.service-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}
.service-image-wrapper {
    height: 200px;
    overflow: hidden;
}
.service-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--transition-slow);
}
.service-card:hover .service-image {
    transform: scale(1.05);
}
.service-content {
    padding: 1.5rem;
}
.service-content h3 {
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
}
.service-description {
    color: var(--gray-500);
    font-size: 0.9rem;
    margin-bottom: 1rem;
}
.service-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 1rem;
}
.total-price-box {
    background: var(--gray-50);
    padding: 1rem;
    text-align: center;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: var(--radius-md);
    margin: 1rem 0;
}
.total-price-box span {
    color: var(--primary);
    font-size: 1.5rem;
}
@media (max-width: 768px) {
    .services-grid {
        grid-template-columns: 1fr;
    }
}
</style>
';

$extra_js = '
<script>
const modal = document.getElementById("serviceModal");
const serviceIdInput = document.getElementById("service_id");
const serviceNameDisplay = document.getElementById("service_name_display");
const servicePriceDisplay = document.getElementById("service_price_display");
const quantityInput = document.getElementById("service_quantity");
const totalSpan = document.getElementById("totalServicePrice");

let currentPrice = 0;

function openServiceModal(id, name, price) {
    serviceIdInput.value = id;
    serviceNameDisplay.value = name;
    currentPrice = price;
    servicePriceDisplay.value = new Intl.NumberFormat("vi-VN").format(price) + " VND";
    quantityInput.value = 1;
    updateTotal();
    modal.style.display = "flex";
    modal.classList.add("active");
}

function closeServiceModal() {
    modal.style.display = "none";
    modal.classList.remove("active");
}

function updateTotal() {
    const quantity = parseInt(quantityInput.value) || 1;
    totalSpan.textContent = new Intl.NumberFormat("vi-VN").format(currentPrice * quantity);
}

quantityInput.addEventListener("input", updateTotal);

window.onclick = function(event) {
    if (event.target === modal) {
        closeServiceModal();
    }
};

document.addEventListener("keydown", function(e) {
    if (e.key === "Escape" && modal.classList.contains("active")) {
        closeServiceModal();
    }
});
</script>
';

require_once 'includes/footer.php';
?>