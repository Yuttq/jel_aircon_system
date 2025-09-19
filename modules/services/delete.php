<?php
include '../../includes/config.php';
checkAuth();

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$serviceId = $_GET['id'];

// Check if service exists
$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
$stmt->execute([$serviceId]);
$service = $stmt->fetch();

if (!$service) {
    header('Location: index.php');
    exit();
}

// Check if service has bookings
$bookingStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE service_id = ?");
$bookingStmt->execute([$serviceId]);
$bookingCount = $bookingStmt->fetchColumn();

if ($bookingCount > 0) {
    $_SESSION['error'] = 'Cannot delete service with existing bookings.';
    header('Location: index.php');
    exit();
}

// Delete service
try {
    $deleteStmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    $deleteStmt->execute([$serviceId]);
    
    $_SESSION['success'] = 'Service deleted successfully.';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error deleting service: ' . $e->getMessage();
}

header('Location: index.php');
exit();
?>