<?php
include '../../includes/config.php';
checkAuth();

// Get quick stats for the dashboard
$revenueStmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN MONTH(payment_date) = MONTH(CURDATE()) THEN amount ELSE 0 END) as current_month,
        SUM(CASE WHEN MONTH(payment_date) = MONTH(CURDATE()) - 1 THEN amount ELSE 0 END) as previous_month,
        SUM(amount) as total_revenue
    FROM payments 
    WHERE status = 'completed'
");
$revenueStmt->execute();
$revenueStats = $revenueStmt->fetch();

$bookingsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
    FROM bookings
    WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$bookingsStmt->execute();
$bookingStats = $bookingsStmt->fetch();

$customersStmt = $pdo->prepare("
    SELECT COUNT(*) as total_customers
    FROM customers
");
$customersStmt->execute();
$customerCount = $customersStmt->fetchColumn();

// Count active customers (those with bookings)
$activeCustomersStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT customer_id) as active_customers
    FROM bookings
    WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$activeCustomersStmt->execute();
$activeCustomerCount = $activeCustomersStmt->fetchColumn();

// Get recent payments for the dashboard
$recentPaymentsStmt = $pdo->prepare("
    SELECT p.*, c.first_name, c.last_name 
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN customers c ON b.customer_id = c.id
    WHERE p.status = 'completed'
    ORDER BY p.payment_date DESC 
    LIMIT 5
");
$recentPaymentsStmt->execute();
$recentPayments = $recentPaymentsStmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Reports & Analytics</h1>
                <div>
                    <span class="text-muted me-2"><?php echo date('F Y'); ?></span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Revenue (Monthly)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        ₱<?php echo number_format($revenueStats['current_month'] ?? 0, 2); ?>
                                    </div>
                                    <div class="mt-1 text-muted text-sm">
                                        <?php
                                        $previousMonth = $revenueStats['previous_month'] ?? 0;
                                        $currentMonth = $revenueStats['current_month'] ?? 0;
                                        $revenueChange = $previousMonth > 0 ? 
                                            (($currentMonth - $previousMonth) / $previousMonth) * 100 : 0;
                                        $revenueClass = $revenueChange >= 0 ? 'text-success' : 'text-danger';
                                        $revenueIcon = $revenueChange >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                                        ?>
                                        <span class="<?php echo $revenueClass; ?>">
                                            <i class="fas <?php echo $revenueIcon; ?>"></i> 
                                            <?php echo number_format(abs($revenueChange), 1); ?>%
                                        </span>
                                        from last month
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-peso-sign fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Bookings (30 days)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $bookingStats['total_bookings'] ?? 0; ?>
                                    </div>
                                    <div class="mt-1 text-muted text-sm">
                                        <span class="text-success">
                                            <i class="fas fa-check-circle"></i> 
                                            <?php echo $bookingStats['completed_bookings'] ?? 0; ?> completed
                                        </span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Customers</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $customerCount; ?>
                                    </div>
                                    <div class="mt-1 text-muted text-sm">
                                        <span class="text-info">
                                            <i class="fas fa-users"></i> 
                                            <?php echo $activeCustomerCount; ?> active
                                        </span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Total Revenue</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        ₱<?php echo number_format($revenueStats['total_revenue'] ?? 0, 2); ?>
                                    </div>
                                    <div class="mt-1 text-muted text-sm">
                                        <span class="text-warning">
                                            <i class="fas fa-chart-line"></i> 
                                            All-time earnings
                                        </span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-pie fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Cards -->
            <div class="row">
                <!-- Revenue Report Card -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card shadow h-100">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Revenue Reports</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <i class="fas fa-peso-sign fa-3x text-primary mb-3"></i>
                            </div>
                            <h5 class="card-title">Financial Reports</h5>
                            <p class="card-text">Generate detailed revenue reports by date range, service type, and payment method.</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-chart-bar text-primary me-2"></i> Monthly revenue trends</li>
                                <li><i class="fas fa-money-bill-wave text-primary me-2"></i> Payment method analysis</li>
                                <li><i class="fas fa-calendar text-primary me-2"></i> Custom date ranges</li>
                            </ul>
                            <a href="revenue.php" class="btn btn-primary">View Revenue Reports</a>
                        </div>
                    </div>
                </div>

                <!-- Services Report Card -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card shadow h-100">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">Service Reports</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <i class="fas fa-tools fa-3x text-success mb-3"></i>
                            </div>
                            <h5 class="card-title">Service Analysis</h5>
                            <p class="card-text">Analyze service performance, popularity, and profitability across different service types.</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-star text-success me-2"></i> Most popular services</li>
                                <li><i class="fas fa-chart-pie text-success me-2"></i> Service category analysis</li>
                                <li><i class="fas fa-clock text-success me-2"></i> Service duration trends</li>
                            </ul>
                            <a href="services.php" class="btn btn-success">View Service Reports</a>
                        </div>
                    </div>
                </div>

                <!-- Technicians Report Card -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card shadow h-100">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-info">Technician Reports</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <i class="fas fa-user-cog fa-3x text-info mb-3"></i>
                            </div>
                            <h5 class="card-title">Performance Reports</h5>
                            <p class="card-text">Track technician performance, workload, and service completion rates.</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-tachometer-alt text-info me-2"></i> Technician productivity</li>
                                <li><i class="fas fa-calendar-check text-info me-2"></i> Assignment completion rates</li>
                                <li><i class="fas fa-star text-info me-2"></i> Performance ratings</li>
                            </ul>
                            <a href="technicians.php" class="btn btn-info">View Technician Reports</a>
                        </div>
                    </div>
                </div>

                <!-- Customer Report Card -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card shadow h-100">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-warning">Customer Reports</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <i class="fas fa-users fa-3x text-warning mb-3"></i>
                            </div>
                            <h5 class="card-title">Customer Analytics</h5>
                            <p class="card-text">Understand customer behavior, retention rates, and service preferences.</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-user-plus text-warning me-2"></i> New customer acquisition</li>
                                <li><i class="fas fa-redo text-warning me-2"></i> Repeat customer analysis</li>
                                <li><i class="fas fa-map-marker-alt text-warning me-2"></i> Geographic distribution</li>
                            </ul>
                            <a href="customers.php" class="btn btn-warning">View Customer Reports</a>
                        </div>
                    </div>
                </div>

                <!-- Booking Report Card -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card shadow h-100">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-danger">Booking Reports</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <i class="fas fa-calendar-alt fa-3x text-danger mb-3"></i>
                            </div>
                            <h5 class="card-title">Booking Analysis</h5>
                            <p class="card-text">Analyze booking patterns, cancellation rates, and seasonal trends.</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-chart-line text-danger me-2"></i> Booking trends over time</li>
                                <li><i class="fas fa-times-circle text-danger me-2"></i> Cancellation analysis</li>
                                <li><i class="fas fa-calendar text-danger me-2"></i> Seasonal patterns</li>
                            </ul>
                            <a href="bookings.php" class="btn btn-danger">View Booking Reports</a>
                        </div>
                    </div>
                </div>

                <!-- Recent Payments Card -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card shadow h-100">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-secondary">Recent Payments</h6>
                            <a href="../payments/" class="btn btn-sm btn-outline-secondary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (count($recentPayments) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentPayments as $payment): ?>
                                        <div class="list-group-item px-0 py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></h6>
                                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></small>
                                                </div>
                                                <div class="text-end">
                                                    <strong>₱<?php echo number_format($payment['amount'], 2); ?></strong><br>
                                                    <small class="text-capitalize"><?php echo $payment['payment_method']; ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">No recent payments found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>