<?php
require_once 'db_connect.php';
require_once 'security.php';

//Security & Authentication Checks
verifyAuthentication();
enforceSessionTimeout(300);

//Database Connection Checks
validateDatabaseConnection($conn);

// Check user role
$isAdmin = isAdmin();
$isFaculty = isFaculty();
$user_id = $_SESSION['user_id'];


// // Fetch courses based on role
if ($isAdmin) {
    // Admin: Access all courses
    $query = "
        SELECT 
            c.course_id, 
            c.course_name, 
            c.course_code, 
            c.start_date, 
            c.end_date 
        FROM courses c 
        ORDER BY c.start_date DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} elseif ($isFaculty) {
    // Faculty: Access only courses they created
    $query = "
        SELECT 
            c.course_id, 
            c.course_name, 
            c.course_code, 
            c.start_date, 
            c.end_date 
        FROM courses c
        INNER JOIN faculty f ON c.course_id = f.course_id
        WHERE f.user_id = ?
        ORDER BY c.start_date DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} else {
    // Students or unauthorized roles: No access
    header("Location: 403.php");
    exit();
}


$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Records</title>
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 1200px; margin-top: 2rem; }
        .table { border-radius: 0.5rem; overflow: hidden; }
        .table thead { background-color: #4da8da; color: white; }
        .badge { border-radius: 0.5rem; padding: 0.375rem 0.75rem; }
        .header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .empty-state { background: #f8f9fa; border-radius: 0.5rem; padding: 2rem; }
        .status-badge {
            border-radius: 1rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            text-transform: capitalize;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <!-- Title -->
        <div class="row justify-content-center mb-4">
            <div class="col-auto">
                <h2 class="text-primary text-center display-7 fw-bold">Course Records</h2>
            </div>
        </div>
        <div class="card p-4">
            <!-- Header -->
            <div class="header-container">
                <a href="dashboard.php" class="btn btn-outline-primary">
                    Back to Dashboard
                </a>
                <div>
                    <?php if ($isAdmin || $isFaculty): ?>
                        <a href="create_course.php" class="btn btn-primary">Create New Course</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($_SESSION['success_message']) || !empty($_SESSION['error_message'])): ?>
                <div class="alert <?= !empty($_SESSION['success_message']) ? 'alert-success' : 'alert-danger'; ?>">
                    <?= htmlspecialchars($_SESSION['success_message'] ?? $_SESSION['error_message']); ?>
                    <?php unset($_SESSION['success_message'], $_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>



            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
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
                        <?php if (!empty($courses)): ?>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?= htmlspecialchars($course['course_code']) ?></td>
                                    <td><?= htmlspecialchars($course['course_name']) ?></td>
                                    <td><?= date('d M Y', strtotime($course['start_date'])) ?></td>
                                    <td>
                                        <span class="badge status-badge 
                                            <?= !$course['end_date'] ? 'bg-primary' : (strtotime($course['end_date']) > time() ? 'bg-warning text-dark' : 'bg-primary') ?>">
                                            <?= !$course['end_date'] ? 'Ongoing' : (strtotime($course['end_date']) > time() ? 'Ongoing (Ends ' . date('d M Y', strtotime($course['end_date'])) . ')' : date('d M Y', strtotime($course['end_date']))) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="update_course.php?course_id=<?= $course['course_id'] ?>" class="btn btn-sm btn-outline-primary">
                                            Manage
                                        </a>
                                        <a href="delete_course.php?course_id=<?= $course['course_id'] ?>" class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to delete this course?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if (empty($courses)): ?>
                    <div class="empty-state p-5 text-center mt-4">
                        <h3 class="h5 text-muted">No courses available</h3>
                        <p class="text-muted mb-0">Start by creating a new course.</p>
                        <?php if ($isAdmin || $isFaculty): ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
