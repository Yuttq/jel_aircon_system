<?php
include '../../includes/config.php';
checkAuth();

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$customerId = $_GET['id'];

// Fetch customer data
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    header('Location: index.php');
    exit();
}

// Fetch customer's booking history
$bookingStmt = $pdo->prepare("
    SELECT b.*, s.name as service_name, t.first_name as tech_first, t.last_name as tech_last 
    FROM bookings b 
    JOIN services s ON b.service_id = s.id 
    LEFT JOIN technicians t ON b.technician_id = t.id 
    WHERE b.customer_id = ? 
    ORDER BY b.booking_date DESC, b.start_time DESC
");
$bookingStmt->execute([$customerId]);
$bookings = $bookingStmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-4">
            <!-- Customer Profile Card -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="avatar-xl bg-primary rounded-circle text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                        <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                    </div>
                    <h4><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h4>
                    <p class="text-muted">Customer ID: <?php echo $customer['id']; ?></p>
                    
                    <div class="d-flex justify-content-center gap-2 mb-4">
                        <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <a href="delete.php?id=<?php echo $customer['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this customer?')">
                            <i class="fas fa-trash me-1"></i> Delete
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contact Information Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Contact Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong><i class="fas fa-phone me-2"></i> Phone</strong>
                        <p class="mb-0"><?php echo htmlspecialchars($customer['phone']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="fas fa-envelope me-2"></i> Email</strong>
                        <p class="mb-0"><?php echo htmlspecialchars($customer['email'] ?: 'Not provided'); ?></p>
                    </div>
                    
                    <div>
                        <strong><i class="fas fa-map-marker-alt me-2"></i> Address</strong>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($customer['address'] ?: 'Not provided')); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Booking History Card -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">Booking History</h6>
                    <a href="../bookings/add.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> New Booking
                    </a>
                </div>
                <div class="card-body">
                    <?php if (count($bookings) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Date & Time</th>
                                        <th>Technician</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
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
                                                <a href="../bookings/view.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No bookings found for this customer.</p>
                            <a href="../bookings/add.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-primary">
                                Create First Booking
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>