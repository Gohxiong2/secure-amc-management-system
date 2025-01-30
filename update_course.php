<?php
require_once 'db_connect.php';
require_once 'security.php';

//Security & Authentication Checks
verifyAuthentication();
enforceSessionTimeout(300);

//Database Connection Checks
validateDatabaseConnection($conn);

// Verify user role (admin and faculty only)
verifyAdminOrFacultyAccess();

// Fetches the details of the course specified by the course_id.
// Returns an associative array of course details or null if the course is not found.
function fetchCourseDetails($conn, $course_id) {
    try {
        $query = "SELECT course_id, course_name, course_code, start_date, end_date FROM courses WHERE course_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error fetching course details: " . $e->getMessage());
        return null; // Return null on error
    }
}


// Handle GET requests to fetch course details
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['course_id'])) {
        $course_id = (int)$_GET['course_id']; // Sanitize input

        // Fetch course details
        $course = fetchCourseDetails($conn, $course_id);
        if (!$course) {
            die("Sorry, the requested course could not be found.");
        }

        // Check update permissions
        if (!isAdmin()) {
            // Ensure faculty can only update their own courses
            $faculty_check_query = "SELECT 1 FROM faculty WHERE user_id = ? AND course_id = ?";
            $faculty_check_stmt = $conn->prepare($faculty_check_query);
            $faculty_check_stmt->bind_param('ii', $_SESSION['user_id'], $course_id);
            $faculty_check_stmt->execute();
            $faculty_check_result = $faculty_check_stmt->get_result();

            if ($faculty_check_result->num_rows === 0) {
                $_SESSION['error_message'] = "You do not have permission to update this course.";
                header("Location: read_course.php");
                exit();
            }
        }

        // Store course details in session
        $_SESSION['selected_course'] = $course;
        header("Location: update_course.php");
        exit();
    } elseif (isset($_SESSION['selected_course'])) {
        // Retrieve course from session if already selected
        $course = $_SESSION['selected_course'];
    } else {
        die("No course is currently selected for editing.");
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("Invalid CSRF token.");
    }

    // Retrieve course details from session
    if (!isset($_SESSION['selected_course'])) {
        die("Unable to find course details in session. Please try again.");
    }

    $course_id = $_SESSION['selected_course']['course_id'];

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


        // Ensure the course name is unique
        $name_query = "SELECT 1 FROM courses WHERE course_name = ? AND course_id != ?";
        $name_stmt = $conn->prepare($name_query);
        $name_stmt->bind_param('si', $course_name, $course_id);
        $name_stmt->execute();
        $name_result = $name_stmt->get_result();

        if ($name_result->num_rows > 0) {
            $error_message = "The course name you entered already exists. Please choose a unique name.";
        } else {
            // Ensure the course code is unique
            $code_query = "SELECT 1 FROM courses WHERE course_code = ? AND course_id != ?";
            $code_stmt = $conn->prepare($code_query);
            $code_stmt->bind_param('si', $course_code, $course_id);
            $code_stmt->execute();
            $code_result = $code_stmt->get_result();

            if ($code_result->num_rows > 0) {
                $error_message = "The course code you entered already exists. Please choose a unique code.";
            } else {
                // Check if updates were made
                $current_data = $_SESSION['selected_course'];
                if (
                    $current_data['course_name'] === $course_name &&
                    $current_data['course_code'] === $course_code &&
                    $current_data['start_date'] === $start_date &&
                    $current_data['end_date'] === $end_date
                ) {
                    $error_message = "No changes were detected. Please update at least one field.";
                } else {
                    // Update the course details
                    $update_query = "UPDATE courses SET course_name = ?, course_code = ?, start_date = ?, end_date = ? WHERE course_id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param('ssssi', $course_name, $course_code, $start_date, $end_date, $course_id);


                    if ($update_stmt->execute()) {

                        $success_message = "The course has been updated successfully.";
                        $_SESSION['selected_course'] = [
                            'course_id' => $course_id,
                            'course_name' => $course_name,
                            'course_code' => $course_code,
                            'start_date' => $start_date,
                            'end_date' => $end_date
                        ];
                    } else {
                        error_log("Error updating course: " . $update_stmt->error);
                        $error_message = "An unexpected error occurred while updating the course. Please try again later.";
                    }

                    
                }
            }
        }
    }


    
    // Update session with the latest course data
    $course = [
        'course_id' => $course_id,
        'course_name' => $course_name,
        'course_code' => $course_code,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
}

// Generate CSRF token
$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Course</title>
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
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

            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div class="mb-3">
                    <label for="course_name" class="form-label">Course Name <span style="color: red;">*</span></label>
                    <input type="text" id="course_name" name="course_name" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars_decode(htmlspecialchars($course['course_name'] ?? '', ENT_QUOTES, 'UTF-8')); ?>" 
                           maxlength="50" required>
                </div>

                <div class="mb-3">
                    <label for="course_code" class="form-label">Course Code <span style="color: red;">*</span></label>
                    <input type="text" id="course_code" name="course_code" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars_decode(htmlspecialchars($course['course_code'] ?? '', ENT_QUOTES, 'UTF-8')); ?>" 
                           maxlength="10" required>
                </div>

                <div class="mb-3">
                    <label for="start_date" class="form-label">Start Date <span style="color: red;">*</span></label>
                    <input type="date" id="start_date" name="start_date" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($course['start_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                           required>
                </div>

                <div class="mb-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($course['end_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <!-- Success or Error Messages -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success mt-3"><?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger mt-3"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Update Course</button>
                    <a href="read_course.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>


