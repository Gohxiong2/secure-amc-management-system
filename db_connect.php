<?php
// Database configuration
$host = 'localhost';
$db = 'amc_student_management_system';
$user = 'root'; // Replace with your MySQL username
$pass = '';     // Replace with your MySQL password
$charset = 'utf8mb4';

// Attempt to connect to the database using MySQLi
$conn = mysqli_connect($host, $user, $pass, $db);

// Check if the connection was successful
if (!$conn) {
    die("Database connection failed!");
}

// Set character set for the connection
if (!mysqli_set_charset($conn, $charset)) {
    die("Error setting character set: " . mysqli_error($conn));
}

// Session management (add this if required for security across pages)
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']), // Enable only on HTTPS
        'use_strict_mode' => true,
        'use_only_cookies' => true,
    ]);
}

// For security and testing purposes, remove this in production
// echo "Database connection successful.";
?>
