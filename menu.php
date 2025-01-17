<?php
session_start();
require 'db_connect.php';
require 'check_login.php';

check_login();

// Fetch orders for the logged-in user or all users based on the role
function role_based_access($user_id, $role) {
    if ($role === 'admin') {

    } elseif ($role === 'faculty') {
        
    } elseif ($role === 'student') {

    }
}

// Display content based on role
role_based_access($_SESSION['user_id'], $_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Menu</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
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
    </style>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <ul>
        <li><a href="create_course.php">Create Course</a></li>
        <li><a href="read_course.php">View Courses</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</body>
</html>
