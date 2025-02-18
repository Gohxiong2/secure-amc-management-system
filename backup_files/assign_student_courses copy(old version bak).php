<?php
include 'db_connect.php';
require_once 'security.php';

verifyAuthentication();

// Verify faculty/admin access
if (!in_array($_SESSION['role'], ['faculty', 'admin'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$message_type = '';

try {
    // Fetch students
    $students = $conn->query("SELECT student_id, name FROM students ORDER BY name")->fetch_all(MYSQLI_ASSOC);

    // Fetch courses based on role
    $courses_query = "SELECT course_id, course_name FROM courses";
    if ($_SESSION['role'] === 'faculty') {
        $courses_query .= " WHERE course_id IN (SELECT course_id FROM faculty WHERE user_id = ?)";
        $stmt = $conn->prepare($courses_query);
        $stmt->bind_param('i', $_SESSION['user_id']);
    } else {
        $stmt = $conn->prepare($courses_query);
    }
    $stmt->execute();
    $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Error fetching data: " . $e->getMessage());
    $_SESSION['error'] = "Error loading data";
    header("Location: read_student_courses.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $student_id = sanitizeInput($_POST['student_id']);
    $course_id = sanitizeInput($_POST['course_id']);
    $status = sanitizeInput($_POST['status']);

    try {
        // Check for existing assignment
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM student_courses WHERE student_id = ? AND course_id = ?");
        $check_stmt->bind_param('ii', $student_id, $course_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            // Duplicate assignment found
            $_SESSION['error'] = "This course is already assigned to the selected student.";
            header("Location: assign_student_courses.php");
            exit();
        }

        // If no duplicate, proceed with insertion
        $stmt = $conn->prepare("INSERT INTO student_courses (student_id, course_id, status) VALUES (?, ?, ?)");
        $stmt->bind_param('iis', $student_id, $course_id, $status);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Course assigned successfully";
            header("Location: read_student_courses.php");
            exit();
        }
    } catch (Exception $e) {
        error_log("Assignment error: " . $e->getMessage());
        $_SESSION['error'] = "Error assigning course";
        header("Location: assign_student_courses.php");
        exit();
    }
}

$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Courses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; margin-top: 50px; }
        .card { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-primary { background-color: #4da8da; border-color: #4da8da; }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <!-- Centered Title -->
        <div class="row justify-content-center mb-4">
            <div class="col-auto">
                <h2 class="text-primary text-center display-7 fw-bold">Assign Courses</h2>
            </div>
        </div>
        <div class="card p-4">
            <div class="header-container mb-4">
                <a href="read_student_courses.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Student Courses
                </a>
            </div>

            <?php displayMessages(); ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="mb-3">
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['student_id'] ?>">
                                <?= htmlspecialchars($student['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Course</label>
                    <select name="course_id" class="form-select" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['course_id'] ?>">
                                <?= htmlspecialchars($course['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value=""></option>
                        <option value="start">Start</option>
                        <option value="in-progress">In Progress</option>
                        <option value="ended">Ended</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Assign Course</button>
            </form>
        </div>
    </div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</body>
</html>