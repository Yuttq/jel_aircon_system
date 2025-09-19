<?php
include '../../includes/config.php';
checkAuth();

// Filter parameters
$status = $_GET['status'] ?? '';
$booking_id = $_GET['booking_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "
    SELECT p.*, b.booking_date, c.first_name, c.last_name, s.name as service_name
    FROM payments p 
    JOIN bookings b ON p.booking_id = b.id 
    JOIN customers c ON b.customer_id = c.id 
    JOIN services s ON b.service_id = s.id 
    WHERE 1=1
";
$params = [];

if (!empty($status)) {
    $query .= " AND p.status = ?";
    $params[] = $status;
}

if (!empty($booking_id)) {
    $query .= " AND p.booking_id = ?";
    $params[] = $booking_id;
}

if (!empty($date_from)) {
    $query .= " AND p.payment_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND p.payment_date <= ?";
    $params[] = $date_to;
}

// FIXED: Order by payment_date instead of created_at
$query .= " ORDER BY p.payment_date DESC";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Get total amounts for summary
$summaryQuery = "SELECT 
    COUNT(*) as total_count,
    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_completed,
    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
    SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END) as total_failed
FROM payments WHERE 1=1";

$summaryParams = [];
if (!empty($date_from)) {
    $summaryQuery .= " AND payment_date >= ?";
    $summaryParams[] = $date_from;
}
if (!empty($date_to)) {
    $summaryQuery .= " AND payment_date <= ?";
    $summaryParams[] = $date_to;
}

$summaryStmt = $pdo->prepare($summaryQuery);
$summaryStmt->execute($summaryParams);
$summary = $summaryStmt->fetch();
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Payment Management</h1>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> New Payment
                </a>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Payments</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $summary['total_count']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-receipt fa-2x text-gray-300"></i>
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
                                        Completed Payments</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        ₱<?php echo number_format($summary['total_completed'], 2); ?>
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
                                        Pending Payments</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        ₱<?php echo number_format($summary['total_pending'], 2); ?>
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
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Failed Payments</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        ₱<?php echo number_format($summary['total_failed'], 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="booking_id" class="form-label">Booking ID</label>
                            <input type="number" class="form-control" id="booking_id" name="booking_id" 
                                   value="<?php echo htmlspecialchars($booking_id); ?>" placeholder="Enter Booking ID">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="index.php" class="btn btn-outline-secondary">Clear Filters</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (count($payments) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Booking Details</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Payment Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td>#<?php echo $payment['id']; ?></td>
                                            <td>
                                                <div>
                                                    <strong>Booking #<?php echo $payment['booking_id']; ?></strong><br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?><br>
                                                        <?php echo htmlspecialchars($payment['service_name']); ?><br>
                                                        <?php echo date('M j, Y', strtotime($payment['booking_date'])); ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                            <td>
                                                <?php 
                                                $methodLabels = [
                                                    'cash' => 'Cash',
                                                    'gcash' => 'GCash',
                                                    'bank_transfer' => 'Bank Transfer',
                                                    'card' => 'Credit/Debit Card'
                                                ];
                                                echo $methodLabels[$payment['payment_method']] ?? ucfirst($payment['payment_method']); 
                                                ?>
                                            </td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($payment['payment_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    switch ($payment['status']) {
                                                        case 'pending': echo 'warning';
                                                        case 'completed': echo 'success';
                                                        case 'failed': echo 'danger';
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="edit.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this payment?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                            <h5>No payments found</h5>
                            <p class="text-muted">Get started by recording your first payment.</p>
                            <a href="add.php" class="btn btn-primary">Record Payment</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>