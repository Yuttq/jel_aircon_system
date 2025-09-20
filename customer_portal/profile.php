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
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

if (!$customer) {
    header('Location: login.php');
    exit();
}

// Handle profile update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check if email already exists (for other customers)
            $emailCheck = $pdo->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
            $emailCheck->execute([$email, $customer_id]);
            
            if ($emailCheck->fetch()) {
                $error = 'This email address is already registered with another account.';
            } else {
                // Update customer profile
                $updateStmt = $pdo->prepare("
                    UPDATE customers 
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$first_name, $last_name, $email, $phone, $address, $customer_id]);
                
                $success = 'Profile updated successfully!';
                
                // Update session data
                $_SESSION['customer_name'] = $first_name . ' ' . $last_name;
                
                // Refresh customer data
                $stmt->execute([$customer_id]);
                $customer = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error = 'Error updating profile. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - JEL Air Conditioning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .profile-card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 8px 8px 0 0;
        }
        .avatar {
            width: 80px;
            height: 80px;
            background-color: rgba(255,255,255,0.2);
            border: 3px solid white;
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
                        <a class="nav-link active" href="profile.php">Profile</a>
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
                <div class="card profile-card">
                    <!-- Profile Header -->
                    <div class="profile-header text-center">
                        <div class="avatar rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3">
                            <span class="fs-3 fw-bold text-white">
                                <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                            </span>
                        </div>
                        <h3><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h3>
                        <p class="mb-0">Customer since <?php echo date('F Y', strtotime($customer['created_at'])); ?></p>
                    </div>

                    <!-- Profile Form -->
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($customer['phone']); ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" 
                                          placeholder="Enter your complete address"><?php echo htmlspecialchars($customer['address']); ?></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>

                        <div class="border-top mt-4 pt-4">
                            <h5 class="mb-3">Account Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Customer ID:</strong> <?php echo $customer['id']; ?></p>
                                    <p class="mb-1"><strong>Member since:</strong> <?php echo date('F j, Y', strtotime($customer['created_at'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Last updated:</strong> <?php echo date('F j, Y', strtotime($customer['created_at'])); ?></p>
                                    <p class="mb-0"><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Notice -->
                <div class="card profile-card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Security Information</h5>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Your privacy and security are important to us.</strong><br>
                            We never share your personal information with third parties. Your data is protected 
                            and only used to provide you with the best service experience.
                        </div>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-shield-alt text-success me-2"></i> Your information is encrypted and secure</li>
                            <li class="mb-2"><i class="fas fa-lock text-success me-2"></i> We don't store sensitive payment information</li>
                            <li><i class="fas fa-user-check text-success me-2"></i> You can request your data anytime</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2024 JEL Air Conditioning Services. All rights reserved.</p>
            <small>Need help? Contact support: <?php echo htmlspecialchars($customer['phone']); ?></small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>