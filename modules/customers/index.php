<?php
include '../../includes/config.php';
checkAuth();

// Search functionality
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM customers WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

// Add sorting
$query .= " ORDER BY first_name, last_name";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Customer Management</h1>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add New Customer
                </a>
            </div>

            <!-- Search and Filter Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="search" placeholder="Search customers..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                        <div class="col-md-2">
                            <a href="index.php" class="btn btn-outline-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Customers Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (count($customers) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Contact Information</th>
                                        <th>Address</th>
                                        <th>Date Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary rounded-circle text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                        <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h6>
                                                        <small class="text-muted">ID: <?php echo $customer['id']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?php echo htmlspecialchars($customer['phone']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($customer['address'], 0, 50) . (strlen($customer['address']) > 50 ? '...' : '')); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this customer?')">
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
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No customers found</h5>
                            <p class="text-muted">Get started by adding your first customer.</p>
                            <a href="add.php" class="btn btn-primary">Add Customer</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>