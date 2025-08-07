<?php
/**
 * Configuration File for Nav Purush Boys Hostel Management System
 * Update these settings according to your environment
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '1521');
define('DB_SERVICE_NAME', 'XE');  // Change to your Oracle service name
define('DB_USERNAME', 'hostel_admin');
define('DB_PASSWORD', 'hostel123');

// Application Configuration
define('APP_NAME', 'Nav Purush Boys Hostel Management System');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'http://localhost/hostel-management');  // Update with your domain
define('APP_TIMEZONE', 'Asia/Kolkata');

// Security Configuration
define('SESSION_TIMEOUT', 3600);  // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('PASSWORD_MIN_LENGTH', 8);
define('ENABLE_AUDIT_LOG', true);

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 5242880);  // 5MB in bytes
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);
define('UPLOAD_PATH', 'uploads/');

// Email Configuration (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_ENCRYPTION', 'tls');

// Messaging Configuration
define('SMS_API_KEY', 'your-sms-api-key');
define('SMS_SENDER_ID', 'HOSTEL');

// Payment Gateway Configuration
define('PAYMENT_GATEWAY', 'razorpay');  // or 'paytm', 'stripe'
define('RAZORPAY_KEY_ID', 'your-razorpay-key');
define('RAZORPAY_KEY_SECRET', 'your-razorpay-secret');

// Reporting Configuration
define('REPORTS_PATH', 'reports/');
define('EXPORT_FORMATS', ['pdf', 'excel', 'csv']);
define('AUTO_BACKUP_ENABLED', true);
define('BACKUP_FREQUENCY', 'daily');  // daily, weekly, monthly

// System Features
define('ENABLE_VISITOR_TRACKING', true);
define('ENABLE_MESS_MANAGEMENT', true);
define('ENABLE_FEE_MANAGEMENT', true);
define('ENABLE_ROOM_ALLOCATION', true);
define('ENABLE_STUDENT_PORTAL', true);

// Notification Settings
define('EMAIL_NOTIFICATIONS', true);
define('SMS_NOTIFICATIONS', false);
define('PUSH_NOTIFICATIONS', false);

// API Configuration
define('API_ENABLED', true);
define('API_RATE_LIMIT', 100);  // requests per hour
define('API_KEY_REQUIRED', true);

// Debug Configuration
define('DEBUG_MODE', false);
define('LOG_LEVEL', 'ERROR');  // DEBUG, INFO, WARNING, ERROR
define('SHOW_ERRORS', false);

// Hostel Specific Settings
define('HOSTEL_NAME', 'Nav Purush Boys Hostel');
define('HOSTEL_ADDRESS', 'Your Hostel Address');
define('HOSTEL_PHONE', '+91-XXXXXXXXXX');
define('HOSTEL_EMAIL', 'info@navpurushhostel.com');
define('HOSTEL_WEBSITE', 'www.navpurushhostel.com');

// Academic Year Configuration
define('CURRENT_ACADEMIC_YEAR', '2024-25');
define('ACADEMIC_START_DATE', '2024-06-01');
define('ACADEMIC_END_DATE', '2025-05-31');

// Fee Structure (in INR)
define('DEFAULT_ROOM_FEE', 8000);
define('MESS_FEE_PER_MONTH', 3000);
define('SECURITY_DEPOSIT', 5000);
define('LATE_FEE_PERCENTAGE', 5);

// Room Configuration
define('DEFAULT_ROOM_CAPACITY', 2);
define('MAX_ROOM_CAPACITY', 4);
define('TOTAL_ROOMS', 50);
define('BLOCKS', ['A', 'B', 'C']);

// Visitor Settings
define('MAX_VISITOR_DURATION', 4);  // hours
define('VISITOR_REGISTRATION_REQUIRED', true);
define('SECURITY_ALERT_DURATION', 6);  // hours

// Mess Settings
define('MEALS_PER_DAY', 3);
define('MESS_TIMINGS', [
    'breakfast' => '07:00-09:00',
    'lunch' => '12:00-14:00',
    'dinner' => '19:00-21:00'
]);

// Maintenance Settings
define('AUTO_ROOM_CLEANING_SCHEDULE', 'weekly');
define('MAINTENANCE_NOTIFICATION_DAYS', 7);
define('BACKUP_RETENTION_DAYS', 30);

// Custom Functions
function getConfig($key) {
    return defined($key) ? constant($key) : null;
}

function isFeatureEnabled($feature) {
    $featureMap = [
        'visitor_tracking' => ENABLE_VISITOR_TRACKING,
        'mess_management' => ENABLE_MESS_MANAGEMENT,
        'fee_management' => ENABLE_FEE_MANAGEMENT,
        'room_allocation' => ENABLE_ROOM_ALLOCATION,
        'student_portal' => ENABLE_STUDENT_PORTAL
    ];
    
    return isset($featureMap[$feature]) ? $featureMap[$feature] : false;
}

function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

function getCurrentAcademicYear() {
    return CURRENT_ACADEMIC_YEAR;
}

function isDebugMode() {
    return DEBUG_MODE;
}

function logMessage($level, $message, $context = []) {
    if (isDebugMode() || $level === 'ERROR') {
        $logFile = 'logs/application.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message " . json_encode($context) . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

// Error handling
if (isDebugMode()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Session configuration
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
ini_set('session.cookie_lifetime', SESSION_TIMEOUT);

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

?> 