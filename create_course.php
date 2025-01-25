<?php
session_start();
require 'db_connect.php';
require 'csrf.php';

function enforce_session_timeout($timeout = 300) {
    if (isset($_SESSION['last_activity'])) {
        $elapsed_time = time() - $_SESSION['last_activity']; 
        if ($elapsed_time > $timeout) {
            session_unset();
            session_destroy();
            header("Location: login.php?error=session_expired");
            exit();
        }
    }
    $_SESSION['last_activity'] = time();
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        session_regenerate_id(true);
        header("Location: login.php");
        exit();
    }
}

function check_role_access($allowed_roles) {
    if (!in_array($_SESSION['role'], $allowed_roles)) {

        header("Location: 403.php");
        exit(); 
    }
}


if (!isset($conn) || $conn === null) {
    $error_message = "Database connection error. Please try again later.";
    $conn = null; 
} 

check_login(); 
enforce_session_timeout(); 
check_role_access(['admin', 'faculty']); 

function handle_course_creation($conn) {
    if ($conn === null) {
        global $error_message;
        $error_message = "Database connection error. Please try again later.";
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf_token($_POST['csrf_token']); // Verify CSRF token
        // Sanitize and validate user inputs
        $course_name = htmlspecialchars(trim($_POST['course_name']), ENT_QUOTES, 'UTF-8');
        $course_code = htmlspecialchars(trim($_POST['course_code']), ENT_QUOTES, 'UTF-8');
        $start_date = $_POST['start_date'];
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

        // Initialize error and success messages
        global $error_message, $success_message;

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
        } elseif (!preg_match('/\d/', $course_code)) {
            $error_message = "The course code must include at least one digit.";
        } elseif (strlen($course_code) > 10) {
            $error_message = "The course code must not exceed 10 characters.";
        } elseif (empty($start_date)) {
            $error_message = "Start date is required.";
        } elseif (!is_null($end_date) && strtotime($start_date) > strtotime($end_date)) {
            $error_message = "The start date must be earlier than the end date.";
        } else {
            // Proceed with database operations
            try {
                // Check for duplicate course name
                $name_query = "SELECT 1 FROM courses WHERE course_name = ?";
                $name_stmt = $conn->prepare($name_query);
                $name_stmt->bind_param('s', $course_name);
                $name_stmt->execute();
                $name_result = $name_stmt->get_result();

                if ($name_result->num_rows > 0) {
                    $error_message = "The course name you entered already exists. Please choose a unique name.";
                } else {
                    // Check for duplicate course code
                    $code_query = "SELECT 1 FROM courses WHERE course_code = ?";
                    $code_stmt = $conn->prepare($code_query);
                    $code_stmt->bind_param('s', $course_code);
                    $code_stmt->execute();
                    $code_result = $code_stmt->get_result();

                    if ($code_result->num_rows > 0) {
                        $error_message = "The course code you entered already exists. Please choose a unique code.";
                    } else {
                        // Insert course into the `courses` table
                        $insert_query = "INSERT INTO courses (course_name, course_code, start_date, end_date) VALUES (?, ?, ?, ?)";
                        $insert_stmt = $conn->prepare($insert_query);
                        $insert_stmt->bind_param('ssss', $course_name, $course_code, $start_date, $end_date);

                        if ($insert_stmt->execute()) {
                            // Get the newly created course ID
                            $course_id = $conn->insert_id;

                            // Assign the course to the faculty member if applicable
                            if ($_SESSION['role'] === 'faculty') {
                                $faculty_query = "INSERT INTO faculty (user_id, course_id) VALUES (?, ?)";
                                $faculty_stmt = $conn->prepare($faculty_query);
                                $faculty_stmt->bind_param('ii', $_SESSION['user_id'], $course_id);
                                $faculty_stmt->execute();
                            }

                            $success_message = "The course has been created successfully.";

                            // Clear the form fields
                            $_POST['course_name'] = '';
                            $_POST['course_code'] = '';
                            $_POST['start_date'] = '';
                            $_POST['end_date'] = '';
                        } else {
                            $error_message = "An unexpected error occurred while creating the course. Please try again later.";
                        }
                    }
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
                $error_message = "An unexpected system error occurred. Please try again later.";
            }
        }
    }

}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create a New Course</title>
    <style>
        /* Adjust placeholder text size to fit */
        input::placeholder {
            font-size: 0.8em;
        }
    </style>
    <script>
        // Handle date field restrictions dynamically
        document.addEventListener("DOMContentLoaded", function () {
            const startDateInput = document.getElementById("start_date");
            const endDateInput = document.getElementById("end_date");

            startDateInput.addEventListener("change", function () {
                endDateInput.min = this.value;
                endDateInput.disabled = false;
            });

            endDateInput.addEventListener("change", function () {
                startDateInput.max = this.value;
            });
        });
    </script>
</head>
<body>
    <h1>Create a New Course</h1>
    <form method="post" action="">
        <?php $csrf_token = generate_csrf_token(); ?> <!-- Generate CSRF Token and assign it to hidden from field -->
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <label for="course_name">Course Name: <span style="color: red;">*</span></label>
        <input type="text" id="course_name" name="course_name" maxlength="50" 
               placeholder="E.g., Cybersecurity Fundamentals" 
               value="<?php echo htmlspecialchars($_POST['course_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
               required><br><br>

        <label for="course_code">Course Code: <span style="color: red;">*</span></label>
        <input type="text" id="course_code" name="course_code" maxlength="10" 
               placeholder="E.g., CDF10123" 
               value="<?php echo htmlspecialchars($_POST['course_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
               required><br><br>

        <label for="start_date">Start Date: <span style="color: red;">*</span></label>
        <input type="date" id="start_date" name="start_date" 
        value="<?php echo htmlspecialchars($_POST['start_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
        required><br><br>

        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" 
        value="<?php echo htmlspecialchars($_POST['end_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>

        <input type="submit" value="Create Course">
    </form>

    <?php if (isset($success_message)): ?>
        <p style="color: green;"> <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?> </p>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <p style="color: red;"> <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?> </p>
    <?php endif; ?>


    <br>
    <a href="read_course.php">Back to Manage Courses</a>
</body>
</html>
