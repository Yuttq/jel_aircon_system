<?php
include '../../includes/config.php';
checkAuth();

// Date range filtering
$start_date = $_GET['start_date'] ?? date('Y-m-01', strtotime('-3 months'));
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Validate dates
if (!strtotime($start_date)) $start_date = date('Y-m-01', strtotime('-3 months'));
if (!strtotime($end_date)) $end_date = date('Y-m-t');
if ($start_date > $end_date) $start_date = $end_date;

// Get customer analytics data
$customerQuery = "
    SELECT 
        c.id,
        c.first_name,
        c.last_name,
        c.email,
        c.phone,
        COUNT(b.id) as total_bookings,
        SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
        SUM(p.amount) as total_spent,
        MAX(b.booking_date) as last_booking_date,
        MIN(b.booking_date) as first_booking_date
    FROM customers c
    LEFT JOIN bookings b ON c.id = b.customer_id 
    LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'completed'
    WHERE (b.booking_date IS NULL OR b.booking_date BETWEEN ? AND ?)
    GROUP BY c.id, c.first_name, c.last_name, c.email, c.phone
    ORDER BY total_spent DESC
";

$customerStmt = $pdo->prepare($customerQuery);
$customerStmt->execute([$start_date, $end_date . ' 23:59:59']);
$customerData = $customerStmt->fetchAll();

// Get summary statistics
$summaryQuery = "
    SELECT 
        COUNT(DISTINCT c.id) as total_customers,
        COUNT(DISTINCT b.customer_id) as active_customers,
        COUNT(DISTINCT b.id) as total_bookings,
        SUM(p.amount) as total_revenue,
        AVG(p.amount) as avg_customer_value
    FROM customers c
    LEFT JOIN bookings b ON c.id = b.customer_id
    LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'completed'
    WHERE b.booking_date BETWEEN ? AND ?
";

$summaryStmt = $pdo->prepare($summaryQuery);
$summaryStmt->execute([$start_date, $end_date . ' 23:59:59']);
$summary = $summaryStmt->fetch();

// Get new vs returning customers
$customerTypeQuery = "
    SELECT 
        CASE WHEN COUNT(b.id) = 1 THEN 'New Customers' ELSE 'Returning Customers' END as customer_type,
        COUNT(DISTINCT c.id) as customer_count,
        SUM(p.amount) as total_revenue
    FROM customers c
    JOIN bookings b ON c.id = b.customer_id
    LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'completed'
    WHERE b.booking_date BETWEEN ? AND ?
    GROUP BY CASE WHEN COUNT(b.id) = 1 THEN 'New Customers' ELSE 'Returning Customers' END
";

$customerTypeStmt = $pdo->prepare($customerTypeQuery);
$customerTypeStmt->execute([$start_date, $end_date . ' 23:59:59']);
$customerTypeData = $customerTypeStmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Customer Analytics Reports</h1>
                <div>
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print Report
                    </button>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo htmlspecialchars($start_date); ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo htmlspecialchars($end_date); ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="customers.php" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Customers</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $summary['total_customers']; ?>
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
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Active Customers</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $summary['active_customers']; ?>
                                    </div>
                                    <div class="mt-1 text-muted text-sm">
                                        <?php echo $summary['total_customers'] > 0 ? number_format(($summary['active_customers'] / $summary['total_customers']) * 100, 1) : 0; ?>% active rate
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-check fa-2x text-gray-300"></i>
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
                                        Total Revenue</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        ₱<?php echo number_format($summary['total_revenue'], 2); ?>
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
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Avg. Customer Value</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        ₱<?php echo number_format($summary['avg_customer_value'], 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Type Analysis -->
            <div class="row mb-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-info">Customer Type Analysis</h6>
                        </div>
                        <div class="card-body">
                            <?php if (count($customerTypeData) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Customer Type</th>
                                                <th>Count</th>
                                                <th>Total Revenue</th>
                                                <th>Avg. Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($customerTypeData as $data): ?>
                                                <tr>
                                                    <td><?php echo $data['customer_type']; ?></td>
                                                    <td><?php echo $data['customer_count']; ?></td>
                                                    <td>₱<?php echo number_format($data['total_revenue'], 2); ?></td>
                                                    <td>₱<?php echo number_format($data['total_revenue'] / $data['customer_count'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">No customer type data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-success">Top Customers</h6>
                        </div>
                        <div class="card-body">
                            <?php 
                            $topCustomers = array_slice($customerData, 0, 5);
                            if (count($topCustomers) > 0): ?>
                                <div class="list-group">
                                    <?php foreach ($topCustomers as $index => $customer): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0"><?php echo ($index + 1) . '. ' . htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h6>
                                                    <small class="text-muted"><?php echo $customer['total_bookings']; ?> bookings</small>
                                                </div>
                                                <div class="text-end">
                                                    <strong>₱<?php echo number_format($customer['total_spent'], 2); ?></strong><br>
                                                    <small>Total spent</small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">No customer data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Analytics Table -->
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Customer Analytics</h6>
                </div>
                <div class="card-body">
                    <?php if (count($customerData) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Contact</th>
                                        <th>Total Bookings</th>
                                        <th>Completed</th>
                                        <th>Total Spent</th>
                                        <th>Avg. per Booking</th>
                                        <th>First Booking</th>
                                        <th>Last Booking</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customerData as $customer): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                                            <td>
                                                <?php if ($customer['phone']): ?>
                                                    <?php echo htmlspecialchars($customer['phone']); ?><br>
                                                <?php endif; ?>
                                                <?php if ($customer['email']): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $customer['total_bookings']; ?></td>
                                            <td><?php echo $customer['completed_bookings']; ?></td>
                                            <td>₱<?php echo number_format($customer['total_spent'], 2); ?></td>
                                            <td>
                                                <?php if ($customer['total_bookings'] > 0): ?>
                                                    ₱<?php echo number_format($customer['total_spent'] / $customer['total_bookings'], 2); ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($customer['first_booking_date']): ?>
                                                    <?php echo date('M j, Y', strtotime($customer['first_booking_date'])); ?>
                                                <?php else: ?>
                                                    Never
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($customer['last_booking_date']): ?>
                                                    <?php echo date('M j, Y', strtotime($customer['last_booking_date'])); ?>
                                                <?php else: ?>
                                                    Never
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No customer data found for the selected period.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>