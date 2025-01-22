<?php
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch records
if ($_SESSION['role'] === 'student') {
    $stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $query = "SELECT s.*, c.class_name 
              FROM students s 
              JOIN classes c ON s.class_id = c.class_id";
    $result = mysqli_query($conn, $query);
}

$students = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Records</title>
    <style>
        .create-btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .create-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>Student Records</h1>
    
    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'faculty'): ?>
        <a href="create_student.php" class="create-btn">Create New Student</a>
    <?php endif; ?>
    
    <table border="1">
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Index Number</th>
            <th>Class</th>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <th>Actions</th>
            <?php endif; ?>
        </tr>
        <?php foreach ($students as $student): ?>
        <tr>
            <td><?= htmlspecialchars($student['name']) ?></td>
            <td><?= htmlspecialchars($student['email']) ?></td>
            <td><?= htmlspecialchars($student['phone']) ?></td>
            <td><?= htmlspecialchars($student['index_number']) ?></td>
            <td><?= htmlspecialchars($student['class_name'] ?? 'N/A') ?></td>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <td>
                    <a href="update_student.php?id=<?= $student['student_id'] ?>">Edit</a>
                    <a href="delete_student.php?id=<?= $student['student_id'] ?>" 
                       onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>