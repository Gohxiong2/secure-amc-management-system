<?php
require_once 'db_connect.php';
require_once 'security.php';

//Database Connection Checks
verifyAuthentication();

// Verify user role (admin and faculty only)
verifyAdminOrFacultyAccess();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token']);

    $student_id = sanitizeInput($_POST['student_id']);
    $course_name = sanitizeInput($_POST['course_name']);

    try {
        // Verify assignment exists
        $stmt = $conn->prepare("SELECT sc.course_id 
                              FROM student_courses sc
                              JOIN courses c ON sc.course_id = c.course_id
                              WHERE sc.student_id = ? AND c.course_name = ?");
        $stmt->bind_param('is', $student_id, $course_name);
        $stmt->execute();
        $course_id = $stmt->get_result()->fetch_row()[0];

        if ($course_id) {
            // Delete assignment
            $delete_stmt = $conn->prepare("DELETE FROM student_courses 
                                         WHERE student_id = ? AND course_id = ?");
            $delete_stmt->bind_param('ii', $student_id, $course_id);
            
            if ($delete_stmt->execute()) {
                $_SESSION['success'] = "Assignment deleted successfully";
            }
        }
    } catch (Exception $e) {
        error_log("Delete error: " . $e->getMessage());
        $_SESSION['error'] = "Error deleting assignment";
    }
}

header("Location: read_student_courses.php");
exit();