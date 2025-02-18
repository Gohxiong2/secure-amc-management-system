<?php
require_once 'db_connect.php';
require_once 'security.php';

verifyAuthentication();
verifyAdminOrFacultyAccess();
enforceSessionTimeout(300);

// Checking for valid CSRF Tokens
if ($_SERVER['REQUEST_METHOD'] === 'POST') {   
    if (validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid CSRF token";
        header("Location: create_class.php");
        exit();
    }

    // Sanitize inputs
    $class_name = sanitizeInput($_POST['class_name']);
    $duration = sanitizeInput($_POST['duration']);
    $start_date = sanitizeInput($_POST['start_date']);
    $end_date = sanitizeInput($_POST['end_date']);         

    // Make sure that fields are not empty and do not have special characters
    if (empty($class_name) || empty($duration)) {
        $_SESSION['error'] = "All fields are required";
    } elseif (strtotime($start_date) > strtotime($end_date)){
        $_SESSION['error'] = "Start date cannot be later than end date!";
    } elseif ((!preg_match('/^[a-zA-Z0-9.-]+$/', $class_name)) || (!preg_match('/^[a-zA-Z0-9.-]+$/', $duration))) {
        $_SESSION['error'] = "No special characters allowed!";
    } 
    else {
        // Prepare and bind parameters
        $stmt = $conn->prepare("INSERT INTO classes (class_name, duration, start_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $class_name, $duration, $start_date, $end_date);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Class created successfully";
            header("Location: read_class.php");
            exit();
        } else {
            $_SESSION['error'] = "Error creating class: " . $stmt->error;
        }
        $stmt->close();
    }
}

// To show course name
$courses_query = "SELECT course_id, course_name, course_code FROM courses";
$courses_result = mysqli_query($conn, $courses_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Class</title>
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .container { max-width: 1200px; margin-top: 30px; }
        .header-container { display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mb-4">
            <div class="col-auto">
                <h2 class="text-primary text-center display-7 fw-bold">Create New Class</h2>
            </div>
        </div>
        <div class="card p-4">
            <div class="header-container mb-4">
                <a href="read_class.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Classes
                </a>
            </div>

            <?php displayMessages(); ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="mb-3">
                    <label for="class_name" class="form-label">Class Name:</label>
                    <input type="text" class="form-control" id="class_name" name="class_name" required>
                </div>

                <!-- To show course names -->
                <div class="mb-3">
                    <label for="course_id" class="form-label">Course:</label>
                    <select class="form-control" id="course_id" name="course_id" required>
                        <option value="">Select Course</option>
                        <?php while ($course = mysqli_fetch_assoc($courses_result)): ?>
                            <option value="<?php echo $course['course_id']; ?>">
                                <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="duration" class="form-label">Semester:</label>
                    <input type="text" class="form-control" id="duration" name="duration" required>
                </div>

                <div class="mb-3">
                    <label for="start_date" class="form-label">Start Date:</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                </div>

                <div class="mb-3">
                    <label for="end_date" class="form-label">End Date:</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Create Class</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>