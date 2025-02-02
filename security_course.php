<?php

//CSRF protection

/* Generates a CSRF token and stores it in the session.
 token expires after 240 seconds (4 minutes). */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token']) || time() > $_SESSION['csrf_token_expiry']) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_expiry'] = time() + 480; // Expire in 8 minutes
    }
    return $_SESSION['csrf_token'];
}

/* Validates the CSRF token to prevent cross-site request forgery attacks.
   If the token is invalid or expired, the user is redirected to an error page. */
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token'], $_SESSION['csrf_token_expiry']) || time() > $_SESSION['csrf_token_expiry']) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_expiry']); // Remove expired token
        header("Location: csrf_error_course.php");
        exit();
    }

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        header("Location: csrf_error_course.php");
        exit();
    }

    regenerateCsrfToken(); // Regenerate only after successful validation
}

//Regenerates the CSRF token after successful validation.
function regenerateCsrfToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_expiry'] = time() + 480;
}


/* Ensures the user is logged and the sessions is set and regenerated before accessing restricted pages.
 If the user is not authenticated, they are redirected to the login page. */
function verifyAuthentication() {
    if (session_status() === PHP_SESSION_NONE) session_start();

    // Regenerate session ID only on new logins
    if (!isset($_SESSION['session_regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['session_regenerated'] = true;
    }

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

/*  If the user is inactive for the specified time (default 5 minutes),
their session is destroyed, and they are redirected to the login page. */

function enforceSessionTimeout($timeout = 600) {
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


//Sanitizes input to prevent XSS (Cross-Site Scripting) attacks.
function sanitizeInput($data) {
    $data = trim($data);                  // Remove extra spaces
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); // Convert special characters to HTML entities
    return $data;
}


/* Ensures that only admin users can access a page.
 Redirects unauthorized users to a "403 Forbidden" page. */
function verifyAdminAccess() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: 403.php");
        die("Access denied");
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



//Redirects the user to a course error page when an issue occurs.
function redirectCourseErrorPage(){
    header("Location: course_error.php");
    exit();
}






