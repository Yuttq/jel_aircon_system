<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/config.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['customer_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Redirect to login page
header('Location: login.php');
exit();
?>