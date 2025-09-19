<?php
include '../../includes/config.php';
checkAuth();

// Date range filtering
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$status = $_GET['status'] ?? '';

// Validate dates
if (!strtotime($start_date)) $start_date = date('Y-m-01');
if (!strtotime($end_date)) $end_date = date('Y-m-t');
if ($start_date > $end_date) $start_date = $end_date;

// Get booking analytics data
$bookingQuery = "
    SELECT 
        b.*,
        c.first_name,
        c.last_name,
        c.phone,
        s.name as service_name,
        s.price as service_price,
        t.first_name as tech_first,
        t.last_name as tech_last,
        p.amount as payment_amount,
        p.status as payment_status
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN services s ON b.service_id = s.id
    LEFT JOIN technicians t ON b.technician_id = t.id
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE b.booking_date BETWEEN ? AND ?
    " . (!empty($status) ? " AND b.status = ?" : "") . "
    ORDER BY b.booking_date DESC, b.start_time DESC
";

$params = [$start_date, $end_date . ' 23:59:59'];
if (!empty($status)) {
    $params[] = $status;
}

$bookingStmt = $pdo->prepare($bookingQuery);
$bookingStmt->execute($params);
$bookingData = $bookingStmt->fetchAll();

// Get summary statistics
$summaryQuery = "
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
        AVG(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as avg_duration
    FROM bookings
    WHERE booking_date BETWEEN ? AND ?
    " . (!empty($status) ? " AND status = ?" : "") . "
";

$summaryParams = [$start_date, $end_date . ' 23:59:59'];
if (!empty($status)) {
    $summaryParams[] = $status;
}

$summaryStmt = $pdo->prepare($summaryQuery);
$summaryStmt->execute($summaryParams);
$summary = $summaryStmt->fetch();

// Get status distribution
$statusQuery = "
    SELECT 
        status,
        COUNT(*) as booking_count
    FROM bookings
    WHERE booking_date BETWEEN ? AND ?
    GROUP BY status
    ORDER BY booking_count DESC
";

$statusStmt = $pdo->prepare($statusQuery);
$statusStmt->execute([$start_date, $end_date . ' 23:59:59']);
$statusData = $statusStmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Booking Analytics Reports</h1>
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
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo htmlspecialchars($start_date); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo htmlspecialchars($end_date); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="in-progress" <?php echo $status === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="bookings.php" class="btn btn-outline-secondary">Reset</a>
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
                                        Completed Bookings</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $summary['completed_bookings']; ?>
                                    </div>
                                    <div class="mt-1 text-muted text-sm">
                                        <?php echo $summary['total_bookings'] > 0 ? number_format(($summary['completed_bookings'] / $summary['total_bookings']) * 100, 1) : 0; ?>% completion rate
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                        Pending Bookings</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $summary['pending_bookings']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                        Avg. Duration</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($summary['avg_duration'], 0); ?> mins
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="row mb-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-info">Booking Status Distribution</h6>
                        </div>
                        <div class="card-body">
                            <?php if (count($statusData) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Status</th>
                                                <th>Count</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($statusData as $data): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            switch ($data['status']) {
                                                                case 'pending': echo 'warning';
                                                                case 'confirmed': echo 'info';
                                                                case 'in-progress': echo 'primary';
                                                                case 'completed': echo 'success';
                                                                case 'cancelled': echo 'danger';
                                                                default: echo 'secondary';
                                                            }
                                                        ?>">
                                                            <?php echo ucfirst($data['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $data['booking_count']; ?></td>
                                                    <td>
                                                        <?php echo number_format(($data['booking_count'] / $summary['total_bookings']) * 100, 1); ?>%
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">No status data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-success">Recent Bookings</h6>
                        </div>
                        <div class="card-body">
                            <?php 
                            $recentBookings = array_slice($bookingData, 0, 5);
                            if (count($recentBookings) > 0): ?>
                                <div class="list-group">
                                    <?php foreach ($recentBookings as $booking): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($booking['service_name']); ?></small>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-<?php 
                                                        switch ($booking['status']) {
                                                            case 'pending': echo 'warning';
                                                            case 'confirmed': echo 'info';
                                                            case 'in-progress': echo 'primary';
                                                            case 'completed': echo 'success';
                                                            case 'cancelled': echo 'danger';
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span><br>
                                                    <small><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">No recent bookings available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Analytics Table -->
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Booking Analytics</h6>
                </div>
                <div class="card-body">
                    <?php if (count($bookingData) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Date & Time</th>
                                        <th>Technician</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookingData as $booking): ?>
                                        <tr>
                                            <td>#<?php echo $booking['id']; ?></td>
                                            <td><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?><br>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($booking['start_time'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($booking['tech_first']): ?>
                                                    <?php echo htmlspecialchars($booking['tech_first'] . ' ' . $booking['tech_last']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $duration = round((strtotime($booking['end_time']) - strtotime($booking['start_time'])) / 60);
                                                echo $duration; ?> mins
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    switch ($booking['status']) {
                                                        case 'pending': echo 'warning';
                                                        case 'confirmed': echo 'info';
                                                        case 'in-progress': echo 'primary';
                                                        case 'completed': echo 'success';
                                                        case 'cancelled': echo 'danger';
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($booking['payment_amount']): ?>
                                                    ₱<?php echo number_format($booking['payment_amount'], 2); ?><br>
                                                    <small class="text-capitalize"><?php echo $booking['payment_status']; ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">No payment</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No booking data found for the selected period.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>