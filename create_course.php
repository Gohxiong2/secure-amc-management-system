<?php
require_once 'db_connect.php';
require 'security_course.php';
require 'error_handler_course.php';
require 'course_form_common.php';

//Database Connection Checks    
verifyAuthentication(); // Ensures the user is logged in and the session is started if it has not been started
verifyAdminOrFacultyAccess(); // Allow only admins and faculty to access this page
enforceSessionTimeout(600);// Log out users after 10 minutes of inactivity


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token']); // Validate CSRF token for security

    // Retrieve and sanitize user input
    $course_name = sanitizeInput($_POST['course_name']);
    $course_code = sanitizeInput($_POST['course_code']);
    $start_date = $_POST['start_date'];
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;


    // Validate input and check for duplicates in a single step
    $error_message = validateAndCheckDuplicates($conn, $course_name, $course_code, $start_date, $end_date, $course_id ?? null);


    // If there are no validation errors, proceed with course creation
    if (!$error_message) {
        try {
            // Prepare an SQL statement to insert the course
            $stmt = $conn->prepare("INSERT INTO courses (course_name, course_code, start_date, end_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $course_name, $course_code, $start_date, $end_date);
            
            // Execute the query
            if ($stmt->execute()) {
                $course_id = $stmt->insert_id; // Get the newly inserted course ID

                // Assign course to faculty table if faculty create a course
                if (isFaculty()) {
                    $stmt = $conn->prepare("INSERT INTO faculty (user_id, course_id) VALUES (?, ?)");
                    $stmt->bind_param('ii', $_SESSION['user_id'], $course_id);
                    $stmt->execute();
                }

                $success_message = "The course has been created successfully.";
                $_POST = []; // Clear form inputs
            } 
        } catch (Exception $e) {
            redirectCourseErrorPage(); // Redirect to error page if an exception occurs
        }
    }
}

// Generate a CSRF token for form security
$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Course</title>
    <!-- Auto-refresh the page after 3 seconds if a success message is displayed -->
    <?php if (!empty($success_message)): ?>
        <meta http-equiv="refresh" content="3">
    <?php endif; ?>


    <!-- Local Bootstrap CSS -->
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Online Bootstrap CSS load this on error -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->

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

            <!-- Course creation form -->
            <form method="POST">

                <!-- CSRF protection token -->
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <!-- Course Name Field -->
                <div class="mb-3">
                    <label for="course_name" class="form-label">Course Name <span class="required">*</span></label>
                    <input type="text" id="course_name" name="course_name" maxlength="50" class="form-control"
                           placeholder="E.g., Cybersecurity Fundamentals"
                           value="<?= htmlspecialchars($_POST['course_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

               <!-- Course Code Field -->
                <div class="mb-3">
                    <label for="course_code" class="form-label">Course Code <span class="required">*</span></label>
                    <input type="text" id="course_code" name="course_code" maxlength="10" class="form-control"
                           placeholder="E.g., CDF10123"
                           value="<?= htmlspecialchars($_POST['course_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <!-- Start Date Field -->
                <div class="mb-3">
                    <label for="start_date" class="form-label">Start Date <span class="required">*</span></label>
                    <input type="date" id="start_date" name="start_date" class="form-control"
                           value="<?= htmlspecialchars($_POST['start_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <!-- End Date Field (Optional) -->
                <div class="mb-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control"
                           value="<?= htmlspecialchars($_POST['end_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <?php  ?>

                <!-- Display success message -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>

                <!-- Display error message -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <!-- Submit & Cancel Buttons -->
                <div class="d-flex justify-content-start mt-3">
                    <button type="submit" class="btn btn-primary px-4">Create Course</button>
                    <a href="read_course.php" class="btn btn-secondary ms-2 px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>