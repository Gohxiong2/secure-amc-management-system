<?php
include 'db_connect.php';
require_once 'security.php';

verifyAdminAccess();

$student_id = $_GET['id'] ?? 0;
$student = [];
$courses = [];

// Fetch student data
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Fetch existing courses
$courseStmt = $conn->prepare("SELECT course_id FROM student_courses WHERE student_id = ?");
$courseStmt->bind_param("i", $student_id);
$courseStmt->execute();
$selectedCourses = $courseStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$selectedCourseIds = array_column($selectedCourses, 'course_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    // Sanitize and validate inputs (similar to create)
    // ... (omitted for brevity, similar to create_student.php)

    // Update student record
    try {
        $stmt = $conn->prepare("UPDATE students SET 
            name = ?, email = ?, phone = ?, student_number = ?, 
            class_id = ?, department = ? 
            WHERE student_id = ?");
        
        $stmt->bind_param("ssssisi",
            $_POST['name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['student_number'],
            $_POST['class_id'],
            $_POST['department'],
            $student_id
        );
        
        if ($stmt->execute()) {
            // Update courses (delete existing and insert new)
            $conn->query("DELETE FROM student_courses WHERE student_id = $student_id");
            if (!empty($_POST['courses'])) {
                $insertStmt = $conn->prepare("INSERT INTO student_courses 
                    (student_id, course_id, status) VALUES (?, ?, 'start')");
                foreach ($_POST['courses'] as $course_id) {
                    $insertStmt->bind_param("ii", $student_id, $course_id);
                    $insertStmt->execute();
                }
            }
            
            $_SESSION['success'] = "Student updated successfully";
            header("Location: read_student.php");
            exit();
        }
    } catch (Exception $e) {
        error_log("Update error: " . $e->getMessage());
        $_SESSION['error'] = "Error updating student";
    }
}

// Fetch dropdown data
$classes = $conn->query("SELECT * FROM classes");
$allCourses = $conn->query("SELECT * FROM courses");
$csrf_token = generateCsrfToken();
?>