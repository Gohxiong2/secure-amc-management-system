<?php
require_once 'db_connect.php';
require_once 'security.php';

//Database Connection Checks
verifyAuthentication();


// Verify user role (admin and faculty only)
verifyAdminOrFacultyAccess();
enforceSessionTimeout(300);

// Initialize variables for messages
$error_message = "";
$success_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (validateCsrfToken($_POST['csrf_token'])) {
        die("Invalid CSRF token.");
    }

    // Sanitize inputs
    $course_name = sanitizeInput($_POST['course_name']);
    $course_code = sanitizeInput($_POST['course_code']);
    $start_date = $_POST['start_date'];
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $current_date = date('Y-m-d'); // Current date
    $six_months_later = (new DateTime($current_date))->modify('+6 months')->format('Y-m-d'); // Six months after today
    $min_start_date = '2015-01-01'; // Minimum start date
    $max_end_date = '2035-12-31'; // Maximum end date

    // Validate inputs and display the first error encountered
    if (empty($course_name)) {
        $error_message = "Please enter a course name.";
    } elseif (!preg_match('/^[a-zA-Z0-9&\-_ ]+$/', $course_name)) {
        $error_message = "The course name can only include letters, numbers, spaces, and the symbols (&, -, _).";
    } elseif (strlen($course_name) > 50) {
        $error_message = "The course name must not exceed 50 characters.";
    } elseif (empty($course_code)) {
        $error_message = "Please provide a course code.";
    } elseif (!preg_match('/^[a-zA-Z0-9\-_]+$/', $course_code)) {
        $error_message = "The course code can only include letters, numbers, and the symbols (-, _).";
    } elseif (strlen($course_code) > 10) {
        $error_message = "The course code must not exceed 10 characters.";
    } elseif (!preg_match('/[a-zA-Z]/', $course_code) || !preg_match('/\d/', $course_code)) {
        $error_message = "The course code must include at least one letter and one digit.";
    } elseif (empty($start_date)) {
        $error_message = "Start date is required.";
    } elseif ($start_date < $min_start_date) {
        $error_message = "The start date must not be before 2015.";
    } elseif ($start_date > $six_months_later) {
        $error_message = "The start date cannot exceed six months from the current date.";
    } elseif (!is_null($end_date) && $end_date > $max_end_date) {
        $error_message = "The end date must not be beyond 2035.";
    } elseif (!is_null($end_date) && strtotime($end_date) < strtotime($start_date)) {
        $error_message = "The End date must be after start date.";
    } elseif (!is_null($end_date) && (strtotime($end_date) - strtotime($start_date)) < 365 * 24 * 60 * 60) {
        $error_message = "The difference between the start date and the end date must be at least one year.";
    } else {
        try {
            // Check for duplicate course name
            $stmt = $conn->prepare("SELECT 1 FROM courses WHERE course_name = ?");
            $stmt->bind_param('s', $course_name);
            $stmt->execute();
            $name_result = $stmt->get_result();

            if ($name_result->num_rows > 0) {
                $error_message = "The course name you entered already exists. Please choose a unique name.";
            } else {
                // Check for duplicate course code
                $stmt = $conn->prepare("SELECT 1 FROM courses WHERE course_code = ?");
                $stmt->bind_param('s', $course_code);
                $stmt->execute();
                $code_result = $stmt->get_result();

                if ($code_result->num_rows > 0) {
                    $error_message = "The course code you entered already exists. Please choose a unique code.";
                } else {
                    // Insert course into database
                    $stmt = $conn->prepare("INSERT INTO courses (course_name, course_code, start_date, end_date) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param('ssss', $course_name, $course_code, $start_date, $end_date);

                    if ($stmt->execute()) {
                        $course_id = $stmt->insert_id;

                        // Assign course to faculty if applicable
                        if (isFaculty()) {
                            $stmt = $conn->prepare("INSERT INTO faculty (user_id, course_id) VALUES (?, ?)");
                            $stmt->bind_param('ii', $_SESSION['user_id'], $course_id);
                            $stmt->execute();
                        }

                        $success_message = "The course has been created successfully.";
                        $_POST = []; // Clear form inputs
                    } else {
                        $error_message = "An unexpected error occurred while creating the course. Please try again later.";
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error creating course: " . $e->getMessage());
            $error_message = "A system error occurred. Please try again later.";
        }
    }
}

// Generate CSRF token
$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Course</title>
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; margin-top: 50px; }
        .card { border-radius: 15px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .btn-primary { background-color: #4da8da; border-color: #4da8da; }
        .btn-primary:hover { background-color: #357abd; border-color: #357abd; }
        .form-label { font-weight: bold; }
        .required { color: red; }
    </style>

</head>
<body class="bg-light">
    <div class="container">
        <div class="card p-4">
            <h2 class="mb-4 text-primary">Create New Course</h2>

            <!-- Form -->
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div class="mb-3">
                    <label for="course_name" class="form-label">Course Name <span class="required">*</span></label>
                    <input type="text" id="course_name" name="course_name" maxlength="50" class="form-control"
                           placeholder="E.g., Cybersecurity Fundamentals"
                           value="<?= htmlspecialchars($_POST['course_name'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="course_code" class="form-label">Course Code <span class="required">*</span></label>
                    <input type="text" id="course_code" name="course_code" maxlength="10" class="form-control"
                           placeholder="E.g., CDF10123"
                           value="<?= htmlspecialchars($_POST['course_code'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="start_date" class="form-label">Start Date <span class="required">*</span></label>
                    <input type="date" id="start_date" name="start_date" class="form-control"
                           value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control"
                           value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>">
                </div>
                <?php displayMessages(); ?>
                <!-- Display success message -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>

                <!-- Display error message -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <div class="d-flex justify-content-start mt-3">
                    <button type="submit" class="btn btn-primary px-4">Create Course</button>
                    <a href="read_course.php" class="btn btn-secondary ms-2 px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>


