<?php
session_start();
require 'db_connect.php';


// Ensure database connection exists
if (!isset($conn) || $conn === null) {
    $_SESSION['error_message'] = "Database connection error. Please try again later.";
    header("Location: read_course.php");
    exit();
}

/*
Enforce session timeout to log out users after a period of inactivity.
timeout Time in seconds before the session expires (1800 seconds or 30 minutes).
*/

function enforce_session_timeout($timeout = 1800) {
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

// This function ensures that only logged-in users can access this page.
// If the user is not logged in, they are redirected to the login page.
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        // Set an error message and redirect to login
        // $_SESSION['error_message'] = "You must be logged in to access this page."; //commented this because of the error.Remove later.
        header("Location: login.php");
        exit();
    }
}

// Add this function to check if the user's role is authorized to access the page
function check_role_access($allowed_roles) {
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // Redirect unauthorized users to a 403 page or display an error
        // $_SESSION['error_message'] = "You do not have permission to access this page.";//commented this because of the error.Remove later.
        header("Location: 403.php");
        exit(); // Stop further script execution
    }
}


check_login(); // Call the function to enforce login requirement
enforce_session_timeout(); // Enforce session timeout (default is 30 minutes)
check_role_access(['admin', 'faculty']); // Allow only admin and faculty roles view courses

// Verifies if the user has permission to delete a course.
// Admins can delete any course, while faculty can delete only their assigned courses.
function check_permission($conn, $user_id, $role, $course_id) {
    if ($role === 'admin') {
        return true;
    }

    if ($role === 'faculty') {
        $query = "SELECT 1 FROM faculty WHERE user_id = ? AND course_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $user_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['course_id'])) {
    // Validate and sanitize course_id
    $course_id = (int)$_GET['course_id'];

    if ($course_id <= 0) {
        $_SESSION['error_message'] = "Invalid course ID. Please try again.";
        header("Location: read_course.php");
        exit();
    }

    // Check if the course is assigned to students
    $check_assignment_query = "SELECT 1 FROM student_courses WHERE course_id = ?";
    $check_assignment_stmt = $conn->prepare($check_assignment_query);
    $check_assignment_stmt->bind_param('i', $course_id);
    $check_assignment_stmt->execute();
    $check_assignment_result = $check_assignment_stmt->get_result();

    if ($check_assignment_result->num_rows > 0) {
        // Course is assigned to students, cannot delete
        $_SESSION['error_message'] = "Unable to delete course. It is already assigned to students.";
        header("Location: read_course.php");
        exit();
    }

    // Proceed with deletion if no assignments exist
    // Confirm the course exists
    $course_check_query = "SELECT 1 FROM courses WHERE course_id = ?";
    $course_check_stmt = $conn->prepare($course_check_query);
    $course_check_stmt->bind_param('i', $course_id);
    $course_check_stmt->execute();
    $course_check_result = $course_check_stmt->get_result();

    if ($course_check_result->num_rows === 0) {
        $_SESSION['error_message'] = "The selected course does not exist.";
        header("Location: read_course.php");
        exit();
    }

    try {
        $conn->begin_transaction();

        // Delete associated faculty entries
        $faculty_query = "DELETE FROM faculty WHERE course_id = ?";
        $faculty_stmt = $conn->prepare($faculty_query);
        $faculty_stmt->bind_param('i', $course_id);
        $faculty_stmt->execute();

        // Delete the course
        $course_query = "DELETE FROM courses WHERE course_id = ?";
        $course_stmt = $conn->prepare($course_query);
        $course_stmt->bind_param('i', $course_id);
        $course_stmt->execute();

        $conn->commit();
        $_SESSION['success_message'] = "The course has been successfully deleted.";
        header("Location: read_course.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log($e->getMessage());
        $_SESSION['error_message'] = "An error occurred while deleting the course. Please try again later.";
        header("Location: read_course.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "Invalid request. Please use the delete button provided.";
    header("Location: read_course.php");
    exit();
}
?>
