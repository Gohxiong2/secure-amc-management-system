<?php
include 'db_connect.php';
require_once 'security.php';

verifyAuthentication();

// Check if admin to show all records
$isAdmin = isAdmin();
$student_id = $_SESSION['user_id']; // Assume user_id is stored in session

if ($isAdmin) {
    $students = $conn->query("SELECT * FROM students");
} else {
    $stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $students = $stmt->get_result();
}

$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 1200px; margin-top: 30px; }
        .table { border-radius: 10px; overflow: hidden; }
        .table thead { background-color: #4da8da; color: white; }
        .badge { border-radius: 10px; }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="card p-4">
            <div class="d-flex justify-content-between mb-4">
                <h2 class="text-primary">Student Records</h2>
                <?php if ($isAdmin): ?>
                    <a href="create_student.php" class="btn btn-primary">Create New</a>
                <?php endif; ?>
            </div>

            <?php displayMessages(); ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Department</th>
                            <?php if ($isAdmin): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($student = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['student_number']) ?></td>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                            <td><?= htmlspecialchars($student['email']) ?></td>
                            <td><?= htmlspecialchars($student['phone']) ?></td>
                            <td>
                                <span class="badge bg-primary">
                                    <?= htmlspecialchars($student['department']) ?>
                                </span>
                            </td>
                            <?php if ($isAdmin): ?>
                            <td>
                                <a href="update_student.php?id=<?= $student['student_id'] ?>" 
                                   class="btn btn-sm btn-outline-primary me-2">Edit</a>
                                <form method="POST" action="delete_student.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                        onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>