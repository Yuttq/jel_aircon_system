<?php
include '../../includes/config.php';
checkAuth();

$errors = [];
$success = '';

// Get bookings that don't have completed payments
$bookingStmt = $pdo->prepare("
    SELECT b.id, b.booking_date, b.start_time, c.first_name, c.last_name, s.name as service_name, s.price
    FROM bookings b 
    JOIN customers c ON b.customer_id = c.id 
    JOIN services s ON b.service_id = s.id 
    WHERE b.id NOT IN (SELECT booking_id FROM payments WHERE status = 'completed')
    AND b.status NOT IN ('cancelled')
    ORDER BY b.booking_date DESC
");
$bookingStmt->execute();
$bookings = $bookingStmt->fetchAll();

// Get booking ID from query string if provided
$booking_id = $_GET['booking_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);
    $payment_date = $_POST['payment_date'] ?: date('Y-m-d H:i:s');

    // Validation
    if (empty($booking_id)) {
        $errors['booking_id'] = 'Booking is required';
    }
    
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors['amount'] = 'Valid amount is required';
    }
    
    if (empty($payment_method)) {
        $errors['payment_method'] = 'Payment method is required';
    }
    
    if (empty($status)) {
        $errors['status'] = 'Status is required';
    }

    // Check if booking already has a completed payment
    if (empty($errors)) {
        $existingPaymentStmt = $pdo->prepare("SELECT id FROM payments WHERE booking_id = ? AND status = 'completed'");
        $existingPaymentStmt->execute([$booking_id]);
        $existingPayment = $existingPaymentStmt->fetch();
        
        if ($existingPayment && $status === 'completed') {
            $errors['booking_id'] = 'This booking already has a completed payment.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, payment_method, status, notes, payment_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$booking_id, $amount, $payment_method, $status, $notes, $payment_date]);
            
            // If payment is completed, update booking status if it's not already completed
            if ($status === 'completed') {
                $updateBookingStmt = $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = ? AND status != 'completed'");
                $updateBookingStmt->execute([$booking_id]);
            }
            
            $success = 'Payment recorded successfully!';
            $_POST = []; // Clear form
            
        } catch (PDOException $e) {
            $errors['database'] = 'Error recording payment: ' . $e->getMessage();
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
                    <h5 class="card-title mb-0">Record New Payment</h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($errors['database'])): ?>
                        <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="booking_id" class="form-label">Booking *</label>
                            <select class="form-select <?php echo isset($errors['booking_id']) ? 'is-invalid' : ''; ?>" 
                                    id="booking_id" name="booking_id" required>
                                <option value="">Select Booking</option>
                                <?php foreach ($bookings as $booking): ?>
                                    <option value="<?php echo $booking['id']; ?>" 
                                        <?php echo ($_POST['booking_id'] ?? $booking_id) == $booking['id'] ? 'selected' : ''; ?>
                                        data-price="<?php echo $booking['price']; ?>">
                                        Booking #<?php echo $booking['id']; ?> - 
                                        <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?> - 
                                        <?php echo htmlspecialchars($booking['service_name']); ?> - 
                                        <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?>
                                        (₱<?php echo number_format($booking['price'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['booking_id'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['booking_id']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">Amount (₱) *</label>
                                <input type="number" step="0.01" class="form-control <?php echo isset($errors['amount']) ? 'is-invalid' : ''; ?>" 
                                       id="amount" name="amount" 
                                       value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
                                <?php if (isset($errors['amount'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['amount']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="payment_method" class="form-label">Payment Method *</label>
                                <select class="form-select <?php echo isset($errors['payment_method']) ? 'is-invalid' : ''; ?>" 
                                        id="payment_method" name="payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="cash" <?php echo ($_POST['payment_method'] ?? '') === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="gcash" <?php echo ($_POST['payment_method'] ?? '') === 'gcash' ? 'selected' : ''; ?>>GCash</option>
                                    <option value="bank_transfer" <?php echo ($_POST['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="card" <?php echo ($_POST['payment_method'] ?? '') === 'card' ? 'selected' : ''; ?>>Credit/Debit Card</option>
                                </select>
                                <?php if (isset($errors['payment_method'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['payment_method']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select <?php echo isset($errors['status']) ? 'is-invalid' : ''; ?>" 
                                        id="status" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="pending" <?php echo ($_POST['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="completed" <?php echo ($_POST['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="failed" <?php echo ($_POST['status'] ?? '') === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                                <?php if (isset($errors['status'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['status']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="payment_date" class="form-label">Payment Date & Time</label>
                                <input type="datetime-local" class="form-control" id="payment_date" name="payment_date" 
                                       value="<?php echo htmlspecialchars($_POST['payment_date'] ?? date('Y-m-d\TH:i')); ?>">
                                <small class="form-text text-muted">Leave blank for current date/time</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Record Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-fill amount based on selected booking
document.getElementById('booking_id').addEventListener('change', function() {
    const bookingSelect = this;
    const amountInput = document.getElementById('amount');
    
    if (bookingSelect.value) {
        const selectedOption = bookingSelect.options[bookingSelect.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        amountInput.value = price;
    } else {
        amountInput.value = '';
    }
});

// Initialize amount if booking is pre-selected
document.addEventListener('DOMContentLoaded', function() {
    const bookingSelect = document.getElementById('booking_id');
    if (bookingSelect.value) {
        bookingSelect.dispatchEvent(new Event('change'));
    }
});
</script>

<?php include '../../includes/footer.php'; ?>