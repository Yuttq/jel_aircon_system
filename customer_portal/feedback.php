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

// Get completed bookings without feedback
$stmt = $pdo->prepare("
    SELECT b.id, b.booking_date, s.name as service_name,
           t.first_name as tech_first, t.last_name as tech_last
    FROM bookings b 
    JOIN services s ON b.service_id = s.id 
    LEFT JOIN technicians t ON b.technician_id = t.id 
    LEFT JOIN feedback f ON b.id = f.booking_id 
    WHERE b.customer_id = ? AND b.status = 'completed' AND f.id IS NULL
    ORDER BY b.booking_date DESC
");
$stmt->execute([$customer_id]);
$bookingsWithoutFeedback = $stmt->fetchAll();

// Handle feedback submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $booking_id = (int)$_POST['booking_id'];
    $rating = (int)$_POST['rating'];
    $comments = trim($_POST['comments']);
    
    // Validate input
    if ($booking_id > 0 && $rating >= 1 && $rating <= 5 && !empty($comments)) {
        try {
            // Verify the booking belongs to the customer
            $verifyStmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND customer_id = ? AND status = 'completed'");
            $verifyStmt->execute([$booking_id, $customer_id]);
            
            if ($verifyStmt->fetch()) {
                // Insert feedback
                $insertStmt = $pdo->prepare("INSERT INTO feedback (booking_id, rating, comments) VALUES (?, ?, ?)");
                $insertStmt->execute([$booking_id, $rating, $comments]);
                
                $success = 'Thank you for your feedback! Your review has been submitted.';
                
                // Refresh the bookings list
                $stmt->execute([$customer_id]);
                $bookingsWithoutFeedback = $stmt->fetchAll();
            } else {
                $error = 'Invalid booking selected.';
            }
        } catch (PDOException $e) {
            $error = 'Error submitting feedback. Please try again.';
        }
    } else {
        $error = 'Please provide a valid rating and comments.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provide Feedback - JEL Air Conditioning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .feedback-card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .star-rating {
            color: #ffc107;
            font-size: 1.5rem;
            cursor: pointer;
        }
        .star-rating .fas {
            transition: transform 0.2s;
        }
        .star-rating .fas:hover {
            transform: scale(1.2);
        }
        .booking-option {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .booking-option:hover, .booking-option.selected {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .booking-option.selected {
            background-color: #e8f4ff;
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
                        <a class="nav-link active" href="feedback.php">Feedback</a>
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
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card feedback-card">
                    <div class="card-header">
                        <h2 class="mb-0">Provide Feedback</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if (count($bookingsWithoutFeedback) > 0): ?>
                            <form method="POST" id="feedbackForm">
                                <!-- Select Booking -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Select Service to Review</label>
                                    <div id="bookingOptions">
                                        <?php foreach ($bookingsWithoutFeedback as $booking): ?>
                                            <div class="booking-option" data-booking-id="<?php echo $booking['id']; ?>">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="booking_id" 
                                                           id="booking<?php echo $booking['id']; ?>" 
                                                           value="<?php echo $booking['id']; ?>" required>
                                                    <label class="form-check-label" for="booking<?php echo $booking['id']; ?>">
                                                        <strong><?php echo htmlspecialchars($booking['service_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            Date: <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?>
                                                            <?php if ($booking['tech_first']): ?>
                                                                • Technician: <?php echo htmlspecialchars($booking['tech_first'] . ' ' . $booking['tech_last']); ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Rating -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Rating</label>
                                    <div class="star-rating mb-2" id="starRating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star-o" data-rating="<?php echo $i; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" name="rating" id="ratingInput" required>
                                    <div id="ratingText" class="text-muted">Please select a rating</div>
                                </div>

                                <!-- Comments -->
                                <div class="mb-4">
                                    <label for="comments" class="form-label fw-bold">Your Review</label>
                                    <textarea class="form-control" id="comments" name="comments" rows="5" 
                                              placeholder="Please share your experience with our service. What did you like? How can we improve?" 
                                              required></textarea>
                                </div>

                                <button type="submit" name="submit_feedback" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Feedback
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h4>All caught up!</h4>
                                <p class="text-muted">You've provided feedback for all your completed services.</p>
                                <p>Thank you for helping us improve our services!</p>
                                <a href="history.php" class="btn btn-primary">
                                    <i class="fas fa-history me-2"></i>View Service History
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Feedback Guidelines -->
                <div class="card feedback-card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Feedback Guidelines</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Be specific about your experience</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Mention what you liked about our service</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Suggest areas for improvement</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Keep your feedback constructive and respectful</li>
                            <li><i class="fas fa-check text-success me-2"></i> Your feedback helps us serve you better</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2024 JEL Air Conditioning Services. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    
    <script>
        // Star rating functionality
        const stars = document.querySelectorAll('.star-rating .fas');
        const ratingInput = document.getElementById('ratingInput');
        const ratingText = document.getElementById('ratingText');
        const ratingTexts = [
            'Please select a rating',
            'Poor - Very dissatisfied',
            'Fair - Somewhat dissatisfied',
            'Good - Satisfied',
            'Very Good - Happy with service',
            'Excellent - Extremely satisfied'
        ];

        stars.forEach(star => {
            star.addEventListener('click', () => {
                const rating = parseInt(star.getAttribute('data-rating'));
                ratingInput.value = rating;
                
                // Update stars display
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.remove('fa-star-o');
                        s.classList.add('fa-star');
                    } else {
                        s.classList.remove('fa-star');
                        s.classList.add('fa-star-o');
                    }
                });
                
                // Update rating text
                ratingText.textContent = ratingTexts[rating];
            });
        });

        // Booking selection styling
        const bookingOptions = document.querySelectorAll('.booking-option');
        bookingOptions.forEach(option => {
            option.addEventListener('click', () => {
                const radio = option.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Update styling
                bookingOptions.forEach(opt => opt.classList.remove('selected'));
                option.classList.add('selected');
            });
        });

        // Form validation
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            if (!ratingInput.value) {
                e.preventDefault();
                alert('Please select a rating before submitting.');
            }
        });
    </script>
</body>
</html>