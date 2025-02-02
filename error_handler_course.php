<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//custom error handlers redirects to course_error.php whenever an error occurs.

function handleError($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return; 
    }

    header("Location: course_error.php");
    exit();

}

function handleException($exception) {
    error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    header("Location: course_error.php");
    exit();
}

function handleFatalError() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
        header("Location: course_error.php");
        exit();
    }
}

// Register the custom error handlers
set_error_handler("handleError"); // Handles non-fatal errors
set_exception_handler("handleException");  // Handles uncaught exceptions
register_shutdown_function("handleFatalError"); // Handles fatal errors on script shutdown
?>
