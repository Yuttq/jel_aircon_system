<?php
include '../../includes/config.php';
checkAuth();

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$bookingId = $_GET['id'];

// Check if booking exists
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: index.php');
    exit();
}

// Check if booking has payments
$paymentStmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE booking_id = ?");
$paymentStmt->execute([$bookingId]);
$paymentCount = $paymentStmt->fetchColumn();

if ($paymentCount > 0) {
    $_SESSION['error'] = 'Cannot delete booking with existing payments.';
    header('Location: index.php');
    exit();
}

// Delete booking
try {
    $deleteStmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
    $deleteStmt->execute([$bookingId]);
    
    $_SESSION['success'] = 'Booking deleted successfully.';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error deleting booking: ' . $e->getMessage();
}

header('Location: index.php');
exit();
?>