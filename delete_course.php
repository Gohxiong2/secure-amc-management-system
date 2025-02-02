<?php
require_once 'db_connect.php';
require 'security_course.php';
require 'error_handler_course.php';

//Security & Authentication Checks
verifyAuthentication(); // Ensures the user is logged in and the session is started if it has not been started
enforceSessionTimeout(600);// Log out users after 10 minutes of inactivity
verifyAdminAccess(); // Allow only admin users to access this page


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['course_id'])) {
    // Validate and sanitize course_id
    $course_id = (int)$_GET['course_id'];


    // Check if the course is assigned to students
    $check_assignment_query = "SELECT 1 FROM student_courses WHERE course_id = ?";
    $check_assignment_stmt = $conn->prepare($check_assignment_query);
    $check_assignment_stmt->bind_param('i', $course_id);
    $check_assignment_stmt->execute();
    $check_assignment_result = $check_assignment_stmt->get_result();

    if ($check_assignment_result->num_rows > 0) {
       // Prevent deletion if the course is assigned to students
        $_SESSION['error_message'] = "Unable to delete course. It is already assigned to students.";
        header("Location: read_course.php");
        exit();
    }

    // Confirm the course exists before attempting deletion
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
        // Begin a transaction: This ensures that both deletions (faculty + course) succeed together
        $conn->begin_transaction();

        // Delete rows related to this course in faculty table if its been created by faculty users.
        $faculty_query = "DELETE FROM faculty WHERE course_id = ?";
        $faculty_stmt = $conn->prepare($faculty_query);
        $faculty_stmt->bind_param('i', $course_id);
        $faculty_stmt->execute();

        // Delete the course from database
        $course_query = "DELETE FROM courses WHERE course_id = ?";
        $course_stmt = $conn->prepare($course_query);
        $course_stmt->bind_param('i', $course_id);
        $course_stmt->execute();

        // If both deletions were successful, confirm (commit) the changes to the database
        $conn->commit();

        $_SESSION['success_message'] = "The course has been successfully deleted.";
        header("Location: read_course.php");
        exit();

    } catch (Exception $e) {
        // Undo changes if something goes wrong
        $conn->rollback();
        $_SESSION['error_message'] = "An error occurred while deleting the course. Please try again later.";
        header("Location: read_course.php");
        exit();
    }
} else {
    // Stop direct access without course_id
    $_SESSION['error_message'] = "Invalid request. Please use the delete button provided.";
    header("Location: read_course.php");
    exit();
}
?>