<?php
require_once 'db_connect.php';
require_once 'security.php';

verifyAdminOrFacultyAccess();

$student_id = $_GET['id'] ?? 0;
$student = [];
$selectedCourseIds = [];

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

    // Sanitize inputs
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $student_number = sanitizeInput($_POST['student_number']);
    $class_id = sanitizeInput($_POST['class_id']);
    $department = sanitizeInput($_POST['department']);
    $courses = $_POST['courses'] ?? [];

    // Validation
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (!preg_match('/^[0-9]{10,15}$/', $phone)) $errors[] = "Invalid phone format";
    if (!preg_match('/^[A-Za-z0-9]{8}$/', $student_number)) $errors[] = "Student number must be 8 alphanumeric characters";

    // Fetch department_id
    $departmentStmt = $conn->prepare("SELECT department_id FROM department WHERE name = ?");
    $departmentStmt->bind_param("s", $department);
    $departmentStmt->execute();
    $departmentResult = $departmentStmt->get_result();
    
    if ($departmentResult->num_rows === 0) {
        $errors[] = "Invalid department";
    } else {
        $departmentRow = $departmentResult->fetch_assoc();
        $department_id = $departmentRow['department_id'];
    }

    if (empty($errors)) {
        try {
            // Update student
            $stmt = $conn->prepare("UPDATE students SET 
                name = ?, email = ?, phone = ?, student_number = ?, 
                class_id = ?, department_id = ? 
                WHERE student_id = ?");
            
            $stmt->bind_param("ssssisi",
                $name,
                $email,
                $phone,
                $student_number,
                $class_id,
                $department_id,
                $student_id
            );
            
            if ($stmt->execute()) {
                // Update courses
                $conn->begin_transaction();
                
                try {
                    // Delete existing courses
                    $deleteStmt = $conn->prepare("DELETE FROM student_courses WHERE student_id = ?");
                    $deleteStmt->bind_param("i", $student_id);
                    $deleteStmt->execute();

                    // Insert new courses
                    if (!empty($courses)) {
                        $insertStmt = $conn->prepare("INSERT INTO student_courses 
                            (student_id, course_id, status) VALUES (?, ?, 'start')");
                        foreach ($courses as $course_id) {
                            if (!is_numeric($course_id)) {
                                throw new Exception("Invalid course ID");
                            }
                            $insertStmt->bind_param("ii", $student_id, $course_id);
                            $insertStmt->execute();
                        }
                    }
                    
                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                    throw $e;
                }
                
                $_SESSION['success'] = "Student updated successfully";
                header("Location: read_student.php");
                exit();
            }
        } catch (Exception $e) {
            error_log("Update error: " . $e->getMessage());
            $_SESSION['error'] = "Error updating student: " . $e->getMessage();
        }
    } else {
        $_SESSION['errors'] = $errors;
    }
}

// Fetch dropdown data
$classes = $conn->query("SELECT * FROM classes");
$allCourses = $conn->query("SELECT * FROM courses");
$departments = $conn->query("SELECT * FROM department");
$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; margin-top: 50px; }
        .card { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="card p-4">
            <h2 class="mb-4 text-primary">Edit Student</h2>
            
            <?php displayMessages(); ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" 
                            value="<?= htmlspecialchars($student['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" 
                            value="<?= htmlspecialchars($student['email'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control" 
                            value="<?= htmlspecialchars($student['phone'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Student Number</label>
                        <input type="text" name="student_number" class="form-control" 
                            value="<?= htmlspecialchars($student['student_number'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Class</label>
                        <select name="class_id" class="form-select" required>
                            <?php while($class = $classes->fetch_assoc()): ?>
                                <option value="<?= $class['class_id'] ?>" 
                                    <?= ($class['class_id'] == $student['class_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['class_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-select" required>
                            <?php while($dept = $departments->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($dept['name']) ?>"
                                    <?= ($dept['department_id'] == $student['department_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Courses</label>
                        <div class="row g-2">
                            <?php while($course = $allCourses->fetch_assoc()): ?>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                            name="courses[]" value="<?= $course['course_id'] ?>"
                                            <?= in_array($course['course_id'], $selectedCourseIds) ? 'checked' : '' ?>>
                                        <label class="form-check-label">
                                            <?= htmlspecialchars($course['course_name']) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary px-4">Update Student</button>
                        <a href="read_student.php" class="btn btn-secondary px-4">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>