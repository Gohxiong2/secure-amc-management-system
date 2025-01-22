<?php
require_once 'db_connect.php';

// Authorization check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$student_id = $_GET['id'] ?? null;
if (!$student_id) die("Invalid student ID");

try {
    $pdo->beginTransaction();
    
    // Get user ID first
    $stmt = $pdo->prepare("SELECT user_id FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $user_id = $stmt->fetchColumn();

    // Delete student
    $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);

    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $pdo->commit();
    $_SESSION['success'] = "Student deleted successfully";
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Deletion failed: " . $e->getMessage();
}

header('Location: read_students.php');
exit;
?>