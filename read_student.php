<?php
// Get modular functions
require_once 'db_connect.php';
require_once 'security.php';

//Only verified users. Auto timeout session after 5 minutes of inactivity.
verifyAuthentication();
enforceSessionTimeout(300);

// Check if admin to show all records
$isAdmin = isAdmin();
$isFaculty = isFaculty();
$user_id = $_SESSION['user_id'];


// Fetch student records
if ($isAdmin) { // Admin can view all student records
    $students = $conn->query("
        SELECT students.*, 
        department.name AS department_name,
        GROUP_CONCAT(courses.course_name SEPARATOR ', ') AS enrolled_courses
        FROM students
        JOIN department ON students.department_id = department.department_id
        LEFT JOIN student_courses ON students.student_id = student_courses.student_id
        LEFT JOIN courses ON student_courses.course_id = courses.course_id
        GROUP BY students.student_id
    ");
} 

if ($isFaculty) { // Faculty can view all student records, related to faculty's courses
    $stmt = $conn->prepare("
        SELECT students.*, 
        department.name AS department_name,
        GROUP_CONCAT(courses.course_name SEPARATOR ', ') AS enrolled_courses
        FROM students
        JOIN department ON students.department_id = department.department_id
        LEFT JOIN student_courses ON students.student_id = student_courses.student_id
        LEFT JOIN courses ON student_courses.course_id = courses.course_id
        JOIN faculty ON student_courses.course_id = faculty.course_id
        WHERE faculty.user_id = ?
        GROUP BY students.student_id
    ");
} else { // Student only can see their own records
    $stmt = $conn->prepare("
        SELECT students.*, 
        department.name AS department_name,
        GROUP_CONCAT(courses.course_name SEPARATOR ', ') AS enrolled_courses
        FROM students
        JOIN department ON students.department_id = department.department_id
        LEFT JOIN student_courses ON students.student_id = student_courses.student_id
        LEFT JOIN courses ON student_courses.course_id = courses.course_id
        WHERE students.user_id = ?
        GROUP BY students.student_id
    ");
}

if (!($isAdmin)) { // Admin queried already. Non admin uses prepared statement

    //user id is stored on the server, sql injection or XSS attack are not possible. In the sense that, client can't directly modify it
    $stmt->bind_param("i", $user_id);
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
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 1200px; margin-top: 30px; }
        .table { border-radius: 10px; overflow: hidden; }
        .table thead { background-color: #4da8da; color: white; }
        .badge { border-radius: 10px; }
        .course-list { max-width: 300px; white-space: normal; }
        .header-container { display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <!-- Centered Title -->
        <div class="row justify-content-center mb-4">
            <div class="col-auto">
                <h2 class="text-primary text-center display-7 fw-bold">Student Records</h2>
            </div>
        </div>
        <div class="card p-4">
            <div class="header-container mb-4">
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <div>
                    <?php if ($isAdmin || $isFaculty): ?>
                        <a href="create_student.php" class="btn btn-primary">Create New</a>
                    <?php endif; ?>
                </div>
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
                            <th>Enrolled Courses</th>
                            <?php if ($isAdmin || $isFaculty): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students->num_rows > 0): ?>
                        <?php while($student = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['student_number']) ?></td>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                            <td><?= htmlspecialchars($student['email']) ?></td>
                            <td><?= htmlspecialchars($student['phone']) ?></td>
                            <td>
                                <span class="badge bg-primary">
                                    <?= htmlspecialchars($student['department_name']) ?>
                                </span>
                            </td>
                            <td class="course-list">
                                <?= $student['enrolled_courses'] 
                                    ? htmlspecialchars($student['enrolled_courses'])
                                    : '<span class="text-muted">No courses assigned</span>' ?>
                            </td>
                            <?php if ($isAdmin): ?>
                            <td>
                                <a href="update_student.php?id=<?= $student['student_id'] ?>" 
                                   class="btn btn-sm btn-outline-primary me-2">Manage</a>
                                <form method="POST" action="delete_student.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="user_id" value="<?= $student['user_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                        onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                            <?php elseif ($isFaculty): ?>
                            <td>
                                <a href="update_student.php?id=<?= $student['student_id'] ?>" 
                                   class="btn btn-sm btn-outline-primary me-2">Manage</a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                        <?php endif; ?>
                        <tr>
                            <a href="forgot_password.php" class="btn btn-outline-primary">
                            Update Password
                            </a>
                        </tr>
                    </tbody>
                </table>
                <?php if ($students->num_rows === 0): ?>
                    <div class="empty-state p-5 text-center mt-4">
                        <h3 class="h5 text-muted">No student records available</h3>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Local Bootstrap JS -->
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</body>
</html>