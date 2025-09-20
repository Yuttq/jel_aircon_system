<?php
include '../../includes/config.php';
checkAuth();

$errors = [];
$success = '';

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

// Get customer ID from query string if provided
$customer_id = $_GET['customer_id'] ?? '';

// Debug: Check if we're getting data
if (empty($customers)) {
    $errors['database'] = 'No customers found. Please add customers first.';
}
if (empty($services)) {
    $errors['database'] = 'No services found. Please add services first.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'];
    $service_id = $_POST['service_id'];
    $technician_id = !empty($_POST['technician_id']) ? $_POST['technician_id'] : null;
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $notes = trim($_POST['notes']);
    
    // Debug: Check POST data
    error_log("POST Data: " . print_r($_POST, true));
    
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
    
    // Calculate end time based on service duration
    if (empty($errors) && !empty($service_id)) {
        $serviceDurationStmt = $pdo->prepare("SELECT duration FROM services WHERE id = ?");
        $serviceDurationStmt->execute([$service_id]);
        $service = $serviceDurationStmt->fetch();
        
        if ($service) {
            $duration = $service['duration'];
            $end_time = date('H:i:s', strtotime("$start_time + $duration minutes"));
        } else {
            $errors['service_id'] = 'Invalid service selected';
            $end_time = date('H:i:s', strtotime("$start_time + 60 minutes")); // Default 1 hour
        }
    } else {
        $end_time = date('H:i:s', strtotime("$start_time + 60 minutes")); // Default 1 hour
    }
    
    // Check for time conflicts (only if technician is assigned)
    if (empty($errors) && $technician_id) {
        $conflictStmt = $pdo->prepare("
            SELECT COUNT(*) FROM bookings 
            WHERE technician_id = ? 
            AND booking_date = ? 
            AND (
                (start_time <= ? AND end_time > ?) OR 
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND end_time <= ?)
            )
            AND status NOT IN ('cancelled', 'completed')
        ");
        
        $conflictStmt->execute([
            $technician_id, 
            $booking_date, 
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
            $stmt = $pdo->prepare("INSERT INTO bookings (customer_id, service_id, technician_id, booking_date, start_time, end_time, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$customer_id, $service_id, $technician_id, $booking_date, $start_time, $end_time, $notes]);
            
            $booking_id = $pdo->lastInsertId();
            $success = 'Booking created successfully!';
            
            // After successful booking creation
if ($success) {
    // Send confirmation notification
    include '../../includes/notifications.php';
    sendBookingConfirmation($booking_id);
}
            
            // Redirect to view page or clear form
            if (isset($_POST['save_and_view'])) {
                header("Location: view.php?id=$booking_id");
                exit();
            }
            
            // Clear form but keep selected customer if coming from customer page
            if (!isset($_GET['customer_id'])) {
                $_POST = [];
            } else {
                $_POST['customer_id'] = $customer_id;
            }
            
        } catch (PDOException $e) {
            error_log("Booking Error: " . $e->getMessage());
            $errors['database'] = 'Error creating booking: ' . $e->getMessage();
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
                    <h5 class="card-title mb-0">Create New Booking</h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($errors['database'])): ?>
                        <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
                    <?php endif; ?>

                    <form method="POST" id="bookingForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_id" class="form-label">Customer *</label>
                                <select class="form-select <?php echo isset($errors['customer_id']) ? 'is-invalid' : ''; ?>" 
                                        id="customer_id" name="customer_id" required>
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>" 
                                            <?php echo ($_POST['customer_id'] ?? $customer_id) == $customer['id'] ? 'selected' : ''; ?>>
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
                                            <?php echo ($_POST['service_id'] ?? '') == $service['id'] ? 'selected' : ''; ?>
                                            data-duration="<?php echo $service['duration']; ?>"
                                            data-price="<?php echo $service['price']; ?>">
                                            <?php echo htmlspecialchars($service['name'] . ' (₱' . number_format($service['price'], 2) . ')'); ?>
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
                                            <?php echo ($_POST['technician_id'] ?? '') == $tech['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="booking_date" class="form-label">Booking Date *</label>
                                <input type="date" class="form-control <?php echo isset($errors['booking_date']) ? 'is-invalid' : ''; ?>" 
                                       id="booking_date" name="booking_date" 
                                       value="<?php echo htmlspecialchars($_POST['booking_date'] ?? date('Y-m-d')); ?>" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                                <?php if (isset($errors['booking_date'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['booking_date']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_time" class="form-label">Start Time *</label>
                                <input type="time" class="form-control <?php echo isset($errors['start_time']) ? 'is-invalid' : ''; ?>" 
                                       id="start_time" name="start_time" 
                                       value="<?php echo htmlspecialchars($_POST['start_time'] ?? '09:00'); ?>" required>
                                <?php if (isset($errors['start_time'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['start_time']; ?></div>
                                <?php endif; ?>
                                
                                <?php if (isset($errors['time'])): ?>
                                    <div class="text-danger small mt-1"><?php echo $errors['time']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="end_time" class="form-label">End Time (Estimated)</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" readonly>
                                <small class="form-text text-muted">Calculated based on service duration</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <div>
                                <button type="submit" name="save" class="btn btn-primary">Save Booking</button>
                                <button type="submit" name="save_and_view" class="btn btn-success">Save & View</button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Debug information (remove in production) -->
                    <?php if (!empty($errors)): ?>
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6>Debug Information:</h6>
                            <pre><?php print_r($errors); ?></pre>
                            <p>Customers: <?php echo count($customers); ?></p>
                            <p>Services: <?php echo count($services); ?></p>
                            <p>Technicians: <?php echo count($technicians); ?></p>
                        </div>
                    <?php endif; ?>
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
document.addEventListener('DOMContentLoaded', function() {
    calculateEndTime();
    
    // Form validation
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        let isValid = true;
        const customerSelect = document.getElementById('customer_id');
        const serviceSelect = document.getElementById('service_id');
        const dateInput = document.getElementById('booking_date');
        const timeInput = document.getElementById('start_time');

        
        
        if (!customerSelect.value) {
            customerSelect.classList.add('is-invalid');
            isValid = false;
        } else {
            customerSelect.classList.remove('is-invalid');
        }
        
        if (!serviceSelect.value) {
            serviceSelect.classList.add('is-invalid');
            isValid = false;
        } else {
            serviceSelect.classList.remove('is-invalid');
        }
        
        if (!dateInput.value) {
            dateInput.classList.add('is-invalid');
            isValid = false;
        } else {
            dateInput.classList.remove('is-invalid');
        }
        
        if (!timeInput.value) {
            timeInput.classList.add('is-invalid');
            isValid = false;
        } else {
            timeInput.classList.remove('is-invalid');
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
});


</script>

<?php include '../../includes/footer.php'; ?>