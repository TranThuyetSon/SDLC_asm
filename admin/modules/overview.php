<?php
/**
 * DASHBOARD OVERVIEW MODULE
 * ============================================
 */

$stats = getDashboardStats($conn);
$recent_bookings = getRecentBookings($conn, 5);
$recent_activities = getRecentActivities($conn, 10);
$popular_room_types = getPopularRoomTypes($conn, 5);
$top_customers = getTopCustomers($conn, 5);
$booking_stats = getBookingStatsByStatus($conn);
$occupancy_rate = getOccupancyRate($conn);
$revenue_data = getRevenueChartData($conn);
?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-number"><?php echo $stats['total_users']; ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-bed"></i></div>
        <div class="stat-number"><?php echo $stats['total_rooms']; ?></div>
        <div class="stat-label">Total Rooms</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-number"><?php echo $stats['active_bookings']; ?></div>
        <div class="stat-label">Active Bookings</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-number"><?php echo formatCurrency($stats['monthly_revenue']); ?></div>
        <div class="stat-label">Monthly Revenue</div>
    </div>
</div>

<!-- Quick Stats Row -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['today_checkins']; ?></div>
        <div class="stat-label">Today's Check-ins</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['today_checkouts']; ?></div>
        <div class="stat-label">Today's Check-outs</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $occupancy_rate; ?>%</div>
        <div class="stat-label">Occupancy Rate</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['unread_contacts']; ?></div>
        <div class="stat-label">Unread Messages</div>
    </div>
</div>

<!-- Recent Bookings & Popular Rooms Row -->
<div class="two-column-row">
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Recent Bookings</h3>
            <a href="?module=bookings" class="btn btn-outline-primary btn-sm">View All</a>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Booking Code</th>
                            <th>Customer</th>
                            <th>Room</th>
                            <th>Check-in</th>
                            <th>Status</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $booking): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($booking['booking_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></td>
                            <td><?php echo getStatusBadge($booking['status'], 'booking'); ?></td>
                            <td><?php echo formatCurrency($booking['total_price']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Popular Room Types</h3>
            <a href="?module=room-types" class="btn btn-outline-primary btn-sm">View All</a>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Room Type</th>
                            <th>Bookings</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_room_types as $type): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($type['name']); ?></td>
                            <td><?php echo $type['booking_count']; ?> bookings</td>
                            <td><?php echo formatCurrency($type['total_revenue']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Top Customers & Recent Activities Row -->
<div class="two-column-row">
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Top Customers</h3>
            <a href="?module=customers" class="btn btn-outline-primary btn-sm">View All</a>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Bookings</th>
                            <th>Total Spent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_customers as $customer): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($customer['email']); ?></small>
                            </td>
                            <td><?php echo $customer['booking_count']; ?></td>
                            <td><?php echo formatCurrency($customer['total_spent']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Recent Activities</h3>
            <a href="?module=audit-logs" class="btn btn-outline-primary btn-sm">View All</a>
        </div>
        <div class="admin-card-body p-0">
            <div class="activity-list">
                <?php foreach ($recent_activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">
                            <strong><?php echo htmlspecialchars($activity['full_name'] ?? 'System'); ?></strong>
                            <?php echo htmlspecialchars($activity['action']); ?>
                        </div>
                        <div class="activity-time"><?php echo formatDateTime($activity['created_at']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<p style="color: #059669; padding: 1rem; background: #d1fae5; border-radius: 8px; margin-top: 1rem;">
    ✅ <strong>Success!</strong> The new English Admin Dashboard is working!
</p>