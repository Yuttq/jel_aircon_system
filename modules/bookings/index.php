<?php
include '../../includes/config.php';
checkAuth();

// Filter parameters
$status = $_GET['status'] ?? '';
$technician_id = $_GET['technician_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "
    SELECT b.*, c.first_name, c.last_name, c.phone, s.name as service_name, 
           t.first_name as tech_first, t.last_name as tech_last 
    FROM bookings b 
    JOIN customers c ON b.customer_id = c.id 
    JOIN services s ON b.service_id = s.id 
    LEFT JOIN technicians t ON b.technician_id = t.id 
    WHERE 1=1
";
$params = [];

if (!empty($status)) {
    $query .= " AND b.status = ?";
    $params[] = $status;
}

if (!empty($technician_id)) {
    $query .= " AND b.technician_id = ?";
    $params[] = $technician_id;
}

if (!empty($date_from)) {
    $query .= " AND b.booking_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND b.booking_date <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY b.booking_date DESC, b.start_time DESC";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get technicians for filter
$techStmt = $pdo->prepare("SELECT id, first_name, last_name FROM technicians WHERE status = 1 ORDER BY first_name");
$techStmt->execute();
$technicians = $techStmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Booking Management</h1>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> New Booking
                </a>
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
                                <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="in-progress" <?php echo $status === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="technician_id" class="form-label">Technician</label>
                            <select class="form-select" id="technician_id" name="technician_id">
                                <option value="">All Technicians</option>
                                <?php foreach ($technicians as $tech): ?>
                                    <option value="<?php echo $tech['id']; ?>" <?php echo $technician_id == $tech['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="index.php" class="btn btn-outline-secondary">Clear Filters</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (count($bookings) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
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
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary rounded-circle text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                        <?php echo strtoupper(substr($booking['first_name'], 0, 1) . substr($booking['last_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($booking['phone']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
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
                                                <div class="btn-group">
                                                    <a href="view.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this booking?')">
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
                            <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                            <h5>No bookings found</h5>
                            <p class="text-muted">Get started by creating your first booking.</p>
                            <a href="add.php" class="btn btn-primary">Create Booking</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>