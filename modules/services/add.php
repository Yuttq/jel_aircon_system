<?php
include '../../includes/config.php';
checkAuth();

$errors = [];
$success = '';

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
            $stmt = $pdo->prepare("INSERT INTO services (name, description, price, duration, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $duration, $status]);
            
            $success = 'Service added successfully!';
            $_POST = []; // Clear form
        } catch (PDOException $e) {
            $errors['database'] = 'Error adding service: ' . $e->getMessage();
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
                    <h5 class="card-title mb-0">Add New Service</h5>
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
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price (₱) *</label>
                                <input type="number" step="0.01" class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>" 
                                       id="price" name="price" 
                                       value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" required>
                                <?php if (isset($errors['price'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['price']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="duration" class="form-label">Duration (minutes) *</label>
                                <input type="number" class="form-control <?php echo isset($errors['duration']) ? 'is-invalid' : ''; ?>" 
                                       id="duration" name="duration" 
                                       value="<?php echo htmlspecialchars($_POST['duration'] ?? ''); ?>" required>
                                <?php if (isset($errors['duration'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['duration']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="status" name="status" <?php echo isset($_POST['status']) ? 'checked' : 'checked'; ?>>
                            <label class="form-check-label" for="status">Active Service</label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Add Service</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>