<?php
session_start();
require 'db_connect.php';

// Ensure the user is logged in and has the correct role (admin or faculty)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'faculty'])) {
    header("Location: login.php");
    exit();
}

// Regenerate session ID periodically (every 5 minutes) to enhance security
if (!isset($_SESSION['regenerated_time']) || time() - $_SESSION['regenerated_time'] > 300) {
    session_regenerate_id(true);
    $_SESSION['regenerated_time'] = time();
}

// Variables for success or error messages
$message = "";
$message_type = ""; // 'success' or 'error'

// Retrieve the student_id and course_name from session variables
$student_id = $_SESSION['student_id'] ?? null;
$course_name = $_SESSION['course_name'] ?? null;

// Redirect if student_id or course_name is missing
if (empty($student_id) || empty($course_name)) {
    $_SESSION['error_message'] = "Invalid request. Missing student or course information.";
    header("Location: manage_student_courses.php");
    exit();
}

// Determine the redirection URL.
$redirect_url = 'manage_student_courses.php';


if (empty($student_id) || empty($course_name)) {
    $_SESSION['error_message'] = "Invalid request. Missing student or course information.";
    header("Location: $redirect_url");
    exit();
}

try {
    // Fetch the current assignment details (status, course_id, student_name)
    $query = "SELECT sc.status, c.course_id, s.name AS student_name 
              FROM student_courses sc
              INNER JOIN courses c ON sc.course_id = c.course_id
              INNER JOIN students s ON sc.student_id = s.student_id
              WHERE sc.student_id = ? AND c.course_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('is', $student_id, $course_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $assignment = $result->fetch_assoc();

    // Redirect if the assignment is not found
    if (!$assignment) {
        $_SESSION['error_message'] = "Assignment not found.";
        header("Location: $redirect_url");
        exit();
    }

    // Extract current assignment details
    $current_status = $assignment['status'];
    $current_course_id = $assignment['course_id'];
    $student_name = $assignment['student_name'];

    // Fetch courses not assigned to the student
    $available_courses_query = "SELECT c.course_id, c.course_name 
                                FROM courses c 
                                WHERE c.course_id NOT IN (
                                    SELECT sc.course_id 
                                    FROM student_courses sc 
                                    WHERE sc.student_id = ?
                                )";

    // Restrict faculty to only their managed courses
    if ($_SESSION['role'] === 'faculty') {
        $available_courses_query .= " AND c.course_id IN (
                                        SELECT course_id 
                                        FROM faculty 
                                        WHERE user_id = ?
                                    )";
    }

    // Append sorting order to the query
    $available_courses_query .= " ORDER BY c.course_name";

    // Prepare and bind parameters for the query
    $course_stmt = $conn->prepare($available_courses_query);
    if ($_SESSION['role'] === 'faculty') {
        $course_stmt->bind_param('ii', $student_id, $_SESSION['user_id']);
    } else {
        $course_stmt->bind_param('i', $student_id);
    }

    // Execute the query and fetch available courses
    $course_stmt->execute();
    $courses_result = $course_stmt->get_result();
    $available_courses = $courses_result->fetch_all(MYSQLI_ASSOC);

    // Filter out the current status from the status options
    $statuses = ['start', 'in-progress', 'ended'];
    $statuses = array_filter($statuses, fn($status) => $status !== $current_status);

    // Handle form submission for updating the assignment
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_course_id = $_POST['course_id'] ?? null;
        $new_status = $_POST['status'] ?? null;

        // Validate form inputs
        if (empty($new_course_id) || empty($new_status)) {
            $message = "Please fill in all fields.";
            $message_type = "error";
        } else {
            try {
                // Update the assignment in the database
                $update_query = "UPDATE student_courses 
                                 SET course_id = ?, status = ? 
                                 WHERE student_id = ? AND course_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('isii', $new_course_id, $new_status, $student_id, $current_course_id);

                // If update is successful, refetch updated details
                if ($update_stmt->execute()) {
                    $message = "Course assignment updated successfully.";
                    $message_type = "success";

                    // Refetch updated assignment details
                    $query = "SELECT sc.status, c.course_id, c.course_name, s.name AS student_name 
                              FROM student_courses sc
                              INNER JOIN courses c ON sc.course_id = c.course_id
                              INNER JOIN students s ON sc.student_id = s.student_id
                              WHERE sc.student_id = ? AND c.course_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param('ii', $student_id, $new_course_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $updated_assignment = $result->fetch_assoc();

                    // Update variables with the new data
                    $current_status = $updated_assignment['status'];
                    $current_course_id = $updated_assignment['course_id'];
                    $course_name = $updated_assignment['course_name'];
                    $student_name = $updated_assignment['student_name'];
                } else {
                    $message = "Failed to update the course assignment. Please try again.";
                    $message_type = "error";
                }
            } catch (Exception $e) {
                error_log("Error updating course assignment: " . $e->getMessage());
                $message = "An unexpected error occurred. Please try again later.";
                $message_type = "error";
            }
        }
    }
} catch (Exception $e) {
    error_log("Error fetching data for updating assignment: " . $e->getMessage());
    $_SESSION['error_message'] = "Unable to load assignment data. Please try again later.";
    header("Location: $redirect_url");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Course Assignment</title>
</head>
<body>
    <h1>Update Course Assignment</h1>

    <form method="post" action="">
        <label for="student_name">Student:</label>
        <input type="text" id="student_name" value="<?php echo htmlspecialchars($student_name, ENT_QUOTES, 'UTF-8'); ?>" readonly style="background-color: #f0f0f0;"><br><br>

        <label for="course_id">Select Course:</label>
        <select name="course_id" id="course_id" required>
            <option value="">-- Select a Course --</option>
            <option value="<?php echo $current_course_id; ?>" selected>
                <?php echo htmlspecialchars($course_name, ENT_QUOTES, 'UTF-8'); ?>
            </option>
            <?php foreach ($available_courses as $course): ?>
                <?php if ($course['course_id'] !== $current_course_id): ?>
                    <option value="<?php echo $course['course_id']; ?>">
                        <?php echo htmlspecialchars($course['course_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select><br><br>

        <label for="status">Select Status:</label>
        <select name="status" id="status" required>
            <option value="">-- Select a Status --</option>
            <option value="<?php echo $current_status; ?>" selected>
                <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $current_status)), ENT_QUOTES, 'UTF-8'); ?>
            </option>
            <?php foreach ($statuses as $status): ?>
                <option value="<?php echo $status; ?>">
                    <?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $status)), ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">Update</button>
    </form>

    <?php if (!empty($message)): ?>
        <p style="color: <?php echo $message_type === 'success' ? 'green' : 'red'; ?>;">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <br>
    <a href="manage_student_courses.php">Back to Manage Student Courses</a>
</body>
</html>
