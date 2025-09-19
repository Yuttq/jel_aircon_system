<?php
include '../../includes/config.php';
checkAuth();

// Get all services
$stmt = $pdo->prepare("SELECT * FROM services ORDER BY name");
$stmt->execute();
$services = $stmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Service Management</h1>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add New Service
                </a>
            </div>

            <!-- Services Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (count($services) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Service Name</th>
                                        <th>Description</th>
                                        <th>Price</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $service): ?>
                                        <tr>
                                            <td>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($service['name']); ?></h6>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($service['description'], 0, 80) . (strlen($service['description']) > 80 ? '...' : '')); ?></td>
                                            <td>₱<?php echo number_format($service['price'], 2); ?></td>
                                            <td><?php echo $service['duration']; ?> minutes</td>
                                            <td>
                                                <span class="badge bg-<?php echo $service['status'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $service['status'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="edit.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this service?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                            <h5>No services found</h5>
                            <p class="text-muted">Get started by adding your first service.</p>
                            <a href="add.php" class="btn btn-primary">Add Service</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>