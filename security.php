<?php

// CSRF

//function that generates csrf token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_expiry']) || time() > $_SESSION['csrf_token_expiry']) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a secure random token
        $_SESSION['csrf_token_expiry'] = time() + 3600; // Set token expiry time (e.g., 1 hour)
    }
    return $_SESSION['csrf_token'];
}

//function that verifies csrf token
function validateCsrfToken($token) {
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        // Redirect to a custom CSRF error page
        header("Location: csrf_error.php");
        exit(); // Ensure no further execution after redirection
    }
}

// Checks

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isFaculty() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'faculty';
}

// Input

function sanitizeInput($data) {
    $data = trim($data);                  // Remove extra spaces
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); // Convert special characters to HTML entities
    return $data;
}

//Verifications

function verifyAdminAccess() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: 403.php");
        die("Access denied");
    }
}

function verifyAuthentication() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

// Others

function displayMessages() {
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>';
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>';
        unset($_SESSION['success']);
    }
}

//what I have added (new).

//Grant access to faculty user using this function.

function verifyAdminOrFacultyAccess() {
    if (!isAdmin() && !isFaculty()) {
        header("Location: 403.php");
        exit();
    }
}

//Logout user after 5 minutes of inactivity.
function enforceSessionTimeout($timeout = 300) {
    // Check if last activity is set
    if (isset($_SESSION['last_activity'])) {
        $elapsed_time = time() - $_SESSION['last_activity']; // Calculate inactivity duration
        if ($elapsed_time > $timeout) {
            // Destroy the session and redirect to login
            session_unset();
            session_destroy();
            header("Location: login.php?error=session_expired");
            exit();
        }
    }
    // Update the last activity timestamp
    $_SESSION['last_activity'] = time();
}


