<?php
include '../../includes/config.php';
checkAuth();

// Get month and year from query parameters or use current month
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Validate month and year
if ($month < 1 || $month > 12) $month = date('n');
if ($year < 2020 || $year > 2030) $year = date('Y');

// Calculate previous and next months
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// Get first day of the month and number of days
$firstDay = date('N', strtotime("$year-$month-01"));
$daysInMonth = date('t', strtotime("$year-$month-01"));

// Get bookings for the month
$startDate = "$year-$month-01";
$endDate = "$year-$month-$daysInMonth";

$stmt = $pdo->prepare("
    SELECT b.*, c.first_name, c.last_name, s.name as service_name, 
           t.first_name as tech_first, t.last_name as tech_last 
    FROM bookings b 
    JOIN customers c ON b.customer_id = c.id 
    JOIN services s ON b.service_id = s.id 
    LEFT JOIN technicians t ON b.technician_id = t.id 
    WHERE b.booking_date BETWEEN ? AND ? 
    AND b.status NOT IN ('cancelled')
    ORDER BY b.booking_date, b.start_time
");
$stmt->execute([$startDate, $endDate]);
$bookings = $stmt->fetchAll();

// Group bookings by date
$bookingsByDate = [];
foreach ($bookings as $booking) {
    $date = $booking['booking_date'];
    if (!isset($bookingsByDate[$date])) {
        $bookingsByDate[$date] = [];
    }
    $bookingsByDate[$date][] = $booking;
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Booking Calendar</h1>
                <div>
                    <a href="index.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-list me-1"></i> List View
                    </a>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> New Booking
                    </a>
                </div>
            </div>

            <!-- Calendar Navigation -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="calendar.php?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-chevron-left me-1"></i> Previous
                        </a>
                        
                        <h4 class="mb-0 text-center"><?php echo date('F Y', strtotime("$year-$month-01")); ?></h4>
                        
                        <a href="calendar.php?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-outline-primary">
                            Next <i class="fas fa-chevron-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Calendar -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 14.28%">Monday</th>
                                    <th style="width: 14.28%">Tuesday</th>
                                    <th style="width: 14.28%">Wednesday</th>
                                    <th style="width: 14.28%">Thursday</th>
                                    <th style="width: 14.28%">Friday</th>
                                    <th style="width: 14.28%">Saturday</th>
                                    <th style="width: 14.28%">Sunday</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $day = 1;
                                $totalCells = ceil(($firstDay + $daysInMonth - 1) / 7) * 7;
                                
                                for ($i = 1; $i <= $totalCells; $i++): 
                                    if ($i % 7 === 1) echo '<tr>';
                                    
                                    $isCurrentMonth = $i >= $firstDay && $day <= $daysInMonth;
                                    $currentDate = $isCurrentMonth ? sprintf("%04d-%02d-%02d", $year, $month, $day) : null;
                                    $isToday = $currentDate === date('Y-m-d');
                                    
                                    $cellClass = $isCurrentMonth ? '' : 'bg-light';
                                    if ($isToday) $cellClass .= ' bg-warning bg-opacity-10';
                                    ?>
                                    
                                    <td class="<?php echo $cellClass; ?>" style="height: 120px; vertical-align: top;">
                                        <?php if ($isCurrentMonth): ?>
                                            <div class="p-2">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="<?php echo $isToday ? 'fw-bold text-primary' : ''; ?>"><?php echo $day; ?></span>
                                                    <?php if ($isToday): ?>
                                                        <span class="badge bg-primary">Today</span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <?php if (isset($bookingsByDate[$currentDate])): ?>
                                                    <div class="booking-events">
                                                        <?php foreach ($bookingsByDate[$currentDate] as $booking): ?>
                                                            <div class="booking-event mb-1 p-1 rounded small" 
                                                                 style="background-color: <?php 
                                                                     switch ($booking['status']) {
                                                                         case 'pending': echo '#fff3cd'; break;
                                                                         case 'confirmed': echo '#d1ecf1'; break;
                                                                         case 'in-progress': echo '#d1e7ff'; break;
                                                                         case 'completed': echo '#d1e7dd'; break;
                                                                         default: echo '#f8d7da';
                                                                     }
                                                                 ?>; border-left: 3px solid <?php 
                                                                     switch ($booking['status']) {
                                                                         case 'pending': echo '#ffc107'; break;
                                                                         case 'confirmed': echo '#0dcaf0'; break;
                                                                         case 'in-progress': echo '#0d6efd'; break;
                                                                         case 'completed': echo '#198754'; break;
                                                                         default: echo '#dc3545';
                                                                     }
                                                                 ?>;">
                                                                <div class="fw-bold"><?php echo date('g:i A', strtotime($booking['start_time'])); ?></div>
                                                                <div class="text-truncate" title="<?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>">
                                                                    <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                                                                </div>
                                                                <div class="text-truncate" title="<?php echo htmlspecialchars($booking['service_name']); ?>">
                                                                    <?php echo htmlspecialchars($booking['service_name']); ?>
                                                                </div>
                                                                <a href="view.php?id=<?php echo $booking['id']; ?>" class="stretched-link"></a>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <?php 
                                    if ($isCurrentMonth) $day++;
                                    if ($i % 7 === 0) echo '</tr>';
                                endfor; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="card-title">Legend</h6>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="d-flex align-items-center">
                            <div style="width: 15px; height: 15px; background-color: #fff3cd; border-left: 3px solid #ffc107;" class="me-2"></div>
                            <span>Pending</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div style="width: 15px; height: 15px; background-color: #d1ecf1; border-left: 3px solid #0dcaf0;" class="me-2"></div>
                            <span>Confirmed</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div style="width: 15px; height: 15px; background-color: #d1e7ff; border-left: 3px solid #0d6efd;" class="me-2"></div>
                            <span>In Progress</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div style="width: 15px; height: 15px; background-color: #d1e7dd; border-left: 3px solid #198754;" class="me-2"></div>
                            <span>Completed</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>