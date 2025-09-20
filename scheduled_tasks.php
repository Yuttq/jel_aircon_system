<?php
// scheduled_tasks.php - Run this daily via cron job
require_once 'includes/config.php';
require_once 'includes/notifications.php';

// Log execution
file_put_contents('scheduler.log', date('Y-m-d H:i:s') . " - Starting scheduled tasks\n", FILE_APPEND);

// Run daily reminders
echo "Sending daily reminders...\n";
$results = scheduleDailyReminders();
$successCount = count(array_filter($results));

file_put_contents('scheduler.log', date('Y-m-d H:i:s') . " - Sent $successCount reminders\n", FILE_APPEND);
echo "Sent $successCount reminders\n";

// Additional scheduled tasks can be added here
// Example: Weekly reports, monthly maintenance reminders, etc.

file_put_contents('scheduler.log', date('Y-m-d H:i:s') . " - Scheduled tasks completed\n", FILE_APPEND);
echo "Scheduled tasks completed successfully.\n";
?>