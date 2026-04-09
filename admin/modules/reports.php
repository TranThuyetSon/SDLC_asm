<?php
/**
 * REPORTS & ANALYTICS MODULE
 * ============================================
 */

if (!isAdmin()) {
    echo '<div class="alert alert-error">Access denied. Admin privileges required.</div>';
    return;
}

$period = $_GET['period'] ?? 'month';
$stats = getDashboardStats($conn);
$revenue_data = getRevenueChartData($conn);
$booking_stats = getBookingStatsByStatus($conn);
$popular_room_types = getPopularRoomTypes($conn, 10);
$top_customers = getTopCustomers($conn, 10);
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Reports & Analytics</h3>
        <div class="period-selector">
            <a href="?module=reports&period=week" class="btn btn-sm <?php echo $period == 'week' ? 'btn-primary' : 'btn-outline-primary'; ?>">Week</a>
            <a href="?module=reports&period=month" class="btn btn-sm <?php echo $period == 'month' ? 'btn-primary' : 'btn-outline-primary'; ?>">Month</a>
            <a href="?module=reports&period=year" class="btn btn-sm <?php echo $period == 'year' ? 'btn-primary' : 'btn-outline-primary'; ?>">Year</a>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Revenue (This Month)</div>
        <div class="stat-number"><?php echo formatCurrency($stats['monthly_revenue']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Bookings</div>
        <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Occupancy Rate</div>
        <div class="stat-number"><?php echo getOccupancyRate($conn); ?>%</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Average Booking Value</div>
        <div class="stat-number">
            <?php 
            $avg = $stats['total_bookings'] > 0 ? $stats['monthly_revenue'] / $stats['total_bookings'] : 0;
            echo formatCurrency($avg);
            ?>
        </div>
    </div>
</div>

<!-- Revenue Chart -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Revenue Trend (Last 12 Months)</h3>
        <button class="btn btn-outline-primary btn-sm" onclick="exportChart()">
            <i class="fas fa-download"></i> Export
        </button>
    </div>
    <div class="admin-card-body">
        <canvas id="reportRevenueChart" style="height: 300px;"></canvas>
    </div>
</div>

<!-- Two Column Layout -->
<div class="two-column-row">
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Popular Room Types</h3>
        </div>
        <div class="admin-card-body p-0">
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
                        <td><?php echo $type['booking_count']; ?></td>
                        <td><?php echo formatCurrency($type['total_revenue']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Top Customers</h3>
        </div>
        <div class="admin-card-body p-0">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Bookings</th>
                        <th>Total Spent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($top_customers, 0, 5) as $customer): ?>
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

<script>
// Revenue Chart
const ctx = document.getElementById('reportRevenueChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($revenue_data, 'month')); ?>,
        datasets: [{
            label: 'Revenue (VND)',
            data: <?php echo json_encode(array_column($revenue_data, 'revenue')); ?>,
            borderColor: '#0a192f',
            backgroundColor: 'rgba(10, 25, 47, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return new Intl.NumberFormat('vi-VN', {
                            style: 'currency',
                            currency: 'VND'
                        }).format(context.raw);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('vi-VN', {
                            notation: 'compact'
                        }).format(value);
                    }
                }
            }
        }
    }
});

function exportChart() {
    showToast('Export feature coming soon!', 'info');
}
</script>

<style>
.period-selector {
    display: flex;
    gap: 0.5rem;
}
</style>