<?php
include 'db_connect.php';
require_once 'security.php';

verifyAdminAccess();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $student_id = $_POST['student_id'] ?? 0;
    
    try {
        // Check for existing courses
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM student_courses 
            WHERE student_id = ? AND status IN ('start', 'in-progress')");
        $checkStmt->bind_param("i", $student_id);
        $checkStmt->execute();
        $hasActiveCourses = $checkStmt->get_result()->fetch_row()[0] > 0;

        if ($hasActiveCourses) {
            $_SESSION['error'] = "Cannot delete student with active courses";
        } else {
            // Delete student
            $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
            $stmt->bind_param("i", $student_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Student deleted successfully";
            } else {
                $_SESSION['error'] = "Error deleting student";
            }
        }
    } catch (Exception $e) {
        error_log("Delete error: " . $e->getMessage());
        $_SESSION['error'] = "Database error";
    }
    
    header("Location: read_student.php");
    exit();
}