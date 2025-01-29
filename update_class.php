<?php
require_once 'db_connect.php';
require_once 'security.php';

verifyAuthentication();
verifyAdminOrFacultyAccess();
enforceSessionTimeout();

$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid CSRF token";
        header("Location: update_class.php?id=" . $class_id);
        exit();
    }

    $class_name = sanitizeInput($_POST['class_name']);
    $course_id = (int)$_POST['course_id'];
    $semester = sanitizeInput($_POST['semester']);
    $start_date = sanitizeInput($_POST['start_date']);
    $end_date = sanitizeInput($_POST['end_date']);
    $class_id = (int)$_POST['class_id'];

    if (empty($class_name) || empty($course_id) || empty($semester)) {
        $_SESSION['error'] = "All fields are required";
    } else {
        $stmt = $conn->prepare("UPDATE classes SET class_name=?, course_id=?, semester=?, start_date=?, end_date=? WHERE class_id=?");
        $stmt->bind_param("sisssi", $class_name, $course_id, $semester, $start_date, $end_date, $class_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Class updated successfully";
            header("Location: read_class.php");
            exit();
        } else {
            $_SESSION['error'] = "Error updating class: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get class data
$stmt = $conn->prepare("SELECT * FROM classes WHERE class_id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();
$class = $result->fetch_assoc();

if (!$class) {
    $_SESSION['error'] = "Class not found";
    header("Location: view_classes.php");
    exit();
}

// Get courses for dropdown
$courses_query = "SELECT course_id, course_name, course_code FROM courses";
$courses_result = mysqli_query($conn, $courses_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Class</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h2>Update Class</h2>
            </div>
            <div class="card-body">
                <?php displayMessages(); ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">

                    <div class="mb-3">
                        <label for="class_name" class="form-label">Class Name:</label>
                        <input type="text" class="form-control" id="class_name" name="class_name" 
                               value="<?php echo htmlspecialchars($class['class_name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="course_id" class="form-label">Course:</label>
                        <select class="form-control" id="course_id" name="course_id" required>
                            <?php while ($course = mysqli_fetch_assoc($courses_result)): ?>
                                <option value="<?php echo $course['course_id']; ?>" 
                                        <?php echo ($course['course_id'] == $class['course_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester:</label>
                        <input type="text" class="form-control" id="semester" name="semester" 
                               value="<?php echo htmlspecialchars($class['semester']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date:</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo htmlspecialchars($class['start_date']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date:</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?php echo htmlspecialchars($class['end_date']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Update Class</button>
                        <a href="read_class.php" class="btn btn-secondary">Back to Classes</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>