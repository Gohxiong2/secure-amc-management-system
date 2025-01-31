<?php
require_once 'db_connect.php';
require_once 'security.php';

verifyAdminAccess();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token']);

    $user_id = $_POST['user_id'] ?? 0;
    
    try {
        // Check for existing courses
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM student_courses 
            WHERE student_id = ? AND status IN ('start', 'in-progress', 'ended')");
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
                $deleteUser = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                $deleteUser->bind_param("i", $user_id);
                $deleteUser->execute();
                
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
?>