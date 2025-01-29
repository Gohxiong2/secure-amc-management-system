<?php
require_once 'db_connect.php';
require_once 'security.php';

//Database Connection Checks
verifyAuthentication();
validateDatabaseConnection($conn);

// Verify user role (admin and faculty only)
verifyAdminOrFacultyAccess();

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

    // Check deletion permissions
    if (!isAdmin()) {
        // Ensure faculty can only delete their own courses
        $faculty_check_query = "SELECT 1 FROM faculty WHERE user_id = ? AND course_id = ?";
        $faculty_check_stmt = $conn->prepare($faculty_check_query);
        $faculty_check_stmt->bind_param('ii', $_SESSION['user_id'], $course_id);
        $faculty_check_stmt->execute();
        $faculty_check_result = $faculty_check_stmt->get_result();

        if ($faculty_check_result->num_rows === 0) {
            $_SESSION['error_message'] = "You do not have permission to delete this course.";
            header("Location: read_course.php");
            exit();
        }
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
