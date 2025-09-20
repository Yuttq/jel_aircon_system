<?php
require_once 'notification_config.php';

/**
 * Send email notification with template support
 */
function sendEmailNotification($to, $template_name, $template_data = []) {
    if (!NOTIFICATION_ENABLED || !EMAIL_NOTIFICATIONS) {
        error_log("Email notifications are disabled");
        return true; // Return true to prevent blocking operations
    }
    
    global $notification_templates;
    
    if (!isset($notification_templates[$template_name])) {
        error_log("Email template not found: $template_name");
        return false;
    }
    
    $template = $notification_templates[$template_name];
    $subject = $template['email_subject'];
    
    // Load and parse email template
    $template_file = EMAIL_TEMPLATE_DIR . $template['email_template'];
    if (!file_exists($template_file)) {
        error_log("Email template file not found: $template_file");
        return false;
    }
    
    $message = file_get_contents($template_file);
    $message = parseTemplate($message, $template_data);
    $subject = parseTemplate($subject, $template_data);
    
    // Headers
    $headers = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . EMAIL_REPLY_TO . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    // Log for development
    error_log("EMAIL NOTIFICATION: To: $to, Subject: $subject");
    
    // Uncomment for production:
    // return mail($to, $subject, $message, $headers);
    
    return true; // Simulate success for development
}

/**
 * Send SMS notification
 */
function sendSMSNotification($phone, $template_name, $template_data = []) {
    if (!NOTIFICATION_ENABLED || !SMS_NOTIFICATIONS) {
        error_log("SMS notifications are disabled");
        return true; // Return true to prevent blocking operations
    }
    
    global $notification_templates;
    
    if (!isset($notification_templates[$template_name])) {
        error_log("SMS template not found: $template_name");
        return false;
    }
    
    $template = $notification_templates[$template_name];
    $message = parseTemplate($template['sms_template'], $template_data);
    
    // Clean phone number (remove spaces, dashes, etc.)
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // For Philippines numbers, ensure proper format
    if (strlen($phone) === 10 && substr($phone, 0, 2) === '09') {
        $phone = '63' . substr($phone, 1); // Convert to international format
    }
    
    error_log("SMS NOTIFICATION: To: $phone, Message: $message");
    
    // In production, integrate with SMS gateway like Twilio, Nexmo, etc.
    // return sendViaSMSGateway($phone, $message);
    
    return true; // Simulate success for development
}

/**
 * Parse template with data
 */
function parseTemplate($template, $data) {
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
    }
    return $template;
}

/**
 * Send booking confirmation notification
 */
function sendBookingConfirmation($booking_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT b.*, c.first_name, c.last_name, c.email, c.phone, 
               s.name as service_name, s.price as service_price
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN services s ON b.service_id = s.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) return false;
    
    $template_data = [
        'first_name' => $booking['first_name'],
        'last_name' => $booking['last_name'],
        'service' => $booking['service_name'],
        'date' => date('F j, Y', strtotime($booking['booking_date'])),
        'time' => date('g:i A', strtotime($booking['start_time'])),
        'price' => '₱' . number_format($booking['service_price'], 2),
        'booking_id' => $booking['id']
    ];
    
    $email_sent = sendEmailNotification($booking['email'], 'booking_confirmation', $template_data);
    $sms_sent = sendSMSNotification($booking['phone'], 'booking_confirmation', $template_data);
    
    // Log notification
    logNotification($booking_id, 'booking_confirmation', $email_sent, $sms_sent);
    
    return $email_sent && $sms_sent;
}

/**
 * Send booking reminder notification
 */
function sendBookingReminder($booking_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT b.*, c.first_name, c.last_name, c.email, c.phone, 
               s.name as service_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN services s ON b.service_id = s.id
        WHERE b.id = ? AND b.status IN ('confirmed', 'pending')
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) return false;
    
    $template_data = [
        'first_name' => $booking['first_name'],
        'service' => $booking['service_name'],
        'date' => date('F j', strtotime($booking['booking_date'])),
        'time' => date('g:i A', strtotime($booking['start_time'])),
        'booking_id' => $booking['id']
    ];
    
    $email_sent = sendEmailNotification($booking['email'], 'booking_reminder', $template_data);
    $sms_sent = sendSMSNotification($booking['phone'], 'booking_reminder', $template_data);
    
    // Log notification
    logNotification($booking_id, 'booking_reminder', $email_sent, $sms_sent);
    
    return $email_sent && $sms_sent;
}

/**
 * Send status update notification
 */
function sendStatusUpdate($booking_id, $new_status) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT b.*, c.first_name, c.last_name, c.email, c.phone, 
               s.name as service_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN services s ON b.service_id = s.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) return false;
    
    $status_messages = [
        'confirmed' => 'confirmed',
        'in-progress' => 'in progress',
        'completed' => 'completed',
        'cancelled' => 'cancelled'
    ];
    
    $status_text = $status_messages[$new_status] ?? $new_status;
    
    $template_data = [
        'first_name' => $booking['first_name'],
        'service' => $booking['service_name'],
        'status' => $status_text,
        'date' => date('F j, Y', strtotime($booking['booking_date'])),
        'booking_id' => $booking['id']
    ];
    
    $email_sent = sendEmailNotification($booking['email'], 'status_update', $template_data);
    $sms_sent = sendSMSNotification($booking['phone'], 'status_update', $template_data);
    
    // Log notification
    logNotification($booking_id, 'status_update', $email_sent, $sms_sent);
    
    return $email_sent && $sms_sent;
}

/**
 * Send payment confirmation notification
 */
function sendPaymentConfirmation($payment_id) {
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
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch();
    
    if (!$payment) return false;
    
    $template_data = [
        'first_name' => $payment['first_name'],
        'service' => $payment['service_name'],
        'amount' => '₱' . number_format($payment['amount'], 2),
        'method' => ucfirst(str_replace('_', ' ', $payment['payment_method'])),
        'date' => date('F j, Y', strtotime($payment['payment_date'])),
        'booking_id' => $payment['booking_id']
    ];
    
    $email_sent = sendEmailNotification($payment['email'], 'payment_confirmation', $template_data);
    $sms_sent = sendSMSNotification($payment['phone'], 'payment_confirmation', $template_data);
    
    // Log notification
    logNotification($payment['booking_id'], 'payment_confirmation', $email_sent, $sms_sent);
    
    return $email_sent && $sms_sent;
}

/**
 * Log notification in database
 */
function logNotification($booking_id, $type, $email_sent, $sms_sent) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (booking_id, type, email_sent, sms_sent, sent_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$booking_id, $type, (int)$email_sent, (int)$sms_sent]);
    } catch (PDOException $e) {
        error_log("Error logging notification: " . $e->getMessage());
    }
}

/**
 * Schedule daily reminders for upcoming bookings
 */
function scheduleDailyReminders() {
    global $pdo;
    
    if (!NOTIFICATION_ENABLED) {
        return [];
    }
    
    // Get bookings happening tomorrow that haven't been reminded yet
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

/**
 * Get notification history for a booking
 */
function getNotificationHistory($booking_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE booking_id = ? 
        ORDER BY sent_at DESC
    ");
    $stmt->execute([$booking_id]);
    return $stmt->fetchAll();
}
?>