<?php
include '../../includes/config.php';
checkAuth();

// Date range filtering
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Validate dates
if (!strtotime($start_date)) $start_date = date('Y-m-01');
if (!strtotime($end_date)) $end_date = date('Y-m-t');
if ($start_date > $end_date) $start_date = $end_date;

// Get service performance data
$serviceQuery = "
    SELECT 
        s.id,
        s.name,
        s.price,
        COUNT(b.id) as booking_count,
        SUM(p.amount) as total_revenue,
        AVG(p.amount) as avg_revenue
    FROM services s
    LEFT JOIN bookings b ON s.id = b.service_id 
    LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'completed'
    WHERE (b.booking_date IS NULL OR b.booking_date BETWEEN ? AND ?)
    GROUP BY s.id, s.name, s.price
    ORDER BY total_revenue DESC
";

$serviceStmt = $pdo->prepare($serviceQuery);
$serviceStmt->execute([$start_date, $end_date . ' 23:59:59']);
$serviceData = $serviceStmt->fetchAll();

// Get total summary
$summaryQuery = "
    SELECT 
        COUNT(DISTINCT b.id) as total_bookings,
        SUM(p.amount) as total_revenue,
        AVG(p.amount) as avg_revenue
    FROM bookings b
    LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'completed'
    WHERE b.booking_date BETWEEN ? AND ?
";

$summaryStmt = $pdo->prepare($summaryQuery);
$summaryStmt->execute([$start_date, $end_date . ' 23:59:59']);
$summary = $summaryStmt->fetch();
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Service Performance Reports</h1>
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
                                <a href="services.php" class="btn btn-outline-secondary">Reset</a>
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
                                        Total Bookings</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $summary['total_bookings']; ?>
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
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
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
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Avg. Revenue per Booking</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        ₱<?php echo number_format($summary['avg_revenue'], 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calculator fa-2x text-gray-300"></i>
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
                                        Services Offered</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo count($serviceData); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-tools fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service Performance Table -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Service Performance Analysis</h6>
                </div>
                <div class="card-body">
                    <?php if (count($serviceData) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Price</th>
                                        <th>Bookings</th>
                                        <th>Total Revenue</th>
                                        <th>Avg. per Booking</th>
                                        <th>Revenue Share</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($serviceData as $service): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($service['name']); ?></td>
                                            <td>₱<?php echo number_format($service['price'], 2); ?></td>
                                            <td><?php echo $service['booking_count']; ?></td>
                                            <td>₱<?php echo number_format($service['total_revenue'], 2); ?></td>
                                            <td>₱<?php echo number_format($service['avg_revenue'], 2); ?></td>
                                            <td>
                                                <?php if ($summary['total_revenue'] > 0): ?>
                                                    <?php echo number_format(($service['total_revenue'] / $summary['total_revenue']) * 100, 1); ?>%
                                                <?php else: ?>
                                                    0%
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="font-weight-bold">
                                        <td>Total</td>
                                        <td>-</td>
                                        <td><?php echo $summary['total_bookings']; ?></td>
                                        <td>₱<?php echo number_format($summary['total_revenue'], 2); ?></td>
                                        <td>₱<?php echo number_format($summary['avg_revenue'], 2); ?></td>
                                        <td>100%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No service data found for the selected period.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>