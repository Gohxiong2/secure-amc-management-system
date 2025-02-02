<?php
require_once 'db_connect.php';
require_once 'security.php';

// Security Checks
verifyAuthentication();

// Verify user role (admin and faculty only)
verifyAdminOrFacultyAccess();

// Ensure request is POST and CSRF is valid
if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    // Retrieve and sanitize input
    $student_id = (int) $_POST['student_id'];
    $course_id = (int) $_POST['course_id'];

    try {
        // Verify that the student-course assignment exists
        $stmt = $conn->prepare("SELECT 1 FROM student_courses WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param('ii', $student_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $_SESSION['error'] = "Error: The selected assignment does not exist.";
        } else {
            // Proceed to delete the student-course assignment
            $delete_stmt = $conn->prepare("DELETE FROM student_courses WHERE student_id = ? AND course_id = ?");
            $delete_stmt->bind_param('ii', $student_id, $course_id);

            if ($delete_stmt->execute()) {
                $_SESSION['success'] = "The student was successfully unassigned from the course.";
            } else {
                $_SESSION['error'] = "Database error: Failed to delete the assignment.";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "An error occurred while processing the request.";
    }
}

// Redirect back to the student-course list
header("Location: read_student_courses.php");
exit();
