<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


// Regenerate session ID periodically (every 5 minutes)
if (!isset($_SESSION['regenerated_time']) || time() - $_SESSION['regenerated_time'] > 300) {
    session_regenerate_id(true);
    $_SESSION['regenerated_time'] = time();
}

// Fetch user details
$username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
$role = $_SESSION['role'];


// Redirect to 403.php if the role is not admin, faculty, or student
$allowed_roles = ['admin', 'faculty', 'student'];
if (!in_array($role, $allowed_roles)) {
    header("Location: 403.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo $username; ?>!</h1>
    <p>Your role: <strong><?php echo ucfirst($role); ?></strong></p>

    <div class="menu">
    <a href="manage_student_courses.php">Manage Student Courses</a>
    <a href="courses_menu.php">Manage Courses</a>
    <a href="logout.php">Logout</a>
    </div>

</body>
</html>
