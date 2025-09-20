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

// Get filter parameters
$status = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$query = "
    SELECT b.*, s.name as service_name, s.price as service_price,
           t.first_name as tech_first, t.last_name as tech_last 
    FROM bookings b 
    JOIN services s ON b.service_id = s.id 
    LEFT JOIN technicians t ON b.technician_id = t.id 
    WHERE b.customer_id = ?
";

$params = [$customer_id];

if (!empty($status)) {
    $query .= " AND b.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY b.booking_date DESC, b.created_at DESC";

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM (" . str_replace('b.*, s.name as service_name, s.price as service_price, t.first_name as tech_first, t.last_name as tech_last', 'b.id', $query) . ") as total";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalBookings = $countStmt->fetchColumn();
$totalPages = ceil($totalBookings / $limit);

// Add pagination to main query
$query .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Execute main query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - JEL Air Conditioning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .booking-card {
            transition: transform 0.2s;
            border-left: 4px solid;
        }
        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .status-pending { border-left-color: #ffc107; }
        .status-confirmed { border-left-color: #0dcaf0; }
        .status-in-progress { border-left-color: #0d6efd; }
        .status-completed { border-left-color: #198754; }
        .status-cancelled { border-left-color: #dc3545; }
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
                        <a class="nav-link active" href="bookings.php">My Bookings</a>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>My Bookings</h1>
            <a href="../modules/bookings/add.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>New Booking
            </a>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Filter by Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="in-progress" <?php echo $status === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Apply Filter</button>
                        <a href="bookings.php" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bookings List -->
        <?php if (count($bookings) > 0): ?>
            <div class="row">
                <?php foreach ($bookings as $booking): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card booking-card status-<?php echo $booking['status']; ?> h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="card-title"><?php echo htmlspecialchars($booking['service_name']); ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            ₱<?php echo number_format($booking['service_price'], 2); ?>
                                        </h6>
                                    </div>
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
                                </div>

                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Date</small>
                                        <p class="mb-0"><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Time</small>
                                        <p class="mb-0"><?php echo date('g:i A', strtotime($booking['start_time'])); ?></p>
                                    </div>
                                </div>

                                <?php if ($booking['tech_first']): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">Technician</small>
                                        <p class="mb-0"><?php echo htmlspecialchars($booking['tech_first'] . ' ' . $booking['tech_last']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if ($booking['notes']): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">Notes</small>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Created: <?php echo date('M j, Y', strtotime($booking['created_at'])); ?>
                                    </small>
                                    <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Bookings pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h4>No bookings found</h4>
                <p class="text-muted">
                    <?php echo $status ? "No {$status} bookings found." : "You haven't made any bookings yet."; ?>
                </p>
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