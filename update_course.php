<?php
require_once 'db_connect.php';
require 'security_course.php';
require 'error_handler_course.php';
require 'course_form_common.php';

//Security & Authentication Checks
verifyAuthentication(); // Ensures the user is logged in and the session is started if it has not been started
enforceSessionTimeout(600);// Log out users after 10 minutes of inactivity
verifyAdminOrFacultyAccess(); // Allow only admins and faculty to access this page


// Handle GET requests to fetch course details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['course_id'])) {
    $course_id = (int)$_GET['course_id']; // Convert course_id to integer for security

    // Ensure the faculty user has permission to update this course
    if (!isAdmin()) {
        $faculty_check_query = "SELECT 1 FROM faculty WHERE user_id = ? AND course_id = ?";
        $faculty_check_stmt = $conn->prepare($faculty_check_query);
        $faculty_check_stmt->bind_param('ii', $_SESSION['user_id'], $course_id);
        $faculty_check_stmt->execute();

        // If the user is not assigned to this course, deny update access
        if ($faculty_check_stmt->get_result()->num_rows === 0) {
            $_SESSION['error_message'] = "You do not have permission to update this course.";
            header("Location: read_course.php");
            exit();
        }
    }


    // Fetch course details from database
    $query = "SELECT course_id, course_name, course_code, start_date, end_date FROM courses WHERE course_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $course_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();

    // If course doesn't exist, redirect to error page
    if (!$course) {
        redirectCourseErrorPage();
    }

    // Store course details in session and redirect for further processing
    $_SESSION['selected_course'] = $course;
    header("Location: update_course.php");
    exit();

}

// If session data is missing, redirect to error page
if (!isset($_SESSION['selected_course'])) {
    redirectCourseErrorPage();
}

// Retrieve course details from session
$course = $_SESSION['selected_course'];
$course_id = $course['course_id'];


// Handle form submission (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token']); // Validate CSRF token for security


    // Retrieve and sanitize user input
    $course_name = sanitizeInput($_POST['course_name']);
    $course_code = sanitizeInput($_POST['course_code']);
    $start_date = $_POST['start_date'];
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

    // Check if there are any actual changes made
    $current_data = $_SESSION['selected_course'];
    if (
        $current_data['course_name'] === $course_name &&
        $current_data['course_code'] === $course_code &&
        $current_data['start_date'] === $start_date &&
        $current_data['end_date'] === $end_date
    ) {
        $error_message = "No changes were detected. Please update at least one field.";
    } else {
        // Validate input and check for duplicates
        $error_message = validateAndCheckDuplicates($conn, $course_name, $course_code, $start_date, $end_date, $course_id ?? null);

    
        if (!$error_message) {
            // Prepare an SQL statement to update the course details
            $update_query = "UPDATE courses SET course_name = ?, course_code = ?, start_date = ?, end_date = ? WHERE course_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('ssssi', $course_name, $course_code, $start_date, $end_date, $course_id);
    
            if ($update_stmt->execute()) { // Execute the query
                $success_message = "The course has been updated successfully."; // Success message
                
                // Update session with latest course details
                $_SESSION['selected_course'] = [
                    'course_id' => $course_id,
                    'course_name' => $course_name,
                    'course_code' => $course_code,
                    'start_date' => $start_date,
                    'end_date' => $end_date
                ];
            } else {
                redirectCourseErrorPage(); // Redirect to error page if update fails
            }
        }
    }

    
    // Update session data with latest values
    $course = [
        'course_id' => $course_id,
        'course_name' => $course_name,
        'course_code' => $course_code,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
}

// Generate a CSRF token for form security
$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Course</title>

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
    </style>

</head>
<body class="bg-light">
    <div class="container">
        <div class="card p-4">
            <h2 class="mb-4 text-primary">Update Course</h2>

            <!-- Course update form -->
            <form method="post" action="">
                <!-- CSRF protection token -->
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <!-- Course Name Field -->
                <div class="mb-3">
                    <label for="course_name" class="form-label">Course Name <span style="color: red;">*</span></label>
                    <input type="text" id="course_name" name="course_name" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars_decode(htmlspecialchars($course['course_name'] ?? '', ENT_QUOTES, 'UTF-8')); ?>" 
                           maxlength="50" required>
                </div>

                <!-- Course Code Field -->
                <div class="mb-3">
                    <label for="course_code" class="form-label">Course Code <span style="color: red;">*</span></label>
                    <input type="text" id="course_code" name="course_code" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars_decode(htmlspecialchars($course['course_code'] ?? '', ENT_QUOTES, 'UTF-8')); ?>" 
                           maxlength="10" required>
                </div>

                <!-- Start Date Field -->
                <div class="mb-3">
                    <label for="start_date" class="form-label">Start Date <span style="color: red;">*</span></label>
                    <input type="date" id="start_date" name="start_date" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($course['start_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                           required>
                </div>

                <!-- End Date Field (Optional) -->
                <div class="mb-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($course['end_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                  <!-- Display success message -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success mt-3"><?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <!-- Display error message -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger mt-3"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <!-- Submit & Cancel Buttons -->
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Update Course</button>
                    <a href="read_course.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>