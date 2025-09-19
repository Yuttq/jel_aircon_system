<?php
include '../../includes/config.php';
checkAuth();

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$paymentId = $_GET['id'];

// Check if payment exists
$stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    header('Location: index.php');
    exit();
}

// Delete payment
try {
    $deleteStmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
    $deleteStmt->execute([$paymentId]);
    
    // If this was a completed payment, update the booking status back to confirmed
    if ($payment['status'] === 'completed') {
        $updateBookingStmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ? AND status = 'completed'");
        $updateBookingStmt->execute([$payment['booking_id']]);
    }
    
    $_SESSION['success'] = 'Payment deleted successfully.';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error deleting payment: ' . $e->getMessage();
}

header('Location: index.php');
exit();
?>