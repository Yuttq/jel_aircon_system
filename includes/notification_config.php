<?php
// Notification Configuration
define('NOTIFICATION_ENABLED', true);
define('EMAIL_NOTIFICATIONS', true);
define('SMS_NOTIFICATIONS', true);

// Email Configuration
define('EMAIL_FROM', 'noreply@jelaircon.com');
define('EMAIL_FROM_NAME', 'JEL Air Conditioning Services');
define('EMAIL_REPLY_TO', 'support@jelaircon.com');

// SMS Configuration (These would be replaced with actual SMS gateway credentials)
define('SMS_API_KEY', 'your_sms_api_key');
define('SMS_API_SECRET', 'your_sms_api_secret');
define('SMS_FROM_NUMBER', 'JELAirCon');

// Notification Templates
$notification_templates = [
    'booking_confirmation' => [
        'email_subject' => 'Booking Confirmation - JEL Air Conditioning',
        'email_template' => 'emails/booking_confirmation.html',
        'sms_template' => 'Your booking for {service} on {date} at {time} is confirmed. Thank you!'
    ],
    'booking_reminder' => [
        'email_subject' => 'Reminder: Upcoming Service - JEL Air Conditioning',
        'email_template' => 'emails/booking_reminder.html',
        'sms_template' => 'Reminder: {service} tomorrow at {time}. Please be available.'
    ],
    'status_update' => [
        'email_subject' => 'Booking Status Update - JEL Air Conditioning',
        'email_template' => 'emails/status_update.html',
        'sms_template' => 'Your booking status: {service} is now {status}.'
    ],
    'payment_confirmation' => [
        'email_subject' => 'Payment Received - JEL Air Conditioning',
        'email_template' => 'emails/payment_confirmation.html',
        'sms_template' => 'Payment of ₱{amount} received. Thank you!'
    ]
];

// Email Template Directory
define('EMAIL_TEMPLATE_DIR', __DIR__ . '/../templates/');
?>