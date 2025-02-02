<?php

//CSRF protection

/* Generates a CSRF token and stores it in the session.
 token expires after 240 seconds (4 minutes). */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token']) || time() > $_SESSION['csrf_token_expiry']) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_expiry'] = time() + 240; // Expire in 30 minutes
    }
    return $_SESSION['csrf_token'];
}

/* Validates the CSRF token to prevent cross-site request forgery attacks.
   If the token is invalid or expired, the user is redirected to an error page. */
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token'], $_SESSION['csrf_token_expiry']) || time() > $_SESSION['csrf_token_expiry']) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_expiry']); // Remove expired token
        header("Location: csrf_error.php");
        exit();
    }

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        header("Location: csrf_error.php");
        exit();
    }

    regenerateCsrfToken(); // Regenerate only after successful validation
}

//Regenerates the CSRF token after successful validation.
function regenerateCsrfToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_expiry'] = time() + 240;
}



//Sanitizes input to prevent XSS (Cross-Site Scripting) attacks.
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}


/* Ensures that only admin users can access a page.
 Redirects unauthorized users to a "403 Forbidden" page. */
function verifyAdminAccess() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: 403.php");
        die("Access denied");
    }
}


/* Ensures the user is logged in and the sessions is set before accessing restricted pages.
 If the user is not authenticated, they are redirected to the login page. */
function verifyAuthentication() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

//  Checks if the current user has admin privileges.
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}




/*Ensures that only admins or faculty members can access a page.
  Redirects unauthorized users to a "403 Forbidden" page.*/
function verifyAdminOrFacultyAccess() {
    if (!isAdmin() && !isFaculty()) {
        header("Location: 403.php");
        exit();
    }
}

function isFaculty() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'faculty';
}

/*  If the user is inactive for the specified time (default 5 minutes),
their session is destroyed, and they are redirected to the login page. */

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



//Redirects the user to a course error page when an issue occurs.
function redirectCourseErrorPage(){
    header("Location: course_error.php");
    exit();
}






