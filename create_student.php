<?php
require_once 'db_connect.php';
require_once 'security.php';
verifyAdminAccess();

// Initialize variables
$errors = [];
$classes = [];
$courses = [];
$departments_result = mysqli_query($conn, "SELECT * FROM department");
$departments = mysqli_fetch_all($departments_result, MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    // Sanitize inputs
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $student_number = sanitizeInput($_POST['student_number']);
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    $department_id = filter_input(INPUT_POST, 'department_id', FILTER_VALIDATE_INT);
    $courses = $_POST['courses'] ?? [];
    $password = bin2hex(random_bytes(8)); // Generate random password

    // Validation
    if (empty($name)) $errors[] = "Name is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (!preg_match('/^[0-9]{8,15}$/', $phone)) $errors[] = "Phone must be 8-15 digits";
    if (!preg_match('/^[A-Za-z0-9]{8}$/', $student_number)) $errors[] = "Student number must be 8 alphanumeric characters";
    if ($department_id <= 0) $errors[] = "Invalid department";

    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        
        try {
            // 1. Create user account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users 
                (username, hashed_password, role) VALUES (?, ?, 'student')");
            mysqli_stmt_bind_param($stmt, 'ss', $student_number, $hashed_password);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("User creation failed: " . mysqli_error($conn));
            }
            $user_id = mysqli_insert_id($conn);

            // 2. Create student record
            $stmt = mysqli_prepare($conn, "INSERT INTO students 
                (user_id, name, email, phone, student_number, class_id, department_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");

            mysqli_stmt_bind_param($stmt, 'issssii', 
                $user_id,
                $name,
                $email,
                $phone,
                $student_number,
                $class_id,
                $department_id
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Student creation failed: " . mysqli_error($conn));
            }
            $student_id = mysqli_insert_id($conn);

            // 3. Assign courses
            if (!empty($courses)) {
                $stmt = mysqli_prepare($conn, "INSERT INTO student_courses 
                    (student_id, course_id, status) VALUES (?, ?, 'start')");
                
                foreach ($courses as $course_id) {
                    $course_id = filter_var($course_id, FILTER_VALIDATE_INT);
                    if (!$course_id) continue;
                    
                    mysqli_stmt_bind_param($stmt, 'ii', $student_id, $course_id);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Course assignment failed: " . mysqli_error($conn));
                    }
                }
            }

            mysqli_commit($conn);
            $_SESSION['success'] = "Student created successfully! Temporary password: $password";
            header("Location: read_student.php");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("Error: " . $e->getMessage());
            $_SESSION['error'] = "Error creating student: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode($errors);
    }
}

// Fetch dropdown data
$classes_result = mysqli_query($conn, "SELECT * FROM classes");
$classes = mysqli_fetch_all($classes_result, MYSQLI_ASSOC);

$courses_result = mysqli_query($conn, "SELECT * FROM courses");
$courses = mysqli_fetch_all($courses_result, MYSQLI_ASSOC);

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
                        <select name="class_id" class="form-select" required>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['class_id'] ?>">
                                    <?= htmlspecialchars($class['class_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select" required>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['department_id'] ?>">
                                        <?= htmlspecialchars($dept['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Courses</label>
                        <div class="row g-2">
                            <?php foreach ($courses as $course): ?>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                            name="courses[]" value="<?= $course['course_id'] ?>">
                                        <label class="form-check-label">
                                            <?= htmlspecialchars($course['course_name']) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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