<?php
require_once 'db_connect.php';
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
        $result = $checkStmt->get_result();
        $hasActiveCourses = $result->fetch_row()[0] > 0;

        if ($hasActiveCourses) {
            $_SESSION['error'] = "Cannot delete student with active courses";
        } else {
            // Delete from student_courses first
            $conn->begin_transaction();
            
            try {
                $deleteCourses = $conn->prepare("DELETE FROM student_courses WHERE student_id = ?");
                $deleteCourses->bind_param("i", $student_id);
                $deleteCourses->execute();
                
                // Delete from students
                $deleteStudent = $conn->prepare("DELETE FROM students WHERE student_id = ?");
                $deleteStudent->bind_param("i", $student_id);
                $deleteStudent->execute();
                
                $conn->commit();
                $_SESSION['success'] = "Student deleted successfully";
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
        }
    } catch (Exception $e) {
        error_log("Delete error: " . $e->getMessage());
        $_SESSION['error'] = "Error deleting student: " . $e->getMessage();
    }
    
    header("Location: read_student.php");
    exit();
}