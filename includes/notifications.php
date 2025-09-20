<?php
// notifications.php - Notification functions for JEL AirCon System

/**
 * Send email notification
 */
function sendEmailNotification($to, $subject, $message, $headers = '') {
    if (empty($headers)) {
        $headers = "From: JEL Air Conditioning <noreply@jelaircon.com>\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    // In a real implementation, you would use PHPMailer or similar
    // For now, we'll log the email and return true for testing
    error_log("EMAIL NOTIFICATION: To: $to, Subject: $subject, Message: $message");
    
    // Uncomment to actually send emails in production:
    // return mail($to, $subject, $message, $headers);
    
    return true; // Simulate success for development
}

/**
 * Send SMS notification (simulated - would integrate with SMS gateway)
 */
function sendSMSNotification($phone, $message) {
    // Simulate SMS sending - in production, integrate with Twilio, etc.
    error_log("SMS NOTIFICATION: To: $phone, Message: $message");
    return true;
}

/**
 * Send booking confirmation notification
 */
function sendBookingConfirmation($bookingId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT b.*, c.first_name, c.last_name, c.email, c.phone, 
               s.name as service_name, s.price as service_price
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN services s ON b.service_id = s.id
        WHERE b.id = ?
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    
    if (!$booking) return false;
    
    // Email notification
    $subject = "Booking Confirmation - JEL Air Conditioning";
    $message = "
        <h2>Booking Confirmed</h2>
        <p>Dear {$booking['first_name']},</p>
        <p>Your booking has been confirmed with the following details:</p>
        
        <table>
            <tr><td><strong>Service:</strong></td><td>{$booking['service_name']}</td></tr>
            <tr><td><strong>Date:</strong></td><td>" . date('F j, Y', strtotime($booking['booking_date'])) . "</td></tr>
            <tr><td><strong>Time:</strong></td><td>" . date('g:i A', strtotime($booking['start_time'])) . "</td></tr>
            <tr><td><strong>Price:</strong></td><td>₱" . number_format($booking['service_price'], 2) . "</td></tr>
        </table>
        
        <p>We will contact you if there are any changes to your booking.</p>
        <p>Thank you for choosing JEL Air Conditioning!</p>
    ";
    
    $emailSent = sendEmailNotification($booking['email'], $subject, $message);
    
    // SMS notification
    $smsMessage = "JEL AirCon: Your booking for {$booking['service_name']} on " . 
                  date('M j', strtotime($booking['booking_date'])) . " at " . 
                  date('g:i A', strtotime($booking['start_time'])) . " is confirmed. Thank you!";
    
    $smsSent = sendSMSNotification($booking['phone'], $smsMessage);
    
    return $emailSent && $smsSent;
}

/**
 * Send booking reminder notification
 */
function sendBookingReminder($bookingId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT b.*, c.first_name, c.last_name, c.email, c.phone, 
               s.name as service_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN services s ON b.service_id = s.id
        WHERE b.id = ? AND b.status IN ('confirmed', 'pending')
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    
    if (!$booking) return false;
    
    // Email reminder
    $subject = "Reminder: Upcoming Service - JEL Air Conditioning";
    $message = "
        <h2>Service Reminder</h2>
        <p>Dear {$booking['first_name']},</p>
        <p>This is a friendly reminder about your upcoming service:</p>
        
        <table>
            <tr><td><strong>Service:</strong></td><td>{$booking['service_name']}</td></tr>
            <tr><td><strong>Date:</strong></td><td>" . date('F j, Y', strtotime($booking['booking_date'])) . "</td></tr>
            <tr><td><strong>Time:</strong></td><td>" . date('g:i A', strtotime($booking['start_time'])) . "</td></tr>
        </table>
        
        <p>Please ensure someone will be available at the premises during the service time.</p>
        <p>If you need to reschedule, please contact us at least 24 hours in advance.</p>
    ";
    
    $emailSent = sendEmailNotification($booking['email'], $subject, $message);
    
    // SMS reminder
    $smsMessage = "JEL AirCon Reminder: {$booking['service_name']} scheduled for " . 
                  date('M j', strtotime($booking['booking_date'])) . " at " . 
                  date('g:i A', strtotime($booking['start_time'])) . ". Please be available.";
    
    $smsSent = sendSMSNotification($booking['phone'], $smsMessage);
    
    return $emailSent && $smsSent;
}

/**
 * Send status update notification
 */
function sendStatusUpdate($bookingId, $newStatus) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT b.*, c.first_name, c.last_name, c.email, c.phone, 
               s.name as service_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN services s ON b.service_id = s.id
        WHERE b.id = ?
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    
    if (!$booking) return false;
    
    $statusMessages = [
        'confirmed' => 'has been confirmed',
        'in-progress' => 'is now in progress',
        'completed' => 'has been completed',
        'cancelled' => 'has been cancelled'
    ];
    
    $message = $statusMessages[$newStatus] ?? 'status has been updated';
    
    $subject = "Booking Update - JEL Air Conditioning";
    $emailMessage = "
        <h2>Booking Status Update</h2>
        <p>Dear {$booking['first_name']},</p>
        <p>Your booking for <strong>{$booking['service_name']}</strong> on " . 
        date('F j, Y', strtotime($booking['booking_date'])) . " {$message}.</p>
        
        <p>Current Status: <strong>" . ucfirst($newStatus) . "</strong></p>
        
        <p>If you have any questions, please don't hesitate to contact us.</p>
    ";
    
    $emailSent = sendEmailNotification($booking['email'], $subject, $emailMessage);
    
    $smsMessage = "JEL AirCon: Your booking for {$booking['service_name']} {$message}. Status: " . ucfirst($newStatus);
    $smsSent = sendSMSNotification($booking['phone'], $smsMessage);
    
    return $emailSent && $smsSent;
}

/**
 * Send payment confirmation notification
 */
function sendPaymentConfirmation($paymentId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT p.*, b.booking_date, c.first_name, c.last_name, c.email, c.phone,
               s.name as service_name
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN customers c ON b.customer_id = c.id
        JOIN services s ON b.service_id = s.id
        WHERE p.id = ?
    ");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();
    
    if (!$payment) return false;
    
    $subject = "Payment Received - JEL Air Conditioning";
    $message = "
        <h2>Payment Confirmation</h2>
        <p>Dear {$payment['first_name']},</p>
        <p>Thank you for your payment. Here are the details:</p>
        
        <table>
            <tr><td><strong>Service:</strong></td><td>{$payment['service_name']}</td></tr>
            <tr><td><strong>Service Date:</strong></td><td>" . date('F j, Y', strtotime($payment['booking_date'])) . "</td></tr>
            <tr><td><strong>Amount Paid:</strong></td><td>₱" . number_format($payment['amount'], 2) . "</td></tr>
            <tr><td><strong>Payment Method:</strong></td><td>" . ucfirst($payment['payment_method']) . "</td></tr>
            <tr><td><strong>Payment Date:</strong></td><td>" . date('F j, Y g:i A', strtotime($payment['payment_date'])) . "</td></tr>
        </table>
        
        <p>We appreciate your business!</p>
    ";
    
    $emailSent = sendEmailNotification($payment['email'], $subject, $message);
    
    $smsMessage = "JEL AirCon: Payment of ₱" . number_format($payment['amount'], 2) . 
                  " for {$payment['service_name']} received. Thank you!";
    $smsSent = sendSMSNotification($payment['phone'], $smsMessage);
    
    return $emailSent && $smsSent;
}

/**
 * Schedule daily reminders for upcoming bookings
 */
function scheduleDailyReminders() {
    global $pdo;
    
    // Get bookings happening tomorrow
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    $stmt = $pdo->prepare("
        SELECT id FROM bookings 
        WHERE booking_date = ? 
        AND status IN ('confirmed', 'pending')
        AND reminder_sent = 0
    ");
    $stmt->execute([$tomorrow]);
    $bookings = $stmt->fetchAll();
    
    $results = [];
    foreach ($bookings as $booking) {
        $results[] = sendBookingReminder($booking['id']);
        
        // Mark as reminder sent
        $updateStmt = $pdo->prepare("UPDATE bookings SET reminder_sent = 1 WHERE id = ?");
        $updateStmt->execute([$booking['id']]);
    }
    
    return $results;
}
?>