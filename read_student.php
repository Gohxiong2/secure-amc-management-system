<?php
require_once 'db_connect.php';

// Authorization check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch records based on role
if ($_SESSION['user_role'] === 'student') {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $pdo->query("SELECT s.*, c.class_name 
                       FROM students s 
                       JOIN classes c ON s.class_id = c.class_id");
}
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Records</title>
</head>
<body>
    <h1>Student Records</h1>
    <table border="1">
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Index Number</th>
            <th>Class</th>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
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
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
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