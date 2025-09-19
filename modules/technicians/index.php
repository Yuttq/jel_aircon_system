<?php
include '../../includes/config.php';
checkAuth();

// Get all technicians with user information
$stmt = $pdo->prepare("
    SELECT t.*, u.username, u.role 
    FROM technicians t 
    LEFT JOIN users u ON t.user_id = u.id 
    ORDER BY t.first_name, t.last_name
");
$stmt->execute();
$technicians = $stmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Technician Management</h1>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add New Technician
                </a>
            </div>

            <!-- Technicians Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (count($technicians) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Technician</th>
                                        <th>Contact Information</th>
                                        <th>Specialization</th>
                                        <th>System Access</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($technicians as $tech): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-info rounded-circle text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                        <?php echo strtoupper(substr($tech['first_name'], 0, 1) . substr($tech['last_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?></h6>
                                                        <small class="text-muted">ID: <?php echo $tech['id']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?php echo htmlspecialchars($tech['phone']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($tech['email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($tech['specialization'] ?: 'General AC Services'); ?></td>
                                            <td>
                                                <?php if ($tech['username']): ?>
                                                    <span class="badge bg-success">Has Access</span>
                                                    <small class="text-muted d-block">Role: <?php echo ucfirst($tech['role']); ?></small>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No Access</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $tech['status'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $tech['status'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view.php?id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this technician?')">
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
                            <i class="fas fa-user-cog fa-3x text-muted mb-3"></i>
                            <h5>No technicians found</h5>
                            <p class="text-muted">Get started by adding your first technician.</p>
                            <a href="add.php" class="btn btn-primary">Add Technician</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>