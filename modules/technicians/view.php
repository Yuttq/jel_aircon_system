<?php
include '../../includes/config.php';
checkAuth();

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$techId = $_GET['id'];

// Fetch technician data with user information
$stmt = $pdo->prepare("
    SELECT t.*, u.username, u.role, u.full_name as user_full_name 
    FROM technicians t 
    LEFT JOIN users u ON t.user_id = u.id 
    WHERE t.id = ?
");
$stmt->execute([$techId]);
$technician = $stmt->fetch();

if (!$technician) {
    header('Location: index.php');
    exit();
}

// Fetch technician's upcoming assignments
$bookingStmt = $pdo->prepare("
    SELECT b.*, c.first_name, c.last_name, s.name as service_name 
    FROM bookings b 
    JOIN customers c ON b.customer_id = c.id 
    JOIN services s ON b.service_id = s.id 
    WHERE b.technician_id = ? 
    AND b.booking_date >= CURDATE() 
    ORDER BY b.booking_date ASC, b.start_time ASC
");
$bookingStmt->execute([$techId]);
$upcomingBookings = $bookingStmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-4">
            <!-- Technician Profile Card -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="avatar-xl bg-info rounded-circle text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                        <?php echo strtoupper(substr($technician['first_name'], 0, 1) . substr($technician['last_name'], 0, 1)); ?>
                    </div>
                    <h4><?php echo htmlspecialchars($technician['first_name'] . ' ' . $technician['last_name']); ?></h4>
                    <p class="text-muted">Technician ID: <?php echo $technician['id']; ?></p>
                    
                    <div class="d-flex justify-content-center gap-2 mb-4">
                        <a href="edit.php?id=<?php echo $technician['id']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <a href="delete.php?id=<?php echo $technician['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this technician?')">
                            <i class="fas fa-trash me-1"></i> Delete
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contact Information Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">Contact Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong><i class="fas fa-phone me-2"></i> Phone</strong>
                        <p class="mb-0"><?php echo htmlspecialchars($technician['phone']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="fas fa-envelope me-2"></i> Email</strong>
                        <p class="mb-0"><?php echo htmlspecialchars($technician['email'] ?: 'Not provided'); ?></p>
                    </div>
                    
                    <div>
                        <strong><i class="fas fa-tools me-2"></i> Specialization</strong>
                        <p class="mb-0"><?php echo htmlspecialchars($technician['specialization'] ?: 'General AC Services'); ?></p>
                    </div>
                </div>
            </div>

            <!-- System Access Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">System Access</h6>
                </div>
                <div class="card-body">
                    <?php if ($technician['username']): ?>
                        <div class="mb-3">
                            <strong><i class="fas fa-user me-2"></i> Username</strong>
                            <p class="mb-0"><?php echo htmlspecialchars($technician['username']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <strong><i class="fas fa-user-tag me-2"></i> Role</strong>
                            <p class="mb-0"><?php echo ucfirst($technician['role']); ?></p>
                        </div>
                        
                        <div>
                            <strong><i class="fas fa-id-card me-2"></i> Full Name</strong>
                            <p class="mb-0"><?php echo htmlspecialchars($technician['user_full_name']); ?></p>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No system access configured.</p>
                        <a href="edit.php?id=<?php echo $technician['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-link me-1"></i> Link User Account
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Upcoming Assignments Card -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">Upcoming Assignments</h6>
                    <span class="badge bg-primary"><?php echo count($upcomingBookings); ?> assignments</span>
                </div>
                <div class="card-body">
                    <?php if (count($upcomingBookings) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingBookings as $booking): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?><br>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($booking['start_time'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    switch ($booking['status']) {
                                                        case 'pending': return 'warning';
                                                        case 'confirmed': return 'info';
                                                        case 'in-progress': return 'primary';
                                                        case 'completed': return 'success';
                                                        case 'cancelled': return 'danger';
                                                        default: return 'secondary';
                                                    }
                                                ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-check fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No upcoming assignments for this technician.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>