<?php
// includes/config.php (around line 12)
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

// Get booking details
$stmt = $pdo->prepare("
    SELECT b.*, c.first_name, c.last_name, c.phone, c.email, c.address,
           s.name as service_name, s.price as service_price, s.description as service_description,
           t.first_name as tech_first, t.last_name as tech_last, t.phone as tech_phone, t.email as tech_email,
           p.amount as payment_amount, p.status as payment_status, p.payment_method, p.payment_date,
           f.rating, f.comments as feedback_comment, f.created_at as feedback_date
    FROM bookings b 
    JOIN customers c ON b.customer_id = c.id 
    JOIN services s ON b.service_id = s.id 
    LEFT JOIN technicians t ON b.technician_id = t.id 
    LEFT JOIN payments p ON b.id = p.booking_id 
    LEFT JOIN feedback f ON b.id = f.booking_id 
    WHERE b.id = ? AND b.customer_id = ?
");
$stmt->execute([$booking_id, $customer_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: bookings.php');
    exit();
}

// Handle feedback submission
$feedback_success = '';
$feedback_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $rating = (int)$_POST['rating'];
    $comments = trim($_POST['comments']);
    
    if ($rating >= 1 && $rating <= 5 && !empty($comments)) {
        try {
            // Check if feedback already exists
            $checkStmt = $pdo->prepare("SELECT id FROM feedback WHERE booking_id = ?");
            $checkStmt->execute([$booking_id]);
            
            if ($checkStmt->fetch()) {
                // Update existing feedback
                $updateStmt = $pdo->prepare("UPDATE feedback SET rating = ?, comments = ? WHERE booking_id = ?");
                $updateStmt->execute([$rating, $comments, $booking_id]);
            } else {
                // Insert new feedback
                $insertStmt = $pdo->prepare("INSERT INTO feedback (booking_id, rating, comments) VALUES (?, ?, ?)");
                $insertStmt->execute([$booking_id, $rating, $comments]);
            }
            
            $feedback_success = 'Thank you for your feedback!';
            
            // Refresh booking data
            $stmt->execute([$booking_id, $customer_id]);
            $booking = $stmt->fetch();
            
        } catch (PDOException $e) {
            $feedback_error = 'Error submitting feedback. Please try again.';
        }
    } else {
        $feedback_error = 'Please provide a valid rating and comments.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - JEL Air Conditioning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .detail-card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .star-rating {
            color: #ffc107;
            font-size: 1.5rem;
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
            <div>
                <a href="bookings.php" class="btn btn-outline-secondary mb-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to Bookings
                </a>
                <h1 class="h3 mb-0">Booking #<?php echo $booking['id']; ?></h1>
            </div>
            <span class="badge status-badge bg-<?php 
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

        <div class="row">
            <!-- Left Column - Booking Details -->
            <div class="col-lg-8">
                <!-- Service Information -->
                <div class="card detail-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Service Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><?php echo htmlspecialchars($booking['service_name']); ?></h6>
                                <p class="text-muted"><?php echo htmlspecialchars($booking['service_description']); ?></p>
                                <p class="h4 text-primary">₱<?php echo number_format($booking['service_price'], 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Date</small>
                                        <p class="mb-0"><strong><?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></strong></p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Time</small>
                                        <p class="mb-0"><strong><?php echo date('g:i A', strtotime($booking['start_time'])); ?></strong></p>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-6">
                                        <small class="text-muted">Duration</small>
                                        <p class="mb-0">
                                            <?php echo round((strtotime($booking['end_time']) - strtotime($booking['start_time'])) / 60); ?> minutes
                                        </p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Created</small>
                                        <p class="mb-0"><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($booking['notes']): ?>
                            <div class="mt-3 p-3 bg-light rounded">
                                <h6 class="mb-2">Additional Notes</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Technician Information -->
                <?php if ($booking['tech_first']): ?>
                <div class="card detail-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Technician Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 50px; height: 50px;">
                                <span class="text-white fw-bold">
                                    <?php echo strtoupper(substr($booking['tech_first'], 0, 1) . substr($booking['tech_last'], 0, 1)); ?>
                                </span>
                            </div>
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($booking['tech_first'] . ' ' . $booking['tech_last']); ?></h6>
                                <?php if ($booking['tech_phone']): ?>
                                    <p class="mb-0">Phone: <?php echo htmlspecialchars($booking['tech_phone']); ?></p>
                                <?php endif; ?>
                                <?php if ($booking['tech_email']): ?>
                                    <p class="mb-0">Email: <?php echo htmlspecialchars($booking['tech_email']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Payment Information -->
                <?php if ($booking['payment_amount']): ?>
                <div class="card detail-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Amount:</strong> ₱<?php echo number_format($booking['payment_amount'], 2); ?></p>
                                <p><strong>Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $booking['payment_method'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?php 
                                        switch ($booking['payment_status']) {
                                            case 'pending': echo 'warning';
                                            case 'completed': echo 'success';
                                            case 'failed': echo 'danger';
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span>
                                </p>
                                <?php if ($booking['payment_date']): ?>
                                    <p><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($booking['payment_date'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column - Actions & Feedback -->
            <div class="col-lg-4">
                <!-- Booking Actions -->
                <div class="card detail-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Booking Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                <a href="cancel-booking.php?id=<?php echo $booking['id']; ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Are you sure you want to cancel this booking?')">
                                    <i class="fas fa-times me-2"></i>Cancel Booking
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($booking['status'] === 'completed' && empty($booking['payment_amount'])): ?>
                                <a href="../modules/payments/add.php?booking_id=<?php echo $booking['id']; ?>" 
                                   class="btn btn-success">
                                    <i class="fas fa-credit-card me-2"></i>Make Payment
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($booking['status'] === 'completed' && empty($booking['rating'])): ?>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                                    <i class="fas fa-star me-2"></i>Provide Feedback
                                </button>
                            <?php endif; ?>
                            
                            <a href="contact-support.php?booking_id=<?php echo $booking['id']; ?>" 
                               class="btn btn-outline-primary">
                                <i class="fas fa-headset me-2"></i>Contact Support
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="card detail-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Your Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></p>
                        <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone']); ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($booking['email']); ?></p>
                        <?php if ($booking['address']): ?>
                            <p class="mb-0"><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($booking['address'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Existing Feedback -->
                <?php if ($booking['rating']): ?>
                <div class="card detail-card">
                    <div class="card-header">
                        <h5 class="mb-0">Your Feedback</h5>
                    </div>
                    <div class="card-body">
                        <div class="star-rating mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i <= $booking['rating'] ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                            <span class="ms-2">(<?php echo $booking['rating']; ?>/5)</span>
                        </div>
                        <p class="mb-2"><?php echo nl2br(htmlspecialchars($booking['feedback_comment'])); ?></p>
                        <small class="text-muted">Submitted on <?php echo date('M j, Y', strtotime($booking['feedback_date'])); ?></small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Feedback Modal -->
    <?php if ($booking['status'] === 'completed' && empty($booking['rating'])): ?>
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Provide Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?php if ($feedback_success): ?>
                            <div class="alert alert-success"><?php echo $feedback_success; ?></div>
                        <?php elseif ($feedback_error): ?>
                            <div class="alert alert-danger"><?php echo $feedback_error; ?></div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <div class="star-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="rating" id="rating<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                        <label class="form-check-label" for="rating<?php echo $i; ?>">
                                            <i class="fas fa-star"></i> <?php echo $i; ?>
                                        </label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comments" class="form-label">Comments</label>
                            <textarea class="form-control" id="comments" name="comments" rows="4" 
                                      placeholder="Please share your experience with our service..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="submit_feedback" class="btn btn-primary">Submit Feedback</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2024 JEL Air Conditioning Services. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    
    <?php if ($feedback_error): ?>
    <script>
        // Show feedback modal if there was an error
        document.addEventListener('DOMContentLoaded', function() {
            var feedbackModal = new bootstrap.Modal(document.getElementById('feedbackModal'));
            feedbackModal.show();
        });
    </script>
    <?php endif; ?>
</body>
</html>