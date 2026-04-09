<?php
/**
 * BOOKING MANAGEMENT MODULE
 * ============================================
 */

$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $where_conditions[] = "(b.booking_code LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR r.room_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM bookings b
              JOIN rooms r ON b.room_id = r.id
              JOIN customers c ON b.customer_id = c.id
              JOIN users u ON c.user_id = u.id
              $where_clause";

$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_bookings = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_bookings / $per_page);
$stmt->close();

// Get bookings
$bookings_sql = "SELECT b.*, r.room_number, rt.name as room_type, rt.base_price,
                        u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
                        c.loyalty_points,
                        p.payment_status, p.payment_method, p.amount as paid_amount, p.transaction_code
                 FROM bookings b
                 JOIN rooms r ON b.room_id = r.id
                 JOIN room_types rt ON r.room_type_id = rt.id
                 JOIN customers c ON b.customer_id = c.id
                 JOIN users u ON c.user_id = u.id
                 LEFT JOIN payments p ON b.id = p.booking_id
                 $where_clause
                 ORDER BY b.created_at DESC
                 LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($bookings_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        
        if ($action == 'update_status' && $booking_id) {
            $new_status = $_POST['new_status'];
            $allowed_statuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
            
            if (in_array($new_status, $allowed_statuses)) {
                $conn->begin_transaction();
                
                try {
                    // Update booking status
                    $update = $conn->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
                    $update->bind_param("si", $new_status, $booking_id);
                    $update->execute();
                    
                    // If checking in, update room status
                    if ($new_status == 'checked_in') {
                        $room_update = $conn->prepare("
                            UPDATE rooms r 
                            JOIN bookings b ON r.id = b.room_id 
                            SET r.status = 'occupied' 
                            WHERE b.id = ?
                        ");
                        $room_update->bind_param("i", $booking_id);
                        $room_update->execute();
                    }
                    
                    // If checking out or cancelled, make room available
                    if (in_array($new_status, ['checked_out', 'cancelled'])) {
                        $room_update = $conn->prepare("
                            UPDATE rooms r 
                            JOIN bookings b ON r.id = b.room_id 
                            SET r.status = 'available' 
                            WHERE b.id = ?
                        ");
                        $room_update->bind_param("i", $booking_id);
                        $room_update->execute();
                    }
                    
                    $conn->commit();
                    logActivity($conn, $_SESSION['user_id'], "Updated booking #$booking_id to $new_status", 'bookings', $booking_id);
                    $success = "Booking status updated successfully.";
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Failed to update booking: " . $e->getMessage();
                }
            }
        } elseif ($action == 'add_payment' && $booking_id) {
            $amount = (float)$_POST['amount'];
            $payment_method = $_POST['payment_method'];
            $transaction_code = trim($_POST['transaction_code'] ?? '');
            
            $insert = $conn->prepare("
                INSERT INTO payments (booking_id, amount, payment_method, payment_status, transaction_code, payment_date) 
                VALUES (?, ?, ?, 'completed', ?, NOW())
            ");
            $insert->bind_param("idss", $booking_id, $amount, $payment_method, $transaction_code);
            
            if ($insert->execute()) {
                logActivity($conn, $_SESSION['user_id'], "Added payment for booking #$booking_id", 'payments', $insert->insert_id);
                $success = "Payment added successfully.";
            } else {
                $error = "Failed to add payment.";
            }
            $insert->close();
        }
    }
}

$csrf_token = Security::generateCSRFToken();
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Filters -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Booking Management</h3>
        <button class="btn btn-primary btn-sm" onclick="openModal('quickBookingModal')">
            <i class="fas fa-plus"></i> New Booking
        </button>
    </div>
    <div class="admin-card-body">
        <form method="GET" class="filter-form">
            <input type="hidden" name="module" value="bookings">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Bookings</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="checked_in" <?php echo $status_filter == 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                        <option value="checked_out" <?php echo $status_filter == 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="filter-group flex-1">
                    <label>Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Booking code, customer name, email or room..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?module=bookings" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Bookings Table -->
<div class="admin-card">
    <div class="admin-card-body p-0">
        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Booking Code</th>
                        <th>Customer</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Guests</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $bookings->fetch_assoc()): 
                        $nights = (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / 86400;
                    ?>
                    <tr>
                        <td>#<?php echo $booking['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($booking['booking_code']); ?></strong></td>
                        <td>
                            <div><strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong></div>
                            <small><?php echo htmlspecialchars($booking['customer_email']); ?></small>
                            <br><small><?php echo htmlspecialchars($booking['customer_phone']); ?></small>
                        </td>
                        <td>
                            <div><?php echo htmlspecialchars($booking['room_number']); ?></div>
                            <small><?php echo htmlspecialchars($booking['room_type']); ?></small>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?></td>
                        <td><?php echo $booking['number_of_guests']; ?> (<?php echo $nights; ?> nights)</td>
                        <td><?php echo formatCurrency($booking['total_price']); ?></td>
                        <td><?php echo getStatusBadge($booking['status'], 'booking'); ?></td>
                        <td>
                            <?php if ($booking['payment_status'] == 'completed'): ?>
                                <span class="status-badge status-success">Paid</span>
                                <small><?php echo formatCurrency($booking['paid_amount']); ?></small>
                            <?php elseif ($booking['payment_status'] == 'pending'): ?>
                                <span class="status-badge status-warning">Pending</span>
                            <?php else: ?>
                                <span class="status-badge status-secondary">Not Paid</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon btn-outline-primary" onclick="viewBooking(<?php echo $booking['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-icon btn-outline-success" onclick="openStatusModal(<?php echo $booking['id']; ?>, '<?php echo $booking['status']; ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($booking['payment_status'] != 'completed'): ?>
                                <button class="btn-icon btn-outline-warning" onclick="openPaymentModal(<?php echo $booking['id']; ?>, <?php echo $booking['total_price']; ?>)">
                                    <i class="fas fa-credit-card"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?module=bookings&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>" 
                   class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Update Status Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update Booking Status</h3>
            <button class="modal-close" onclick="closeModal('statusModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="booking_id" id="status_booking_id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label>New Status</label>
                    <select name="new_status" id="new_status" class="form-select" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="checked_in">Checked In</option>
                        <option value="checked_out">Checked Out</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Payment Modal -->
<div id="paymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Payment</h3>
            <button class="modal-close" onclick="closeModal('paymentModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="add_payment">
            <input type="hidden" name="booking_id" id="payment_booking_id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label>Amount (VND)</label>
                    <input type="number" name="amount" id="payment_amount" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="cash">Cash</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="momo">Momo</option>
                        <option value="vnpay">VNPay</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Transaction Code (Optional)</label>
                    <input type="text" name="transaction_code" class="form-control" placeholder="Transaction reference">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('paymentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
function openStatusModal(bookingId, currentStatus) {
    document.getElementById('status_booking_id').value = bookingId;
    document.getElementById('new_status').value = currentStatus;
    openModal('statusModal');
}

function openPaymentModal(bookingId, totalAmount) {
    document.getElementById('payment_booking_id').value = bookingId;
    document.getElementById('payment_amount').value = totalAmount;
    openModal('paymentModal');
}

function viewBooking(bookingId) {
    window.location.href = '?module=bookings&view=' + bookingId;
}
</script>

<style>
.filter-row {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
}
.filter-group {
    min-width: 150px;
}
.filter-group.flex-1 {
    flex: 1;
}
.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    padding: 1.5rem;
    border-top: 1px solid #e2e8f0;
}
.page-link {
    padding: 0.5rem 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    color: #0a192f;
    text-decoration: none;
    transition: all 0.2s;
}
.page-link:hover {
    background: #f8fafc;
}
.page-link.active {
    background: #0a192f;
    color: white;
    border-color: #0a192f;
}
.status-badge.status-success {
    background: #d1fae5;
    color: #059669;
}
.status-badge.status-warning {
    background: #fef3c7;
    color: #d97706;
}
.status-badge.status-secondary {
    background: #e2e8f0;
    color: #64748b;
}
.btn-outline-success {
    color: #059669;
    border-color: #059669;
}
.btn-outline-success:hover {
    background: #059669;
    color: white;
}
.btn-outline-warning {
    color: #d97706;
    border-color: #d97706;
}
.btn-outline-warning:hover {
    background: #d97706;
    color: white;
}
</style>