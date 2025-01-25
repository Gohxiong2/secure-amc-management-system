<?php
require_once 'db_connect.php';
require_once 'security.php';

verifyAdminAccess();

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
    if (!in_array($department, ['Cybersecurity', 'AI'])) $errors[] = "Invalid department";

    if (empty($errors)) {
        try {
            // Insert student
            $stmt = $conn->prepare("INSERT INTO students 
                (user_id, name, email, phone, student_number, class_id, department) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            // Temporary user_id until proper user management is implemented
            $placeholder_user_id = 1;
            
            $stmt->bind_param("issssis", 
                $placeholder_user_id,
                $name,
                $email,
                $phone,
                $student_number,
                $class_id,
                $department
            );
            
            if ($stmt->execute()) {
                $student_id = $stmt->insert_id;
                
                // Insert student courses
                if (!empty($courses)) {
                    $courseStmt = $conn->prepare("INSERT INTO student_courses 
                        (student_id, course_id, status) VALUES (?, ?, 'start')");
                    
                    foreach ($courses as $course_id) {
                        if (!is_numeric($course_id)) {
                            throw new Exception("Invalid course ID");
                        }
                        $courseStmt->bind_param("ii", $student_id, $course_id);
                        $courseStmt->execute();
                    }
                }
                
                $_SESSION['success'] = "Student created successfully";
                header("Location: read_student.php");
                exit();
            }
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            $_SESSION['error'] = "Error creating student: " . $e->getMessage();
        }
    } else {
        $_SESSION['errors'] = $errors;
    }
}

// Fetch dropdown data
$classes = $conn->query("SELECT * FROM classes");
$courses = $conn->query("SELECT * FROM courses");
$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; margin-top: 50px; }
        .card { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-primary { background-color: #4da8da; border-color: #4da8da; }
        .btn-primary:hover { background-color: #357abd; border-color: #357abd; }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="card p-4">
            <h2 class="mb-4 text-primary">Create New Student</h2>
            
            <?php displayMessages(); ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Student Number</label>
                        <input type="text" name="student_number" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Class</label>
                        <select name="class_id" class="form-select">
                            <?php while($class = $classes->fetch_assoc()): ?>
                                <option value="<?= $class['class_id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-select">
                            <option value="Cybersecurity">Cybersecurity</option>
                            <option value="AI">AI</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Courses</label>
                        <div class="row g-2">
                            <?php while($course = $courses->fetch_assoc()): ?>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                            name="courses[]" value="<?= $course['course_id'] ?>">
                                        <label class="form-check-label">
                                            <?= htmlspecialchars($course['course_name']) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary px-4">Create Student</button>
                        <a href="read_student.php" class="btn btn-secondary px-4">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>