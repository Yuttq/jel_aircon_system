<?php
include '../../includes/config.php';
checkAuth();

// Date range filtering
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Validate dates
if (!strtotime($start_date)) $start_date = date('Y-m-01');
if (!strtotime($end_date)) $end_date = date('Y-m-t');
if ($start_date > $end_date) $start_date = $end_date;

// Get technician performance data
$techQuery = "
    SELECT 
        t.id,
        t.first_name,
        t.last_name,
        t.specialization,
        COUNT(b.id) as total_assignments,
        SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_assignments,
        SUM(CASE WHEN b.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_assignments,
        SUM(p.amount) as total_revenue,
        AVG(TIMESTAMPDIFF(MINUTE, b.start_time, b.end_time)) as avg_service_time
    FROM technicians t
    LEFT JOIN bookings b ON t.id = b.technician_id 
    LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'completed'
    WHERE (b.booking_date IS NULL OR b.booking_date BETWEEN ? AND ?)
    GROUP BY t.id, t.first_name, t.last_name, t.specialization
    ORDER BY total_revenue DESC
";

$techStmt = $pdo->prepare($techQuery);
$techStmt->execute([$start_date, $end_date . ' 23:59:59']);
$techData = $techStmt->fetchAll();

// Get summary statistics
$summaryQuery = "
    SELECT 
        COUNT(DISTINCT b.id) as total_bookings,
        COUNT(DISTINCT t.id) as active_technicians,
        SUM(p.amount) as total_revenue,
        AVG(TIMESTAMPDIFF(MINUTE, b.start_time, b.end_time)) as avg_service_time
    FROM bookings b
    LEFT JOIN technicians t ON b.technician_id = t.id
    LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'completed'
    WHERE b.booking_date BETWEEN ? AND ?
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
                <h1 class="h3 mb-0">Technician Performance Reports</h1>
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
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo htmlspecialchars($start_date); ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo htmlspecialchars($end_date); ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="technicians.php" class="btn btn-outline-secondary">Reset</a>
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
                                        Total Bookings</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $summary['total_bookings']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
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
                                        Active Technicians</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $summary['active_technicians']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-cog fa-2x text-gray-300"></i>
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
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Avg. Service Time</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($summary['avg_service_time'], 0); ?> mins
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Technician Performance Table -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Technician Performance Analysis</h6>
                </div>
                <div class="card-body">
                    <?php if (count($techData) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Technician</th>
                                        <th>Specialization</th>
                                        <th>Total Assignments</th>
                                        <th>Completed</th>
                                        <th>Cancelled</th>
                                        <th>Completion Rate</th>
                                        <th>Total Revenue</th>
                                        <th>Avg. Service Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($techData as $tech): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($tech['specialization'] ?: 'General'); ?></td>
                                            <td><?php echo $tech['total_assignments']; ?></td>
                                            <td><?php echo $tech['completed_assignments']; ?></td>
                                            <td><?php echo $tech['cancelled_assignments']; ?></td>
                                            <td>
                                                <?php if ($tech['total_assignments'] > 0): ?>
                                                    <?php echo number_format(($tech['completed_assignments'] / $tech['total_assignments']) * 100, 1); ?>%
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>₱<?php echo number_format($tech['total_revenue'], 2); ?></td>
                                            <td>
                                                <?php if ($tech['avg_service_time']): ?>
                                                    <?php echo number_format($tech['avg_service_time'], 0); ?> mins
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No technician data found for the selected period.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-info">Top Performing Technicians</h6>
                        </div>
                        <div class="card-body">
                            <?php 
                            $topTechnicians = array_slice($techData, 0, 5);
                            if (count($topTechnicians) > 0): ?>
                                <div class="list-group">
                                    <?php foreach ($topTechnicians as $index => $tech): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0"><?php echo ($index + 1) . '. ' . htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($tech['specialization'] ?: 'General'); ?></small>
                                                </div>
                                                <div class="text-end">
                                                    <strong>₱<?php echo number_format($tech['total_revenue'], 2); ?></strong><br>
                                                    <small><?php echo $tech['completed_assignments']; ?> completed jobs</small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">No technician data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-success">Efficiency Leaders</h6>
                        </div>
                        <div class="card-body">
                            <?php 
                            // Sort by completion rate
                            $efficientTechs = $techData;
                            usort($efficientTechs, function($a, $b) {
                                $aRate = $a['total_assignments'] > 0 ? ($a['completed_assignments'] / $a['total_assignments']) : 0;
                                $bRate = $b['total_assignments'] > 0 ? ($b['completed_assignments'] / $b['total_assignments']) : 0;
                                return $bRate <=> $aRate;
                            });
                            $efficientTechs = array_slice($efficientTechs, 0, 5);
                            ?>
                            <?php if (count($efficientTechs) > 0): ?>
                                <div class="list-group">
                                    <?php foreach ($efficientTechs as $index => $tech): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0"><?php echo ($index + 1) . '. ' . htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']); ?></h6>
                                                    <small class="text-muted"><?php echo $tech['total_assignments']; ?> assignments</small>
                                                </div>
                                                <div class="text-end">
                                                    <strong>
                                                        <?php if ($tech['total_assignments'] > 0): ?>
                                                            <?php echo number_format(($tech['completed_assignments'] / $tech['total_assignments']) * 100, 1); ?>%
                                                        <?php else: ?>
                                                            N/A
                                                        <?php endif; ?>
                                                    </strong><br>
                                                    <small>Completion rate</small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">No efficiency data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>