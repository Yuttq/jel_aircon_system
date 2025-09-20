<?php
include '../../includes/config.php';
checkAuth();

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$bookingId = $_GET['id'];
$errors = [];
$success = '';

// Fetch booking data
$stmt = $pdo->prepare("
    SELECT b.*, c.first_name, c.last_name, s.duration 
    FROM bookings b 
    JOIN customers c ON b.customer_id = c.id 
    JOIN services s ON b.service_id = s.id 
    WHERE b.id = ?
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: index.php');
    exit();
}

// Get customers, services, and technicians
$customerStmt = $pdo->prepare("SELECT id, first_name, last_name, phone FROM customers ORDER BY first_name, last_name");
$customerStmt->execute();
$customers = $customerStmt->fetchAll();

$serviceStmt = $pdo->prepare("SELECT id, name, price, duration FROM services WHERE status = 1 ORDER BY name");
$serviceStmt->execute();
$services = $serviceStmt->fetchAll();

$techStmt = $pdo->prepare("SELECT id, first_name, last_name FROM technicians WHERE status = 1 ORDER BY first_name");
$techStmt->execute();
$technicians = $techStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'];
    $service_id = $_POST['service_id'];
    $technician_id = !empty($_POST['technician_id']) ? $_POST['technician_id'] : null;
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $notes = trim($_POST['notes']);
    $status = $_POST['status'];
    
    // Calculate end time based on service duration
    $serviceDurationStmt = $pdo->prepare("SELECT duration FROM services WHERE id = ?");
    $serviceDurationStmt->execute([$service_id]);
    $duration = $serviceDurationStmt->fetchColumn();
    
    $end_time = date('H:i:s', strtotime("$start_time + $duration minutes"));

    // Validation
    if (empty($customer_id)) {
        $errors['customer_id'] = 'Customer is required';
    }
    
    if (empty($service_id)) {
        $errors['service_id'] = 'Service is required';
    }
    
    if (empty($booking_date)) {
        $errors['booking_date'] = 'Booking date is required';
    }
    
    if (empty($start_time)) {
        $errors['start_time'] = 'Start time is required';
    }
    
    // Check for time conflicts (excluding current booking)
    if (empty($errors) && $technician_id) {
        $conflictStmt = $pdo->prepare("
            SELECT COUNT(*) FROM bookings 
            WHERE technician_id = ? 
            AND booking_date = ? 
            AND id != ?
            AND (
                (start_time <= ? AND end_time > ?) OR 
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND end_time <= ?)
            )
            AND status NOT IN ('cancelled', 'completed')
        ");

        // After status update
if ($success && isset($_POST['update_status'])) {
    include '../../includes/notifications.php';
    sendStatusUpdate($bookingId, $newStatus);
}
        
        $conflictStmt->execute([
            $technician_id, 
            $booking_date, 
            $bookingId,
            $start_time, $start_time,
            $end_time, $end_time,
            $start_time, $end_time
        ]);
        
        $conflictCount = $conflictStmt->fetchColumn();
        
        if ($conflictCount > 0) {
            $errors['time'] = 'The selected technician is not available at this time. Please choose a different time or technician.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE bookings SET customer_id = ?, service_id = ?, technician_id = ?, booking_date = ?, start_time = ?, end_time = ?, notes = ?, status = ? WHERE id = ?");
            $stmt->execute([$customer_id, $service_id, $technician_id, $booking_date, $start_time, $end_time, $notes, $status, $bookingId]);
            
            $success = 'Booking updated successfully!';
            
            // Refresh booking data
            $stmt = $pdo->prepare("
                SELECT b.*, c.first_name, c.last_name, s.duration 
                FROM bookings b 
                JOIN customers c ON b.customer_id = c.id 
                JOIN services s ON b.service_id = s.id 
                WHERE b.id = ?
            ");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();
            
        } catch (PDOException $e) {
            $errors['database'] = 'Error updating booking: ' . $e->getMessage();
        }
    }
}
?>


<?php include '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Booking</h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($errors['database'])): ?>
                        <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_id" class="form-label">Customer *</label>
                                <select class="form-select <?php echo isset($errors['customer_id']) ? 'is-invalid' : ''; ?>" 
                                        id="customer_id" name="customer_id" required>
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>" 
                                            <?php echo ($_POST['customer_id'] ?? $booking['customer_id']) == $customer['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'] . ' (' . $customer['phone'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['customer_id'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['customer_id']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="service_id" class="form-label">Service *</label>
                                <select class="form-select <?php echo isset($errors['service_id']) ? 'is-invalid' : ''; ?>" 
                                        id="service_id" name="service_id" required>
                                    <option value="">Select Service</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>" 
                                            <?php echo ($_POST['service_id'] ?? $booking['service_id']) == $service['id'] ? 'selected' : ''; ?>
                                            data-duration="<?php echo $service['duration']; ?>">
                                            <?php echo htmlspecialchars($service['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['service_id'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['service_id']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="technician_id" class="form-label">Technician</label>
                                <select class="form-select" id="technician_id" name="technician_id">
                                    <option value="">Not Assigned</option>
                                    <?php foreach ($technicians as $tech): ?>
                                        <option value="<?php echo $tech['id']; ?>" 
                                            <?php echo ($_POST['technician_id'] ?? $booking['technician_id']) == $tech['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="pending" <?php echo ($_POST['status'] ?? $booking['status']) === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo ($_POST['status'] ?? $booking['status']) === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="in-progress" <?php echo ($_POST['status'] ?? $booking['status']) === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo ($_POST['status'] ?? $booking['status']) === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo ($_POST['status'] ?? $booking['status']) === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="booking_date" class="form-label">Booking Date *</label>
                                <input type="date" class="form-control <?php echo isset($errors['booking_date']) ? 'is-invalid' : ''; ?>" 
                                       id="booking_date" name="booking_date" 
                                       value="<?php echo htmlspecialchars($_POST['booking_date'] ?? $booking['booking_date']); ?>" required>
                                <?php if (isset($errors['booking_date'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['booking_date']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="start_time" class="form-label">Start Time *</label>
                                <input type="time" class="form-control <?php echo isset($errors['start_time']) ? 'is-invalid' : ''; ?>" 
                                       id="start_time" name="start_time" 
                                       value="<?php echo htmlspecialchars($_POST['start_time'] ?? date('H:i', strtotime($booking['start_time']))); ?>" required>
                                <?php if (isset($errors['start_time'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['start_time']; ?></div>
                                <?php endif; ?>
                                
                                <?php if (isset($errors['time'])): ?>
                                    <div class="text-danger small mt-1"><?php echo $errors['time']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="end_time" class="form-label">End Time (Estimated)</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" 
                                       value="<?php echo htmlspecialchars($_POST['end_time'] ?? date('H:i', strtotime($booking['end_time']))); ?>" readonly>
                                <small class="form-text text-muted">Calculated based on service duration</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? $booking['notes']); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="view.php?id=<?php echo $bookingId; ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Booking</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate end time based on service duration
document.getElementById('service_id').addEventListener('change', calculateEndTime);
document.getElementById('start_time').addEventListener('change', calculateEndTime);

function calculateEndTime() {
    const serviceSelect = document.getElementById('service_id');
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    
    if (serviceSelect.value && startTimeInput.value) {
        const duration = parseInt(serviceSelect.options[serviceSelect.selectedIndex].getAttribute('data-duration'));
        const startTime = startTimeInput.value;
        
        // Calculate end time
        const [hours, minutes] = startTime.split(':').map(Number);
        const startDate = new Date();
        startDate.setHours(hours, minutes, 0, 0);
        
        const endDate = new Date(startDate.getTime() + duration * 60000);
        const endTime = endDate.toTimeString().substring(0, 5);
        
        endTimeInput.value = endTime;
    }
}

// Initialize end time calculation on page load
calculateEndTime();
</script>

<?php include '../../includes/footer.php'; ?>