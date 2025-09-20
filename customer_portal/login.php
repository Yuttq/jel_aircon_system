<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['customer_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Validate customer credentials
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ? AND phone = ?");
    $stmt->execute([$email, $phone]);
    $customer = $stmt->fetch();
    
    if ($customer) {
        $_SESSION['customer_id'] = $customer['id'];
        $_SESSION['customer_name'] = $customer['first_name'] . ' ' . $customer['last_name'];
        $_SESSION['customer_email'] = $customer['email'];
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid email or phone number. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal Login - JEL Air Conditioning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
        }
        .login-card {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card login-card">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <img src="../assets/images/logo.png" alt="JEL Air Conditioning" height="60" class="mb-3">
                                <h2 class="card-title">Customer Portal</h2>
                                <p class="text-muted">Access your bookings and service history</p>
                            </div>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 py-2">Login to Portal</button>
                            </form>
                            
                            <div class="text-center mt-4">
                                <p class="mb-0">New customer? <a href="../modules/bookings/add.php">Book your first service</a></p>
                                <p class="mt-2"><a href="../">← Back to main website</a></p>
                            </div>
                            
                            <div class="mt-4 p-3 bg-light rounded">
                                <small class="text-muted">
                                    <strong>Demo Credentials:</strong><br>
                                    Use the email and phone number you used when booking a service.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>