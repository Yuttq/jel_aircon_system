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

// Get completed bookings
$stmt = $pdo->prepare("
    SELECT b.*, s.name as service_name, s.price as service_price,
           t.first_name as tech_first, t.last_name as tech_last,
           p.amount as payment_amount, p.payment_method,
           f.rating, f.comments as feedback
    FROM bookings b 
    JOIN services s ON b.service_id = s.id 
    LEFT JOIN technicians t ON b.technician_id = t.id 
    LEFT JOIN payments p ON b.id = p.booking_id 
    LEFT JOIN feedback f ON b.id = f.booking_id 
    WHERE b.customer_id = ? AND b.status = 'completed'
    ORDER BY b.booking_date DESC
");
$stmt->execute([$customer_id]);
$completedBookings = $stmt->fetchAll();

// Calculate total spent
$totalSpent = 0;
foreach ($completedBookings as $booking) {
    $totalSpent += $booking['payment_amount'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service History - JEL Air Conditioning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .history-card {
            border-left: 4px solid #198754;
            transition: transform 0.2s;
        }
        .history-card:hover {
            transform: translateY(-2px);
        }
        .star-rating {
            color: #ffc107;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../assets/images/logo.png" alt="JEL Air Conditioning" height="30" class="me-2">
                Customer Portal
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">My Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="history.php">Service History</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="navbar-text me-3">Welcome, <?php echo $_SESSION['customer_name']; ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Service History</h1>
            <a href="../modules/bookings/add.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>New Booking
            </a>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h3><?php echo count($completedBookings); ?></h3>
                        <p class="mb-0">Completed Services</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-peso-sign fa-2x mb-2"></i>
                        <h3>₱<?php echo number_format($totalSpent, 2); ?></h3>
                        <p class="mb-0">Total Spent</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-star fa-2x mb-2"></i>
                        <h3>
                            <?php
                            $ratedServices = array_filter($completedBookings, function($b) {
                                return !empty($b['rating']);
                            });
                            echo count($ratedServices) . '/' . count($completedBookings);
                            ?>
                        </h3>
                        <p class="mb-0">Services Rated</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service History -->
        <?php if (count($completedBookings) > 0): ?>
            <div class="row">
                <?php foreach ($completedBookings as $booking): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card history-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="card-title"><?php echo htmlspecialchars($booking['service_name']); ?></h5>
                                        <h6 class="card-subtitle text-muted">
                                            ₱<?php echo number_format($booking['service_price'], 2); ?>
                                        </h6>
                                    </div>
                                    <?php if ($booking['rating']): ?>
                                        <div class="star-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i <= $booking['rating'] ? '' : '-o'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Service Date</small>
                                        <p class="mb-0"><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Completed</small>
                                        <p class="mb-0"><?php echo date('M j, Y', strtotime($booking['updated_at'] ?? $booking['created_at'])); ?></p>
                                    </div>
                                </div>

                                <?php if ($booking['tech_first']): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">Technician</small>
                                        <p class="mb-0"><?php echo htmlspecialchars($booking['tech_first'] . ' ' . $booking['tech_last']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if ($booking['payment_amount']): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">Payment</small>
                                        <p class="mb-0">
                                            ₱<?php echo number_format($booking['payment_amount'], 2); ?> 
                                            via <?php echo ucfirst($booking['payment_method']); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <?php if ($booking['feedback']): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">Your Feedback</small>
                                        <p class="mb-0">"<?php echo nl2br(htmlspecialchars($booking['feedback'])); ?>"</p>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        View Details
                                    </a>
                                    <?php if (empty($booking['rating'])): ?>
                                        <a href="booking-details.php?id=<?php echo $booking['id']; ?>#feedback" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-star me-1"></i>Add Feedback
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                <h4>No service history yet</h4>
                <p class="text-muted">You haven't completed any services yet.</p>
                <a href="../modules/bookings/add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Book Your First Service
                </a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2024 JEL Air Conditioning Services. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>