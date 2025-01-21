<?php
session_start();
require 'db_connect.php';

// Ensure the user is logged in and has the correct role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['faculty', 'admin'])) {
    header("Location: login.php"); // Redirect to login page if the user is not logged in
    exit();
}

// Regenerate session ID periodically (every 5 minutes) for security purposes
if (!isset($_SESSION['regenerated_time']) || time() - $_SESSION['regenerated_time'] > 300) {
    session_regenerate_id(true);
    $_SESSION['regenerated_time'] = time();
}

$user_id = $_SESSION['user_id']; // Get the logged-in user ID
$user_role = $_SESSION['role']; // Get the user's role (admin or faculty)
$message = ""; // Message to display to the user
$message_type = ""; // Type of the message: 'success' or 'error'

// Determine the redirection URL based on the user's role
$redirect_url = ($user_role === 'admin') ? 'manage_student_courses_admin.php' : 'manage_student_courses_faculty.php';

try {
    // Fetch the list of all students
    $students_query = "SELECT student_id, name FROM students ORDER BY name";
    $students_result = $conn->query($students_query);
    $students = $students_result->fetch_all(MYSQLI_ASSOC); // Fetch all students as an associative array

    // Fetch the list of courses based on the user's role
    $courses_query = "SELECT course_id, course_name FROM courses";

    if ($user_role === 'faculty') {
        // Restrict faculty to view only their assigned courses
        $courses_query .= " WHERE course_id IN (
                            SELECT course_id FROM faculty WHERE user_id = ?
                          )";
    }

    $courses_query .= " ORDER BY course_name"; // Sort courses alphabetically
    $stmt = $conn->prepare($courses_query);

    if ($user_role === 'faculty') {
        $stmt->bind_param('i', $user_id); // Bind faculty user ID for filtering courses
    }

    $stmt->execute(); // Execute the query
    $courses_result = $stmt->get_result();
    $courses = $courses_result->fetch_all(MYSQLI_ASSOC); // Fetch all courses as an associative array

} catch (Exception $e) {
    // Log any errors and set an error message
    error_log("Error fetching data for assignment: " . $e->getMessage());
    $_SESSION['error_message'] = "Unable to load assignment data. Please try again later.";
    header("Location: dashboard.php"); // Redirect to the dashboard
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data for student ID, course ID, and status
    $student_id = $_POST['student_id'];
    $course_id = $_POST['course_id'] ?? ''; // Get single course ID
    $status = $_POST['status'];

    // Validate input fields
    if (empty($student_id) || empty($course_id) || empty($status)) {
        $message = "Please fill in all fields.";
        $message_type = "error";
    } else {
        try {
            // Check if the student is already assigned to the selected course
            $check_query = "SELECT 1 FROM student_courses WHERE student_id = ? AND course_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param('ii', $student_id, $course_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                // If assignment exists, set an error message
                $message = "The student is already assigned to the selected course.";
                $message_type = "error";
            } else {
                // Assign the course to the student
                $assign_query = "INSERT INTO student_courses (student_id, course_id, status, enrollment_date) VALUES (?, ?, ?, NOW())";
                $assign_stmt = $conn->prepare($assign_query);
                $assign_stmt->bind_param('iis', $student_id, $course_id, $status);
                $assign_stmt->execute();

                // Set a success message and redirect to the appropriate page
                $message = "Course successfully assigned to the student.";
                $message_type = "success";
                header("Location: $redirect_url");
                exit();
            }
        } catch (Exception $e) {
            // Log any errors and set an error message
            error_log("Error assigning courses: " . $e->getMessage());
            $message = "An error occurred while assigning the course. Please try again later.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Courses</title>
</head>
<body>
    <h1>Assign Courses to Student</h1>

    <!-- Form for assigning courses -->
    <form method="post" action="">
        <!-- Dropdown to select a student -->
        <label for="student_id">Select Student:</label>
        <select name="student_id" id="student_id" required>
            <option value="">-- Select a Student --</option>
            <?php foreach ($students as $student): ?>
                <option value="<?php echo $student['student_id']; ?>">
                    <?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <!-- Dropdown to select a course -->
        <label for="course_id">Select Course:</label>
        <select name="course_id" id="course_id" required>
            <option value="">-- Select a Course --</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?php echo $course['course_id']; ?>">
                    <?php echo htmlspecialchars($course['course_name'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <!-- Dropdown to select status -->
        <label for="status">Select Status:</label>
        <select name="status" id="status" required>
            <option value="">-- Select a Status --</option>
            <option value="start">Start</option>
            <option value="in-progress">In-Progress</option>
            <option value="ended">Ended</option>
        </select><br><br>

        <button type="submit">Assign Courses</button>
    </form>

    <!-- Display success or error message -->
    <?php if (!empty($message)): ?>
        <p style="color: <?php echo $message_type === 'success' ? 'green' : 'red'; ?>;">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <br>
    <!-- Link to go back to manage student courses -->
    <a href="<?php echo $redirect_url; ?>">Back to Manage Student Courses</a>
</body>
</html>
