<?php
include 'includes/config.php';
checkAuth();
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h2">Dashboard</h1>
            <p class="text-muted">Welcome, <?php echo $_SESSION['full_name']; ?>!</p>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Statistics Cards -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Today's Bookings</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE()");
                                $stmt->execute();
                                echo $stmt->fetchColumn();
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
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
                                Pending Bookings</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
                                $stmt->execute();
                                echo $stmt->fetchColumn();
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                Total Customers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers");
                                $stmt->execute();
                                echo $stmt->fetchColumn();
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                                Revenue (Monthly)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ₱
                                <?php
                                $stmt = $pdo->prepare("
                                    SELECT COALESCE(SUM(p.amount), 0) 
                                    FROM payments p 
                                    JOIN bookings b ON p.booking_id = b.id 
                                    WHERE p.status = 'completed' 
                                    AND MONTH(p.payment_date) = MONTH(CURDATE())
                                    AND YEAR(p.payment_date) = YEAR(CURDATE())
                                ");
                                $stmt->execute();
                                echo number_format($stmt->fetchColumn(), 2);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-peso-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Bookings -->
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Bookings</h6>
                    <a href="modules/bookings/" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="recentBookings" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Date & Time</th>
                                    <th>Technician</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Since we don't have bookings yet, let's modify this to handle empty state
                                $stmt = $pdo->prepare("
                                    SELECT b.*, c.first_name, c.last_name, s.name as service_name, t.first_name as tech_first, t.last_name as tech_last
                                    FROM bookings b
                                    JOIN customers c ON b.customer_id = c.id
                                    JOIN services s ON b.service_id = s.id
                                    LEFT JOIN technicians t ON b.technician_id = t.id
                                    ORDER BY b.created_at DESC LIMIT 5
                                ");
                                
                                try {
                                    $stmt->execute();
                                    $bookings = $stmt->fetchAll();
                                    
                                    if (count($bookings) > 0) {
                                        foreach ($bookings as $booking) {
                                            echo "<tr>";
                                            echo "<td>{$booking['first_name']} {$booking['last_name']}</td>";
                                            echo "<td>{$booking['service_name']}</td>";
                                            echo "<td>{$booking['booking_date']} {$booking['start_time']}</td>";
                                            echo "<td>";
                                            if ($booking['tech_first']) {
                                                echo "{$booking['tech_first']} {$booking['tech_last']}";
                                            } else {
                                                echo "<span class='text-muted'>Not assigned</span>";
                                            }
                                            echo "</td>";
                                            echo "<td><span class='badge bg-".getStatusBadge($booking['status'])."'>".ucfirst($booking['status'])."</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center py-3'>No bookings found. <a href='modules/bookings/add.php'>Create your first booking</a></td></tr>";
                                    }
                                    
                                } catch (PDOException $e) {
                                    echo "<tr><td colspan='5' class='text-center py-3'>No bookings data available yet.</td></tr>";
                                }
                                
                                function getStatusBadge($status) {
                                    switch ($status) {
                                        case 'pending': return 'warning';
                                        case 'confirmed': return 'info';
                                        case 'in-progress': return 'primary';
                                        case 'completed': return 'success';
                                        case 'cancelled': return 'danger';
                                        default: return 'secondary';
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>