<?php
include '../../includes/config.php';
checkAuth();

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$paymentId = $_GET['id'];
$errors = [];
$success = '';

// Fetch payment data
$stmt = $pdo->prepare("
    SELECT p.*, b.booking_date, c.first_name, c.last_name, s.name as service_name, s.price
    FROM payments p 
    JOIN bookings b ON p.booking_id = b.id 
    JOIN customers c ON b.customer_id = c.id 
    JOIN services s ON b.service_id = s.id 
    WHERE p.id = ?
");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);
    $payment_date = $_POST['payment_date'];

    // Validation
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors['amount'] = 'Valid amount is required';
    }
    
    if (empty($payment_method)) {
        $errors['payment_method'] = 'Payment method is required';
    }
    
    if (empty($status)) {
        $errors['status'] = 'Status is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE payments SET amount = ?, payment_method = ?, status = ?, notes = ?, payment_date = ? WHERE id = ?");
            $stmt->execute([$amount, $payment_method, $status, $notes, $payment_date, $paymentId]);
            
            // Update booking status based on payment status
            if ($status === 'completed') {
                $updateBookingStmt = $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?");
                $updateBookingStmt->execute([$payment['booking_id']]);
            } elseif ($payment['status'] === 'completed' && $status !== 'completed') {
                // If payment was completed but now changed to something else, revert booking status
                $updateBookingStmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ? AND status = 'completed'");
                $updateBookingStmt->execute([$payment['booking_id']]);
            }
            
            $success = 'Payment updated successfully!';
            
            // Refresh payment data
            $stmt = $pdo->prepare("
                SELECT p.*, b.booking_date, c.first_name, c.last_name, s.name as service_name, s.price
                FROM payments p 
                JOIN bookings b ON p.booking_id = b.id 
                JOIN customers c ON b.customer_id = c.id 
                JOIN services s ON b.service_id = s.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch();
            
        } catch (PDOException $e) {
            $errors['database'] = 'Error updating payment: ' . $e->getMessage();
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
                    <h5 class="card-title mb-0">Edit Payment</h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($errors['database'])): ?>
                        <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
                    <?php endif; ?>

                    <!-- Payment Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Booking Information</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Booking:</strong> #<?php echo $payment['booking_id']; ?> - <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></p>
                            <p><strong>Service:</strong> <?php echo htmlspecialchars($payment['service_name']); ?></p>
                            <p><strong>Service Price:</strong> ₱<?php echo number_format($payment['price'], 2); ?></p>
                            <p><strong>Booking Date:</strong> <?php echo date('M j, Y', strtotime($payment['booking_date'])); ?></p>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">Amount (₱) *</label>
                                <input type="number" step="0.01" class="form-control <?php echo isset($errors['amount']) ? 'is-invalid' : ''; ?>" 
                                       id="amount" name="amount" 
                                       value="<?php echo htmlspecialchars($_POST['amount'] ?? $payment['amount']); ?>" required>
                                <?php if (isset($errors['amount'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['amount']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="payment_method" class="form-label">Payment Method *</label>
                                <select class="form-select <?php echo isset($errors['payment_method']) ? 'is-invalid' : ''; ?>" 
                                        id="payment_method" name="payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="cash" <?php echo ($_POST['payment_method'] ?? $payment['payment_method']) === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="gcash" <?php echo ($_POST['payment_method'] ?? $payment['payment_method']) === 'gcash' ? 'selected' : ''; ?>>GCash</option>
                                    <option value="bank_transfer" <?php echo ($_POST['payment_method'] ?? $payment['payment_method']) === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="card" <?php echo ($_POST['payment_method'] ?? $payment['payment_method']) === 'card' ? 'selected' : ''; ?>>Credit/Debit Card</option>
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
                                    <option value="pending" <?php echo ($_POST['status'] ?? $payment['status']) === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="completed" <?php echo ($_POST['status'] ?? $payment['status']) === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="failed" <?php echo ($_POST['status'] ?? $payment['status']) === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                                <?php if (isset($errors['status'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['status']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="payment_date" class="form-label">Payment Date & Time</label>
                                <input type="datetime-local" class="form-control" id="payment_date" name="payment_date" 
                                       value="<?php echo htmlspecialchars($_POST['payment_date'] ?? date('Y-m-d\TH:i', strtotime($payment['payment_date']))); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? $payment['notes']); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Back to List</a>
                            <button type="submit" class="btn btn-primary">Update Payment</button>
                        </div>
                    </form>
                </div>
            </div>
</div>

<?php include '../../includes/footer.php'; ?>