<?php
require_once 'db_connect.php';
require_once 'security.php';

verifyAuthentication();
verifyAdminOrFacultyAccess();
enforceSessionTimeout(300);

$student_id = $_GET['id'] ?? 0;
if (filter_var($student_id, FILTER_VALIDATE_INT) === false) {
    die("Invalid student ID.");
}
$user_id = $_SESSION['user_id'] ?? 0;

$isFaculty = isFaculty();

if ($isFaculty) {
    $stmt = $conn->prepare("SELECT faculty.user_id FROM student_courses JOIN faculty ON faculty.course_id = student_courses.course_id WHERE student_courses.student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $faculty = $stmt->get_result();
    $user_ids = array_column($faculty->fetch_all(MYSQLI_ASSOC), 'user_id');

    if ($faculty->num_rows === 0 || !in_array($user_id, $user_ids)) {
        header("Location: 403.php");
        exit();
    }
}

$student = [];

// Fetch student data
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token']);

    // Sanitize inputs
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $student_number = sanitizeInput($_POST['student_number']);
    $class_id = sanitizeInput($_POST['class_id']);
    $department_id = sanitizeInput($_POST['department_id']);

    // Validation
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (!preg_match('/^[0-9]{8,15}$/', $phone)) $errors[] = "Phone must be 8-15 digits";
    if (!preg_match('/^[A-Za-z0-9]{8}$/', $student_number)) $errors[] = "Student number must be 8 alphanumeric characters";
    if ($department_id <= 0) $errors[] = "Invalid department";

    if (empty($errors)) {
        try {
            // Update student record
            $stmt = $conn->prepare("UPDATE students SET 
                name = ?, email = ?, phone = ?, student_number = ?, 
                class_id = ?, department_id = ? 
                WHERE student_id = ?");

            $stmt->bind_param("ssssiii",
                $name,
                $email,
                $phone,
                $student_number,
                $class_id,
                $department_id,
                $student_id
            );

            $stmt->execute();
        
            $_SESSION['success'] = "Student updated successfully";
            header("Location: read_student.php");
            exit();
        } catch (Exception $e) {
            error_log("Update error: " . $e->getMessage());
            $_SESSION['error'] = "Error updating student: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode(", ", $errors);
    }
}

$classes = $conn->query("SELECT * FROM classes");
$departments = $conn->query("SELECT * FROM department");

$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <select name="department_id" class="form-select" required>
                            <?php while($dept = $departments->fetch_assoc()): ?>
                                <option value="<?= $dept['department_id'] ?>"
                                    <?= ($dept['department_id'] == $student['department_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
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