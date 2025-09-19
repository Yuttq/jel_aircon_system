<?php
include '../../includes/config.php';
checkAuth();

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$bookingId = $_GET['id'];

// Fetch booking data with related information
$stmt = $pdo->prepare("
    SELECT b.*, 
           c.first_name, c.last_name, c.email, c.phone, c.address,
           s.name as service_name, s.price as service_price,
           t.first_name as tech_first, t.last_name as tech_last, t.phone as tech_phone
    FROM bookings b 
    JOIN customers c ON b.customer_id = c.id 
    JOIN services s ON b.service_id = s.id 
    LEFT JOIN technicians t ON b.technician_id = t.id 
    WHERE b.id = ?
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: index.php');
    exit();
}

// Fetch payment information
$paymentStmt = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ?");
$paymentStmt->execute([$bookingId]);
$payment = $paymentStmt->fetch();

// Update booking status if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    
    try {
        $updateStmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $bookingId]);
        
        // Refresh booking data
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        $success = 'Booking status updated successfully!';
    } catch (PDOException $e) {
        $error = 'Error updating booking status: ' . $e->getMessage();
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8">
            <!-- Booking Details Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Booking Details</h5>
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
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Service Information</h6>
                            <p><strong>Service:</strong> <?php echo htmlspecialchars($booking['service_name']); ?></p>
                            <p><strong>Date & Time:</strong> <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?> at <?php echo date('g:i A', strtotime($booking['start_time'])); ?></p>
                            <p><strong>Duration:</strong> <?php echo round((strtotime($booking['end_time']) - strtotime($booking['start_time'])) / 60); ?> minutes</p>
                            <p><strong>Price:</strong> ₱<?php echo number_format($booking['service_price'], 2); ?></p>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Assignment</h6>
                            <p><strong>Technician:</strong> 
                                <?php if ($booking['tech_first']): ?>
                                    <?php echo htmlspecialchars($booking['tech_first'] . ' ' . $booking['tech_last']); ?>
                                    <?php if ($booking['tech_phone']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($booking['tech_phone']); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </p>
                            
                            <?php if ($booking['notes']): ?>
                                <h6 class="mt-3">Notes</h6>
                                <p><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Information Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">Customer Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <?php if ($booking['email']): ?>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['email']); ?></p>
                            <?php endif; ?>
                            <?php if ($booking['address']): ?>
                                <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($booking['address'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Status Update Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">Update Status</h6>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="in-progress" <?php echo $booking['status'] === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
                    </form>
                </div>
            </div>

            <!-- Payment Information Card -->
            <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Payment Information</h6>
        <?php if (!$payment): ?>
            <a href="../payments/add.php?booking_id=<?php echo $bookingId; ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-money-bill me-1"></i> Record Payment
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($payment): ?>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Amount:</strong> ₱<?php echo number_format($payment['amount'], 2); ?></p>
                    <p><strong>Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Status:</strong> 
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
                    </p>
                    <p><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($payment['payment_date'])); ?></p>
                </div>
            </div>
            
            <?php if ($payment['notes']): ?>
                <div class="mt-3">
                    <strong>Notes:</strong>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($payment['notes'])); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="mt-3">
                <a href="../payments/edit.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
                    <i class="fas fa-edit me-1"></i> Edit Payment
                </a>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">No payment recorded yet.</p>
        <?php endif; ?>
    </div>
</div>

            <!-- Actions Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="edit.php?id=<?php echo $bookingId; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i> Edit Booking
                        </a>
                        <a href="delete.php?id=<?php echo $bookingId; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this booking?')">
                            <i class="fas fa-trash me-1"></i> Delete Booking
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>