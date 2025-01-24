<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//function that generates csrf token
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_expiry']) || time() > $_SESSION['csrf_token_expiry']) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a secure random token
        $_SESSION['csrf_token_expiry'] = time() + 3600; // Set token expiry time (e.g., 1 hour)
    }
    return $_SESSION['csrf_token'];
}


//function that verifies csrf token
function verify_csrf_token($token) {
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        // Redirect to a custom CSRF error page
        header("Location: csrf_error.php");
        exit(); // Ensure no further execution after redirection
    }
}

// function verify_csrf_token($token) {
//     if (empty($token) || empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_expiry']) || time() > $_SESSION['csrf_token_expiry'] || !hash_equals($_SESSION['csrf_token'], $token)) {
//         die("CSRF token validation failed or expired."); // Block execution on invalid or expired token.
//     }
// }

?>
