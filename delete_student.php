<?php
// Get modular functions
require_once 'db_connect.php';
require_once 'security.php';

//Only verified users. Page can only be access by admin
verifyAuthentication();
verifyAdminAccess();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token']); // Prevent CSRF attacks

    // Delete student, along with it's courses , record, and user account
    $user_id = $_POST['user_id'] ?? 0;

    // Delete from users table , the sql cascade will delete rest of student record
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success'] = "Student deleted successfully";
    } catch (Exception $e) {
        error_log("Delete error: " . $e->getMessage());
        $_SESSION['error'] = "Error deleting student: " . $e->getMessage();
        exit();
    }
    
    header("Location: read_student.php");
    exit();
}
?>