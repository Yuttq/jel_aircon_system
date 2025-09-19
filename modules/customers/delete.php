<?php
include '../../includes/config.php';
checkAuth();

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$customerId = $_GET['id'];

// Check if customer exists
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    header('Location: index.php');
    exit();
}

// Check if customer has bookings
$bookingStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ?");
$bookingStmt->execute([$customerId]);
$bookingCount = $bookingStmt->fetchColumn();

if ($bookingCount > 0) {
    $_SESSION['error'] = 'Cannot delete customer with existing bookings.';
    header('Location: index.php');
    exit();
}

// Delete customer
try {
    $deleteStmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $deleteStmt->execute([$customerId]);
    
    $_SESSION['success'] = 'Customer deleted successfully.';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error deleting customer: ' . $e->getMessage();
}

header('Location: index.php');
exit();
?>