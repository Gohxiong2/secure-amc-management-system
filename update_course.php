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


if (!isset($conn) || $conn === null) {
    $error_message = "Database connection error. Please try again later.";
    $conn = null; 
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
        $_SESSION['error_message'] = "You do not have permission to access this page.";
        header("Location: 403.php");
        exit(); 
    }
}


check_login(); // 
enforce_session_timeout(); 
check_role_access(['admin', 'faculty']); 


function check_permission($conn, $user_id, $role, $course_id) {
    if ($role === 'admin') {
        return true;
    }

    if ($role === 'faculty') {
        $query = "SELECT 1 FROM faculty WHERE user_id = ? AND course_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $user_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    return false;
}


function fetch_course_details($conn, $course_id) {
    try {
        $query = "SELECT course_id, course_name, course_code, start_date, end_date FROM courses WHERE course_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error fetching course details: " . $e->getMessage());
        return null; 
    }
}


if ($conn === null) {
    $error_message = $error_message ?? "Database connection error. Please try again later.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (isset($_GET['course_id'])) {
        $course_id = (int)$_GET['course_id']; // Sanitize input

        $course = fetch_course_details($conn, $course_id);
        if (!$course) {
            die("Sorry, the requested course could not be found.");
        }

        // Check if the user has permission to edit this course
        if (!check_permission($conn, $_SESSION['user_id'], $_SESSION['role'], $course_id)) {
            die("You do not have the necessary permissions to edit this course.");
        }

        // Store course details in session
        $_SESSION['selected_course'] = $course;
        header("Location: update_course.php");
        exit();
    } elseif (isset($_SESSION['selected_course'])) {
        $course = $_SESSION['selected_course'];
    } else {
        die("No course is currently selected for editing.");
    }
} 
if ($conn === null) {
    $error_message = $error_message ?? "Database connection error. Please try again later.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']); // Verify CSRF token
    
    if (!isset($_SESSION['selected_course'])) {
        die("Unable to find course details in session. Please try again.");
    }

    $course = $_SESSION['selected_course'];
    $course_id = $course['course_id'];

    // Sanitize and validate user inputs
    $course_name = htmlspecialchars(trim($_POST['course_name']), ENT_QUOTES, 'UTF-8');
    $course_code = htmlspecialchars(trim($_POST['course_code']), ENT_QUOTES, 'UTF-8');
    $start_date = $_POST['start_date'];
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

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
    } elseif (!preg_match('/\d/', $course_code)) {
    $error_message = "The course code must include at least one digit.";
    } elseif (empty($start_date)) {
        $error_message = "Start date is required.";
    } elseif (!is_null($end_date) && strtotime($start_date) > strtotime($end_date)) {
        $error_message = "The start date must be earlier than the end date.";
    } else {
        if (!check_permission($conn, $_SESSION['user_id'], $_SESSION['role'], $course_id)) {
            die("You do not have the necessary permissions to edit this course.");
        }

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

                    try {
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
                    } catch (Exception $e) {
                        error_log("Exception during course update: " . $e->getMessage());
                        $error_message = "A system error occurred while updating the course. Please contact support.";
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


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Course</title>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const startDateInput = document.getElementById("start_date");
            const endDateInput = document.getElementById("end_date");

            startDateInput.addEventListener("change", function () {
                endDateInput.min = this.value;
            });

            endDateInput.addEventListener("change", function () {
                startDateInput.max = this.value;
            });
        });
    </script>
</head>
<body>
    <h1>Update Course</h1>
    <form method="post" action="">
        <?php $csrf_token = generate_csrf_token(); ?> <!-- Generate CSRF Token and assign it to hidden from field -->
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <label for="course_name">Course Name: <span style="color: red;">*</span></label>
        <input type="text" id="course_name" name="course_name" value="<?php echo htmlspecialchars_decode(htmlspecialchars($course['course_name'] ?? '', ENT_QUOTES, 'UTF-8')); ?>" maxlength="50" required>
        <br><br>

        <label for="course_code">Course Code: <span style="color: red;">*</span></label>
        <input type="text" id="course_code" name="course_code" value="<?php echo htmlspecialchars_decode(htmlspecialchars($course['course_code'] ?? '', ENT_QUOTES, 'UTF-8')); ?>" maxlength="10" required>
        <br><br>

        <label for="start_date">Start Date: <span style="color: red;">*</span></label>
        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($course['start_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required><br><br>

        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($course['end_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><br><br>

        <button type="submit">Update Course</button>
    </form>

    <?php if (!empty($error_message)): ?>
        <p style="color: red;"> <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?> </p>
    <?php endif; ?>

    <?php if (isset($success_message)): ?>
        <p style="color: green;"> <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?> </p>
    <?php endif; ?>

    <br>
    <a href="read_course.php">Back to Manage Courses</a>
</body>
</html>
