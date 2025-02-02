<?php
require_once 'db_connect.php';
require_once 'security.php';

// Security & Authentication
verifyAuthentication();
verifyAdminOrFacultyAccess();
enforceSessionTimeout(600);

// Initialize messages
$error_message = "";
$success_message = "";

try {
    // Fetch students
    $stmt = $conn->prepare("SELECT student_id, name FROM students ORDER BY name");
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch courses based on role
    if ($_SESSION['role'] === 'faculty') {
        $stmt = $conn->prepare("SELECT course_id, course_name FROM courses 
                                WHERE course_id IN (SELECT course_id FROM faculty WHERE user_id = ?)");
        $stmt->bind_param('i', $_SESSION['user_id']);
    } else {
        $stmt = $conn->prepare("SELECT course_id, course_name FROM courses");
    }

    $stmt->execute();
    $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (Exception $e) {
    $_SESSION['error'] = "Error loading data.";
    header("Location: read_student_courses.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token']);

    $student_id = $_POST['student_id'];
    $course_id = $_POST['course_id'];
    $status = $_POST['status'];

    // Check for existing assignment
    $stmt = $conn->prepare("SELECT COUNT(*) FROM student_courses WHERE student_id = ? AND course_id = ?");
    $stmt->bind_param('ii', $student_id, $course_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $_SESSION['error'] = "This course is already assigned to the selected student.";
        header("Location: assign_student_courses.php");
        exit();
    }

    // Insert new assignment
    $stmt = $conn->prepare("INSERT INTO student_courses (student_id, course_id, status) VALUES (?, ?, ?)");
    $stmt->bind_param('iis', $student_id, $course_id, $status);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Course assigned successfully.";
        header("Location: read_student_courses.php");
        exit();
    }

    $stmt->close();
}

$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Courses</title>
    
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        .container { max-width: 800px; margin-top: 50px; }
        .card { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-primary { background-color: #4da8da; border-color: #4da8da; }
    </style>
</head>

<body class="bg-light">
    <div class="container">
        
        <!-- Title -->
        <div class="row justify-content-center mb-4">
            <div class="col-auto">
                <h2 class="text-primary text-center display-7 fw-bold">Assign Courses</h2>
            </div>
        </div>
        
        <div class="card p-4">
            
            <!-- Back Button -->
            <div class="mb-4">
                <a href="read_student_courses.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Student Courses
                </a>
            </div>

            <?php displayMessages(); ?>

            <!-- Form -->
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <!-- Student Selection -->
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

                <!-- Course Selection -->
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

                <!-- Status Selection -->
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value=""></option>
                        <option value="start">Start</option>
                        <option value="in-progress">In Progress</option>
                        <option value="ended">Ended</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary">Assign Course</button>
            </form>

        </div>
    </div>
</body>
</html>
