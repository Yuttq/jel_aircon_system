<?php
include '../../includes/config.php';
checkAuth();

// Date range filtering
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$group_by = $_GET['group_by'] ?? 'day';

// Validate dates
if (!strtotime($start_date)) $start_date = date('Y-m-01');
if (!strtotime($end_date)) $end_date = date('Y-m-t');
if ($start_date > $end_date) $start_date = $end_date;

// Get revenue data
$revenueQuery = "
    SELECT 
        " . ($group_by === 'day' ? "DATE(payment_date) as period" : "MONTH(payment_date) as period") . ",
        SUM(amount) as total_revenue,
        COUNT(*) as payment_count
    FROM payments 
    WHERE status = 'completed' 
    AND payment_date BETWEEN ? AND ?
    GROUP BY " . ($group_by === 'day' ? "DATE(payment_date)" : "MONTH(payment_date)") . "
    ORDER BY period
";

$revenueStmt = $pdo->prepare($revenueQuery);
$revenueStmt->execute([$start_date, $end_date . ' 23:59:59']);
$revenueData = $revenueStmt->fetchAll();

// Get revenue by payment method
$methodQuery = "
    SELECT 
        payment_method,
        SUM(amount) as total_revenue,
        COUNT(*) as payment_count
    FROM payments 
    WHERE status = 'completed' 
    AND payment_date BETWEEN ? AND ?
    GROUP BY payment_method
    ORDER BY total_revenue DESC
";

$methodStmt = $pdo->prepare($methodQuery);
$methodStmt->execute([$start_date, $end_date . ' 23:59:59']);
$methodData = $methodStmt->fetchAll();

// Get total summary
$summaryQuery = "
    SELECT 
        SUM(amount) as total_revenue,
        COUNT(*) as total_payments,
        AVG(amount) as avg_payment
    FROM payments 
    WHERE status = 'completed' 
    AND payment_date BETWEEN ? AND ?
";

$summaryStmt = $pdo->prepare($summaryQuery);
$summaryStmt->execute([$start_date, $end_date . ' 23:59:59']);
$summary = $summaryStmt->fetch();
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Revenue Reports</h1>
                <div>
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print Report
                    </button>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo htmlspecialchars($start_date); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo htmlspecialchars($end_date); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="group_by" class="form-label">Group By</label>
                            <select class="form-select" id="group_by" name="group_by">
                                <option value="day" <?php echo $group_by === 'day' ? 'selected' : ''; ?>>Daily</option>
                                <option value="month" <?php echo $group_by === 'month' ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="revenue.php" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Revenue</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        ₱<?php echo number_format($summary['total_revenue'], 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-peso-sign fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Payments</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $summary['total_payments']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-receipt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Average Payment</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        ₱<?php echo number_format($summary['avg_payment'], 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calculator fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Date Range</div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                                        <?php echo date('M j, Y', strtotime($start_date)); ?> - 
                                        <?php echo date('M j, Y', strtotime($end_date)); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Chart and Table -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Revenue Trend</h6>
                        </div>
                        <div class="card-body">
                            <?php if (count($revenueData) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th><?php echo $group_by === 'day' ? 'Date' : 'Month'; ?></th>
                                                <th>Number of Payments</th>
                                                <th>Total Revenue</th>
                                                <th>Average Payment</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($revenueData as $data): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($group_by === 'day'): ?>
                                                            <?php echo date('M j, Y', strtotime($data['period'])); ?>
                                                        <?php else: ?>
                                                            <?php echo date('F', mktime(0, 0, 0, $data['period'], 1)); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $data['payment_count']; ?></td>
                                                    <td>₱<?php echo number_format($data['total_revenue'], 2); ?></td>
                                                    <td>₱<?php echo number_format($data['total_revenue'] / $data['payment_count'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="font-weight-bold">
                                                <td>Total</td>
                                                <td><?php echo $summary['total_payments']; ?></td>
                                                <td>₱<?php echo number_format($summary['total_revenue'], 2); ?></td>
                                                <td>₱<?php echo number_format($summary['avg_payment'], 2); ?></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-4">No revenue data found for the selected period.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-success">Revenue by Payment Method</h6>
                        </div>
                        <div class="card-body">
                            <?php if (count($methodData) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Payment Method</th>
                                                <th>Revenue</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($methodData as $data): ?>
                                                <tr>
                                                    <td class="text-capitalize"><?php echo $data['payment_method']; ?></td>
                                                    <td>₱<?php echo number_format($data['total_revenue'], 2); ?></td>
                                                    <td>
                                                        <?php echo number_format(($data['total_revenue'] / $summary['total_revenue']) * 100, 1); ?>%
                                                        (<?php echo $data['payment_count']; ?> payments)
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-4">No payment method data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Options -->
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-info">Export Report</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                                <h5>Excel Export</h5>
                                <p>Export revenue data to Excel for further analysis</p>
                                <button class="btn btn-success" onclick="exportToExcel()">
                                    <i class="fas fa-download me-1"></i> Export to Excel
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <i class="fas fa-file-pdf fa-3x text-danger mb-3"></i>
                                <h5>PDF Export</h5>
                                <p>Generate a printable PDF version of this report</p>
                                <button class="btn btn-danger" onclick="window.print()">
                                    <i class="fas fa-file-pdf me-1"></i> Export to PDF
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <i class="fas fa-chart-bar fa-3x text-primary mb-3"></i>
                                <h5>Chart View</h5>
                                <p>View graphical representation of revenue data</p>
                                <button class="btn btn-primary" onclick="alert('Chart feature coming soon!')">
                                    <i class="fas fa-chart-line me-1"></i> View Charts
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportToExcel() {
    // Simple table export functionality
    alert('Excel export feature would be implemented here. This would typically download a CSV or Excel file of the revenue data.');
    
    // In a real implementation, this would redirect to an export script
    // window.location.href = 'export_revenue.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>';
}
</script>

<?php include '../../includes/footer.php'; ?>