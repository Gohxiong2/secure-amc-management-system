<?php
require_once 'db_connect.php';
require_once 'security.php';

verifyAuthentication();
verifyAdminAccess();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token']);

    $user_id = $_POST['user_id'] ?? 0;
    // Delete from users , the cascade will delete rest of student record

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