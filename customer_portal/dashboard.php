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

// Get customer details
$customerStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$customerStmt->execute([$customer_id]);
$customer = $customerStmt->fetch();

// Get recent bookings (last 5)
$bookingsStmt = $pdo->prepare("
    SELECT b.*, s.name as service_name, s.price as service_price,
           t.first_name as tech_first, t.last_name as tech_last 
    FROM bookings b 
    JOIN services s ON b.service_id = s.id 
    LEFT JOIN technicians t ON b.technician_id = t.id 
    WHERE b.customer_id = ? 
    ORDER BY b.booking_date DESC, b.created_at DESC 
    LIMIT 5
");
$bookingsStmt->execute([$customer_id]);
$recentBookings = $bookingsStmt->fetchAll();

// Get booking statistics
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
    FROM bookings 
    WHERE customer_id = ?
");
$statsStmt->execute([$customer_id]);
$stats = $statsStmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - JEL Air Conditioning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">My Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="history.php">Service History</a>
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
        <!-- Welcome Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1>Welcome back, <?php echo $customer['first_name']; ?>!</h1>
                        <p class="lead">Here's your service overview</p>
                    </div>
                    <a href="../modules/bookings/add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>New Booking
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-primary mb-2">
                            <i class="fas fa-calendar-check fa-2x"></i>
                        </div>
                        <h3 class="card-title"><?php echo $stats['total_bookings']; ?></h3>
                        <p class="card-text text-muted">Total Bookings</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-success mb-2">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                        <h3 class="card-title"><?php echo $stats['completed_bookings']; ?></h3>
                        <p class="card-text text-muted">Completed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-warning mb-2">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                        <h3 class="card-title"><?php echo $stats['pending_bookings'] + $stats['confirmed_bookings']; ?></h3>
                        <p class="card-text text-muted">Upcoming</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-danger mb-2">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                        <h3 class="card-title"><?php echo $stats['cancelled_bookings']; ?></h3>
                        <p class="card-text text-muted">Cancelled</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Bookings -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Bookings</h5>
                        <a href="bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (count($recentBookings) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Service</th>
                                            <th>Date & Time</th>
                                            <th>Technician</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentBookings as $booking): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($booking['service_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">₱<?php echo number_format($booking['service_price'], 2); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?>
                                                    <br>
                                                    <small class="text-muted"><?php echo date('g:i A', strtotime($booking['start_time'])); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($booking['tech_first']): ?>
                                                        <?php echo htmlspecialchars($booking['tech_first'] . ' ' . $booking['tech_last']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not assigned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        switch ($booking['status']) {
                                                            case 'pending': echo 'warning';
                                                            case 'confirmed': echo 'info';
                                                            case 'in-progress': echo 'primary';
                                                            case 'completed': echo 'success';
                                                            case 'cancelled': echo 'danger';
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5>No bookings yet</h5>
                                <p class="text-muted">You haven't made any bookings yet.</p>
                                <a href="../modules/bookings/add.php" class="btn btn-primary">Book Your First Service</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Profile -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="../modules/bookings/add.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>New Booking Request
                            </a>
                            <a href="history.php" class="btn btn-outline-primary">
                                <i class="fas fa-history me-2"></i>Service History
                            </a>
                            <a href="profile.php" class="btn btn-outline-secondary">
                                <i class="fas fa-user me-2"></i>Update Profile
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Profile Summary -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Your Profile</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 50px; height: 50px;">
                                <span class="text-white fw-bold fs-5">
                                    <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                                </span>
                            </div>
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h6>
                                <small class="text-muted">Customer since <?php echo date('M Y', strtotime($customer['created_at'])); ?></small>
                            </div>
                        </div>
                        
                        <div class="border-top pt-3">
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
                            <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone']); ?></p>
                            <?php if ($customer['address']): ?>
                                <p class="mb-0"><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($customer['address'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>JEL Air Conditioning Services</h5>
                    <p>Professional AC installation, repair, and maintenance services in Cebu City</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Customer Support: <strong><?php echo htmlspecialchars($customer['phone']); ?></strong></p>
                    <p>Email: <strong><?php echo htmlspecialchars($customer['email']); ?></strong></p>
                </div>
            </div>
            <div class="border-top mt-3 pt-3 text-center">
                <small>&copy; 2024 JEL Air Conditioning Services. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>