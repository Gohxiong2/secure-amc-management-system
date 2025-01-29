<?php
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function verifyAdminAccess() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: 403.php");
        die("Access denied");
    }
}

function verifyAuthentication() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

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

function isFaculty() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'faculty';
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


// Function to safely validate the database connection
function validateDatabaseConnection($conn) {
    if (!isset($conn) || $conn === null) {
        $_SESSION['error_message'] = "Database connection error. Please try again later.";
        return false;
    }
    return true;
}

