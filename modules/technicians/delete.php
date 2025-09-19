<?php
include '../../includes/config.php';
checkAuth();

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$techId = $_GET['id'];

// Check if technician exists
$stmt = $pdo->prepare("SELECT * FROM technicians WHERE id = ?");
$stmt->execute([$techId]);
$technician = $stmt->fetch();

if (!$technician) {
    header('Location: index.php');
    exit();
}

// Check if technician has bookings
$bookingStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE technician_id = ?");
$bookingStmt->execute([$techId]);
$bookingCount = $bookingStmt->fetchColumn();

if ($bookingCount > 0) {
    $_SESSION['error'] = 'Cannot delete technician with existing bookings.';
    header('Location: index.php');
    exit();
}

// Delete technician
try {
    $deleteStmt = $pdo->prepare("DELETE FROM technicians WHERE id = ?");
    $deleteStmt->execute([$techId]);
    
    $_SESSION['success'] = 'Technician deleted successfully.';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error deleting technician: ' . $e->getMessage();
}

header('Location: index.php');
exit();
?>