<?php
/**
 * ARIA HOTEL - RESTAURANT PAGE
 * ============================================
 */

$page_title = 'Restaurant - Aria Hotel';
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

// Lấy danh sách bàn
$tables = [];
$table_result = $conn->query("SELECT id, table_number, capacity, location, is_available FROM restaurant_tables ORDER BY table_number");
while ($row = $table_result->fetch_assoc()) {
    $tables[] = $row;
}

// Lấy menu theo danh mục
$menu_by_category = [];
$categories = ['appetizer', 'main_course', 'dessert', 'beverage', 'breakfast'];
foreach ($categories as $cat) {
    $stmt = $conn->prepare("SELECT id, name, description, price, image FROM menu_items WHERE category = ? AND is_available = 1");
    $stmt->bind_param("s", $cat);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $menu_by_category[$cat][] = $row;
    }
    $stmt->close();
}

// Xử lý giỏ hàng
$cart = $_SESSION['restaurant_cart'] ?? [];
$reservation_error = '';
$reservation_success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $reservation_error = 'Invalid security token.';
    } elseif (isset($_POST['add_to_cart'])) {
        $item_id = (int)$_POST['item_id'];
        $quantity = (int)$_POST['quantity'];
        
        foreach ($menu_by_category as $items) {
            foreach ($items as $item) {
                if ($item['id'] == $item_id) {
                    if (isset($cart[$item_id])) {
                        $cart[$item_id]['quantity'] += $quantity;
                    } else {
                        $cart[$item_id] = [
                            'id' => $item['id'],
                            'name' => $item['name'],
                            'price' => $item['price'],
                            'quantity' => $quantity
                        ];
                    }
                    break 2;
                }
            }
        }
        $_SESSION['restaurant_cart'] = $cart;
        
    } elseif (isset($_POST['remove_from_cart'])) {
        $item_id = (int)$_POST['item_id'];
        unset($cart[$item_id]);
        $_SESSION['restaurant_cart'] = $cart;
        
    } elseif (isset($_POST['clear_cart'])) {
        $cart = [];
        $_SESSION['restaurant_cart'] = $cart;
        
    } elseif (isset($_POST['book_table'])) {
        if (!$is_logged_in) {
            $reservation_error = 'Please login to make a reservation.';
        } else {
            $table_id = (int)$_POST['table_id'];
            $reservation_date = $_POST['reservation_date'];
            $reservation_time = $_POST['reservation_time'];
            $number_of_guests = (int)$_POST['number_of_guests'];
            $special_requests = trim($_POST['special_requests'] ?? '');
            
            $today = date('Y-m-d');
            if ($reservation_date < $today) {
                $reservation_error = 'Reservation date cannot be in the past.';
            } elseif (empty($table_id)) {
                $reservation_error = 'Please select a table.';
            } else {
                $check_sql = "SELECT COUNT(*) as count FROM table_reservations 
                              WHERE table_id = ? AND reservation_date = ? AND reservation_time = ? 
                              AND status IN ('pending', 'confirmed')";
                $stmt = $conn->prepare($check_sql);
                $stmt->bind_param("iss", $table_id, $reservation_date, $reservation_time);
                $stmt->execute();
                $conflict = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if ($conflict['count'] > 0) {
                    $reservation_error = 'This table is already booked for the selected date and time.';
                } else {
                    $reservation_code = 'TB' . date('Ymd') . strtoupper(substr(uniqid(), -6));
                    
                    $conn->begin_transaction();
                    
                    try {
                        if (!$customer_id) {
                            $cust_stmt = $conn->prepare("INSERT INTO customers (user_id, loyalty_points, nationality, address) VALUES (?, 0, 'Vietnam', '')");
                            $cust_stmt->bind_param("i", $user_id);
                            $cust_stmt->execute();
                            $customer_id = $conn->insert_id;
                            $cust_stmt->close();
                        }
                        
                        $insert_sql = "INSERT INTO table_reservations (reservation_code, user_id, table_id, reservation_date, reservation_time, number_of_guests, special_requests, status)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
                        $stmt = $conn->prepare($insert_sql);
                        $stmt->bind_param("siissis", $reservation_code, $user_id, $table_id, $reservation_date, $reservation_time, $number_of_guests, $special_requests);
                        $stmt->execute();
                        $reservation_id = $conn->insert_id;
                        $stmt->close();
                        
                        if (!empty($cart)) {
                            $order_stmt = $conn->prepare("INSERT INTO order_items (reservation_id, menu_item_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
                            foreach ($cart as $item) {
                                $order_stmt->bind_param("iiid", $reservation_id, $item['id'], $item['quantity'], $item['price']);
                                $order_stmt->execute();
                            }
                            $order_stmt->close();
                            $_SESSION['restaurant_cart'] = [];
                            $cart = [];
                        }
                        
                        $conn->commit();
                        $reservation_success = "Table booked successfully! Your reservation code: " . $reservation_code;
                        
                    } catch (Exception $e) {
                        $conn->rollback();
                        $reservation_error = 'Failed to make reservation. Please try again.';
                    }
                }
            }
        }
    }
}

$cart_total = 0;
foreach ($cart as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}

$csrf_token = Security::generateCSRFToken();

// Category names
$category_names = [
    'appetizer' => 'Appetizers',
    'main_course' => 'Main Course',
    'dessert' => 'Desserts',
    'beverage' => 'Beverages',
    'breakfast' => 'Breakfast'
];
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1>Horizon Restaurant</h1>
        <p>Exquisite dining experience with panoramic city views</p>
    </div>
</section>

<!-- Restaurant Hero -->
<section class="restaurant-hero">
    <div class="container">
        <div class="hero-grid">
            <div class="hero-content">
                <h2>Welcome to Our Restaurant</h2>
                <p>Experience the finest culinary delights at Horizon Restaurant. Our chefs prepare each dish with the freshest ingredients, combining local flavors with international techniques.</p>
                <div class="restaurant-hours">
                    <div class="hours-item"><i class="fas fa-utensils"></i> Breakfast: 6:00 - 10:00</div>
                    <div class="hours-item"><i class="fas fa-mug-hot"></i> Lunch: 11:30 - 14:30</div>
                    <div class="hours-item"><i class="fas fa-wine-glass-alt"></i> Dinner: 18:00 - 22:00</div>
                </div>
            </div>
            <div class="hero-image">
                <img data-src="https://images.pexels.com/photos/260922/pexels-photo-260922.jpeg?w=600" 
                     alt="Restaurant" class="lazy">
            </div>
        </div>
    </div>
</section>

<!-- Messages -->
<?php if ($reservation_error): ?>
    <div class="container">
        <div class="alert alert-error"><?php echo htmlspecialchars($reservation_error); ?></div>
    </div>
<?php endif; ?>

<?php if ($reservation_success): ?>
    <div class="container">
        <div class="alert alert-success"><?php echo htmlspecialchars($reservation_success); ?></div>
    </div>
<?php endif; ?>

<!-- Menu Section -->
<section class="menu-section">
    <div class="container">
        <h2 class="section-title">Our Menu</h2>
        
        <div class="category-tabs">
            <?php foreach ($categories as $index => $cat): ?>
                <button class="category-btn <?php echo $index === 0 ? 'active' : ''; ?>" data-cat="<?php echo $cat; ?>">
                    <?php echo $category_names[$cat]; ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <?php foreach ($categories as $index => $cat): ?>
            <div class="menu-grid category-grid" id="menu-<?php echo $cat; ?>" style="display: <?php echo $index === 0 ? 'grid' : 'none'; ?>">
                <?php foreach ($menu_by_category[$cat] as $item): ?>
                    <div class="menu-item">
                        <img data-src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="menu-item-image lazy">
                        <div class="menu-item-info">
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p class="menu-item-desc"><?php echo htmlspecialchars(substr($item['description'], 0, 60)); ?>...</p>
                            <div class="menu-item-price"><?php echo number_format($item['price'], 0, ',', '.'); ?> VND</div>
                            <form method="POST" class="add-to-cart-form">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <div class="quantity-control">
                                    <input type="number" name="quantity" value="1" min="1" max="10">
                                    <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm">Add</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Booking Section -->
<section class="booking-section">
    <div class="container">
        <div class="booking-grid">
            <div class="booking-form-wrapper">
                <h3>Book a Table</h3>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Select Table</label>
                        <select name="table_id" class="form-select" required>
                            <option value="">Choose a table</option>
                            <?php foreach ($tables as $table): ?>
                                <option value="<?php echo $table['id']; ?>">
                                    Table <?php echo $table['table_number']; ?> - <?php echo ucfirst($table['location']); ?> (<?php echo $table['capacity']; ?> seats)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Date</label>
                            <input type="date" name="reservation_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Time</label>
                            <select name="reservation_time" class="form-select" required>
                                <option value="08:00">8:00 AM</option>
                                <option value="09:00">9:00 AM</option>
                                <option value="10:00">10:00 AM</option>
                                <option value="12:00">12:00 PM</option>
                                <option value="13:00">1:00 PM</option>
                                <option value="18:00">6:00 PM</option>
                                <option value="19:00">7:00 PM</option>
                                <option value="20:00">8:00 PM</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Number of Guests</label>
                        <input type="number" name="number_of_guests" class="form-control" min="1" max="20" value="2" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Special Requests</label>
                        <textarea name="special_requests" class="form-textarea" rows="2" placeholder="Any dietary restrictions?"></textarea>
                    </div>
                    
                    <button type="submit" name="book_table" class="btn btn-primary btn-block">Confirm Reservation</button>
                </form>
            </div>
            
            <div class="cart-wrapper">
                <h3>Your Order</h3>
                <p class="cart-note">Items will be served at your table</p>
                
                <?php if (empty($cart)): ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-basket"></i>
                        <p>Your order is empty</p>
                    </div>
                <?php else: ?>
                    <div class="cart-items">
                        <?php foreach ($cart as $item): ?>
                            <div class="cart-item">
                                <div class="cart-item-info">
                                    <span class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                    <span class="cart-item-quantity">x<?php echo $item['quantity']; ?></span>
                                </div>
                                <div class="cart-item-price">
                                    <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> VND
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="remove_from_cart" class="remove-btn" title="Remove">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-total">
                        Total: <?php echo number_format($cart_total, 0, ',', '.'); ?> VND
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <button type="submit" name="clear_cart" class="btn btn-outline-danger btn-sm">Clear Order</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
$extra_css = '
<style>
.restaurant-hero {
    padding: 3rem 0;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}
.restaurant-hero .hero-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
}
.restaurant-hours {
    display: flex;
    gap: 2rem;
    margin-top: 1.5rem;
}
.hours-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}
.hours-item i {
    font-size: 1.5rem;
    color: var(--primary);
}
.hero-image img {
    width: 100%;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
}
.menu-section {
    padding: 3rem 0;
}
.category-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}
.category-btn {
    padding: 0.6rem 1.5rem;
    background: var(--gray-100);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-full);
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition-base);
}
.category-btn.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}
.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}
.menu-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-md);
}
.menu-item-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: var(--radius-md);
}
.menu-item-info {
    flex: 1;
}
.menu-item-info h4 {
    font-size: 1rem;
    margin-bottom: 0.25rem;
}
.menu-item-desc {
    font-size: 0.8rem;
    color: var(--gray-500);
    margin-bottom: 0.5rem;
}
.menu-item-price {
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.5rem;
}
.quantity-control {
    display: flex;
    gap: 0.5rem;
}
.quantity-control input {
    width: 60px;
    padding: 0.25rem;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
}
.booking-section {
    padding: 3rem 0;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-200);
}
.booking-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}
.booking-form-wrapper, .cart-wrapper {
    background: white;
    padding: 1.5rem;
    border-radius: var(--radius-lg);
    border: 1px solid var(--gray-200);
}
.cart-note {
    font-size: 0.85rem;
    color: var(--gray-500);
    margin-bottom: 1rem;
}
.empty-cart {
    text-align: center;
    padding: 2rem;
    color: var(--gray-400);
}
.empty-cart i {
    font-size: 3rem;
    margin-bottom: 0.5rem;
}
.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--gray-200);
}
.cart-item-name {
    font-weight: 500;
}
.cart-item-quantity {
    color: var(--gray-500);
    margin-left: 0.5rem;
}
.cart-item-price {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.remove-btn {
    background: none;
    border: none;
    color: var(--danger);
    cursor: pointer;
    padding: 0.25rem;
}
.cart-total {
    padding: 1rem 0;
    font-size: 1.25rem;
    font-weight: 700;
    text-align: right;
    border-top: 2px solid var(--gray-200);
    margin-top: 0.5rem;
}
@media (max-width: 768px) {
    .restaurant-hero .hero-grid {
        grid-template-columns: 1fr;
    }
    .booking-grid {
        grid-template-columns: 1fr;
    }
    .menu-grid {
        grid-template-columns: 1fr;
    }
}
</style>
';

$extra_js = '
<script>
document.querySelectorAll(".category-btn").forEach(btn => {
    btn.addEventListener("click", function() {
        document.querySelectorAll(".category-btn").forEach(b => b.classList.remove("active"));
        this.classList.add("active");
        document.querySelectorAll(".category-grid").forEach(grid => grid.style.display = "none");
        document.getElementById("menu-" + this.dataset.cat).style.display = "grid";
    });
});
</script>
';

require_once 'includes/footer.php';
?>