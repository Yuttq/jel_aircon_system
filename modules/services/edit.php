<?php
include '../../includes/config.php';
checkAuth();

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$serviceId = $_GET['id'];
$errors = [];
$success = '';

// Fetch service data
$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
$stmt->execute([$serviceId]);
$service = $stmt->fetch();

if (!$service) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $duration = trim($_POST['duration']);
    $status = isset($_POST['status']) ? 1 : 0;

    // Validation
    if (empty($name)) {
        $errors['name'] = 'Service name is required';
    }
    
    if (empty($price) || !is_numeric($price) || $price <= 0) {
        $errors['price'] = 'Valid price is required';
    }
    
    if (empty($duration) || !is_numeric($duration) || $duration <= 0) {
        $errors['duration'] = 'Valid duration is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE services SET name = ?, description = ?, price = ?, duration = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $description, $price, $duration, $status, $serviceId]);
            
            $success = 'Service updated successfully!';
            
            // Refresh service data
            $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
            $stmt->execute([$serviceId]);
            $service = $stmt->fetch();
            
        } catch (PDOException $e) {
            $errors['database'] = 'Error updating service: ' . $e->getMessage();
        }
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Service</h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($errors['database'])): ?>
                        <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Service Name *</label>
                            <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                   id="name" name="name" 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? $service['name']); ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? $service['description']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price (₱) *</label>
                                <input type="number" step="0.01" class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>" 
                                       id="price" name="price" 
                                       value="<?php echo htmlspecialchars($_POST['price'] ?? $service['price']); ?>" required>
                                <?php if (isset($errors['price'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['price']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="duration" class="form-label">Duration (minutes) *</label>
                                <input type="number" class="form-control <?php echo isset($errors['duration']) ? 'is-invalid' : ''; ?>" 
                                       id="duration" name="duration" 
                                       value="<?php echo htmlspecialchars($_POST['duration'] ?? $service['duration']); ?>" required>
                                <?php if (isset($errors['duration'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['duration']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="status" name="status" <?php echo ($_POST['status'] ?? $service['status']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="status">Active Service</label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Back to List</a>
                            <button type="submit" class="btn btn-primary">Update Service</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>