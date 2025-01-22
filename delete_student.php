<?php
require_once 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$student_id = $_GET['id'] ?? null;
if (!$student_id) die("Invalid student ID");

try {
    mysqli_begin_transaction($conn);
    
    // Get user ID
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM students WHERE student_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $student_id);
    mysqli_stmt_execute($stmt);
    $user_id = mysqli_fetch_column(mysqli_stmt_get_result($stmt));

    // Delete student
    $stmt = mysqli_prepare($conn, "DELETE FROM students WHERE student_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $student_id);
    mysqli_stmt_execute($stmt);

    // Delete user
    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);

    mysqli_commit($conn);
    $_SESSION['success'] = "Student deleted successfully";
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = "Deletion failed: " . mysqli_error($conn);
}

header('Location: read_student.php');
exit;
?>