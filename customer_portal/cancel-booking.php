<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/config.php';

// Redirect if not logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Get booking ID
if (!isset($_GET['id'])) {
    header('Location: bookings.php');
    exit();
}

$booking_id = $_GET['id'];

// Verify booking belongs to customer and can be cancelled
$stmt = $pdo->prepare("
    SELECT id, status FROM bookings 
    WHERE id = ? AND customer_id = ? AND status IN ('pending', 'confirmed')
");
$stmt->execute([$booking_id, $customer_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: bookings.php');
    exit();
}

// Cancel the booking
$updateStmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
$updateStmt->execute([$booking_id]);

header('Location: booking-details.php?id=' . $booking_id . '&cancelled=1');
exit();
?>