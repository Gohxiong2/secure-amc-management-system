<?php
session_start();
require 'db_connect.php';


if (!isset($conn)) {
    $conn = null;
}
if ($conn === null) {
    $_SESSION['error_message'] = "Database connection error. Please try again later.";
}


function enforce_session_timeout($timeout = 300) {
    // Check if last activity is set
    if (isset($_SESSION['last_activity'])) {
        $elapsed_time = time() - $_SESSION['last_activity']; 
        if ($elapsed_time > $timeout) {
            session_unset();
            session_destroy();
            header("Location: login.php?error=session_expired");
            exit();
        }
    }
    // Update the last activity timestamp
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


check_login(); 
enforce_session_timeout(); 
check_role_access(['admin', 'faculty']); 



function fetch_courses($conn, $user_id, $role) {
    if ($conn === null) {
        return [];
    }

    $courses = []; // Initialize an empty array for courses

    try {
        if ($role === 'admin') {
            // Admin users can view all courses
            $query = "SELECT course_id, course_name, course_code, start_date, end_date FROM courses ORDER BY start_date DESC";
            $stmt = $conn->prepare($query);
        } elseif ($role === 'faculty') {
            // Faculty users can view only the courses they created
            $query = "SELECT c.course_id, c.course_name, c.course_code, c.start_date, c.end_date 
                      FROM courses c
                      INNER JOIN faculty f ON c.course_id = f.course_id 
                      WHERE f.user_id = ? 
                      ORDER BY c.start_date DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $user_id);
        } else {
            return $courses;
        }


        if (!$stmt->execute()) {
            throw new Exception("Database query failed: " . $stmt->error);
        }


        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $courses[] = array_map(fn($value) => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $row);
        }
    } catch (Exception $e) {
        error_log("Error fetching courses: " . $e->getMessage());
        $_SESSION['error_message'] = "Unable to load courses. Please try again later.";
    }

    return $courses;
}


$courses = [];
if ($conn !== null) {
    $courses = fetch_courses($conn, $_SESSION['user_id'], $_SESSION['role']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses</title>

    <style>
        table {
            width: 60%; 
            table-layout: fixed; 
            border-collapse: collapse;
            margin-top: 20px;
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

        /* Specific Column Adjustments */
        td:nth-child(2) { /* Course Name column */
            max-width: 200px; /* Set a max width for wrapping */
            word-wrap: break-word; /* Wrap words to fit within cell */
            overflow-wrap: break-word; /* Ensure long words wrap properly */
        }


        form {
            display: inline-block;
        }
        .action-buttons button {
            margin: 0 5px;
        }


    </style>



    <script>
        // Function to confirm course deletion with a custom message
        function confirmDeletion(courseName) {
            return confirm(`Are you sure you want to delete the course "${courseName}"? This action cannot be undone.`);
        }
    </script>



</head>
<body>
    <?php if (!empty($_SESSION['success_message']) || !empty($_SESSION['error_message'])): ?>

            <span><?php
                echo !empty($_SESSION['success_message']) ? htmlspecialchars($_SESSION['success_message']) : htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['success_message'], $_SESSION['error_message']);
            ?></span>
            <button class="close-btn" style="
                background: none;
                border: none;
                color: white;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
                margin-left: 15px;
            " onclick="this.parentElement.style.display='none';">&times;</button>
        </div>
    <?php endif; ?>


    <h1>Manage Courses</h1>
    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'faculty'): ?>
        <a href="create_course.php">Create a New Course</a>
    <?php endif; ?>
        

    <?php if ($conn === null): ?>
        <!-- Display database connection error -->
        <p style="color: red; font-weight: bold; margin: 20px 0;">
            Database connection error. Please try again later.
        </p>

    <?php elseif (!empty($courses)): ?>
        <!-- Display courses table -->
        <table>
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?php echo $course['course_code']; ?></td>
                        <td><?php echo $course['course_name']; ?></td>
                        <td><?php echo date('d M Y', strtotime($course['start_date'])); ?></td>
                        <td>
                            <?php
                            if (!$course['end_date']) {
                                echo "Ongoing";
                            } elseif (strtotime($course['end_date']) > time()) {
                                echo "Ongoing (Ends on " . date('d M Y', strtotime($course['end_date'])) . ")";
                            } else {
                                echo date('d M Y', strtotime($course['end_date']));
                            }
                            ?>
                        </td>
                        <td class="action-buttons">
                            <form method="get" action="update_course.php">
                                <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                <button type="submit" title="Edit this course">Edit</button>
                            </form>
                            <form method="get" action="delete_course.php" onsubmit="return confirmDeletion('<?php echo $course['course_name']; ?>');">
                                <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                <button type="submit" title="Delete this course">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <h3 style="font-weight: bold;">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'faculty'): ?>
                No courses found. Use the "Create a New Course" link above to add your first course!
            <?php else: ?>
                No courses are available for you to view at the moment.
            <?php endif; ?>
            </h3>
    <?php endif; ?>

    <!--Link to Dashboard.php -->
    <br>
    <a href="dashboard.php">Back to Dashboard</a>


</body>
</html>
