<?php
// Start the session to track user state and variables
session_start();
require 'db_connect.php'; // Include database connection script

// Ensure the user is logged in and has the correct role (admin or faculty)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'faculty'])) {
    header("Location: login.php"); // Redirect to login if user is not authenticated
    exit(); // Stop further script execution
}

// Regenerate session ID every 5 minutes to enhance session security
if (!isset($_SESSION['regenerated_time']) || time() - $_SESSION['regenerated_time'] > 300) {
    session_regenerate_id(true); // Regenerate session ID
    $_SESSION['regenerated_time'] = time(); // Update the regeneration timestamp
}

// Initialize message variables for feedback
$message = ""; // Feedback message
$message_type = ""; // Type of feedback ('success' or 'error')

// Safely retrieve and validate input data from POST request
$student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT); // Validate as integer
$course_name = filter_input(INPUT_POST, 'course_name', FILTER_SANITIZE_STRING); // Sanitize string

// Check if both student ID and course name are valid
if (!$student_id || !$course_name) {
    $_SESSION['error_message'] = "Invalid input data."; // Set error message
    header("Location: $redirect_url"); // Redirect back to the relevant page
    exit();
}

// the redirection URL.
$redirect_url = 'manage_student_courses.php';

// Check again if the student ID or course name is empty (defensive programming)
if (empty($student_id) || empty($course_name)) {
    $_SESSION['error_message'] = "Invalid request. Missing student or course information."; // Error message
    header("Location: $redirect_url"); // Redirect
    exit();
}

try {
    // Check if the course assignment exists in the database
    $query = "SELECT sc.course_id 
              FROM student_courses sc
              INNER JOIN courses c ON sc.course_id = c.course_id
              WHERE sc.student_id = ? AND c.course_name = ?";
    $stmt = $conn->prepare($query); // Prepare SQL statement
    $stmt->bind_param('is', $student_id, $course_name); // Bind parameters
    $stmt->execute(); // Execute query
    $result = $stmt->get_result(); // Get query result
    $assignment = $result->fetch_assoc(); // Fetch the assignment details

    // If no assignment is found, set an error message and redirect
    if (!$assignment) {
        $_SESSION['error_message'] = "The selected course assignment does not exist.";
        header("Location: $redirect_url");
        exit();
    }

    $course_id = $assignment['course_id']; // Retrieve course ID from the result

    // Additional permission check for faculty members
    if ($_SESSION['role'] === 'faculty') {
        $permission_query = "SELECT 1 
                             FROM faculty f
                             WHERE f.user_id = ? AND f.course_id = ?";
        $permission_stmt = $conn->prepare($permission_query); // Prepare SQL statement
        $permission_stmt->bind_param('ii', $_SESSION['user_id'], $course_id); // Bind parameters
        $permission_stmt->execute(); // Execute query
        $permission_result = $permission_stmt->get_result(); // Get query result

        // If faculty does not have permission, set error and redirect
        if ($permission_result->num_rows === 0) {
            $_SESSION['error_message'] = "You do not have permission to delete this course assignment.";
            header("Location: $redirect_url");
            exit();
        }
    }

    // Delete the course assignment
    $delete_query = "DELETE FROM student_courses WHERE student_id = ? AND course_id = ?";
    $delete_stmt = $conn->prepare($delete_query); // Prepare SQL statement
    $delete_stmt->bind_param('ii', $student_id, $course_id); // Bind parameters

    // Execute the deletion and set success or error message
    if ($delete_stmt->execute()) {
        $_SESSION['success_message'] = "Course assignment deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to delete the course assignment. Please try again.";
    }
} catch (Exception $e) {
    // Log the error and set a user-friendly error message
    error_log("Error deleting course assignment: " . $e->getMessage());
    $_SESSION['error_message'] = "An unexpected error occurred. Please try again later.";
}

// Redirect back to the appropriate management page
header("Location: $redirect_url");
exit(); // Stop further script execution
?>
