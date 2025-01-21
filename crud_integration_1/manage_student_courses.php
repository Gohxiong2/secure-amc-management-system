<?php
session_start();
require 'db_connect.php';

// If the "Edit" button is clicked, save student_id and course_name into session variables
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_session'])) {
    // Store the student ID and course name into session variables for use in other pages
    $_SESSION['student_id'] = $_POST['student_id'];
    $_SESSION['course_name'] = $_POST['course_name'];

    // Redirect to the update_course_assignment.php page
    header("Location: update_course_assignment.php");
    exit();
}





// Regenerate session ID periodically (every 5 minutes) for security purposes
if (!isset($_SESSION['regenerated_time']) || time() - $_SESSION['regenerated_time'] > 300) {
    session_regenerate_id(true);
    $_SESSION['regenerated_time'] = time();
}

// Get the logged-in user's ID and role
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Redirect to 403.php if the role is not admin, or faculty.
$allowed_roles = ['admin', 'faculty'];
if (!in_array($role, $allowed_roles)) {
    header("Location: 403.php");
    exit();
}

// Initialize an array to store student and course data
$students = [];

try {
    // Define the query based on the user's role
    if ($role === 'admin') {
        // Admin: Fetch all student-course assignments
        $query = "SELECT s.student_id, s.name AS student_name, s.email, c.course_name, sc.status
                  FROM students s
                  INNER JOIN student_courses sc ON s.student_id = sc.student_id
                  INNER JOIN courses c ON sc.course_id = c.course_id
                  ORDER BY s.name, c.course_name";
        $stmt = $conn->prepare($query);
    } elseif ($role === 'faculty') {
        // Faculty: Fetch only student-course assignments for courses created by the logged-in faculty member
        $query = "SELECT s.student_id, s.name AS student_name, s.email, c.course_name, sc.status
                  FROM students s
                  INNER JOIN student_courses sc ON s.student_id = sc.student_id
                  INNER JOIN courses c ON sc.course_id = c.course_id
                  WHERE c.course_id IN (
                      SELECT course_id FROM faculty WHERE user_id = ?
                  )
                  ORDER BY s.name, c.course_name";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $user_id);
    }

    // Execute the query and fetch results
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
} catch (Exception $e) {
    // Log the error message for debugging purposes
    error_log("Error fetching student courses: " . $e->getMessage());

    // Set an error message to be displayed to the user and redirect to the dashboard
    $_SESSION['error_message'] = "Unable to load student courses. Please try again later.";
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Student Courses</title>
    <style>
        table {
            border-collapse: collapse;
            margin-top: 20px;
            width: 60%;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        form {
            display: inline;
        }
        .message {
            margin: 20px 0;
            font-weight: bold;
        }
        .action-buttons button {
            margin: 0 5px;
        }
    </style>
</head>
<body>
    <h1>Manage Student Courses</h1>

    <a href="assign_student_courses.php">Assign Student to Courses</a><br><br>

    <?php if (!empty($students)): ?>
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Email</th>
                    <th>Course Name</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($student['course_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($student['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="action-buttons">
                            <form method="post" action="">
                                <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                <input type="hidden" name="course_name" value="<?php echo htmlspecialchars($student['course_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" name="set_session">Edit</button>
                            </form>
                            <form method="post" action="delete_course_assignment.php" onsubmit="return confirm('Are you sure you want to delete this assignment?');">
                                <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                <input type="hidden" name="course_name" value="<?php echo htmlspecialchars($student['course_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <h3 style="font-weight: bold;">No student courses found. Assign courses to your students to manage them here.</h3>
    <?php endif; ?>

    <br>
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>
