<?php
require_once 'db_connect.php';
require_once 'security.php';

// Security & Authentication
verifyAuthentication();
enforceSessionTimeout(600);

// Initialize variables
$user_id = $_SESSION['user_id'];
$isAdmin = isAdmin();
$isFaculty = isFaculty();
$isStudent = $isAdmin === $isFaculty;

// Fetch student course assignments
$query = "SELECT s.student_id, s.name AS student_name, s.student_number, 
                 c.course_id, c.course_name, sc.status
          FROM student_courses sc
          INNER JOIN students s ON sc.student_id = s.student_id
          INNER JOIN courses c ON sc.course_id = c.course_id";

if ($isFaculty) { // Restrict to faculty's assigned courses
    $query .= " WHERE c.course_id IN (SELECT course_id FROM faculty WHERE user_id = ?)";
} elseif ($isStudent) { // Student can only view their own coures and status
    $query .= " WHERE s.user_id = ?";
}

$query .= " ORDER BY s.name ASC, c.course_name ASC";
$stmt = $conn->prepare($query);

if ($isFaculty || $isStudent) {
    $stmt->bind_param('i', $user_id);
}

$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Course Management</title>
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 1400px; margin-top: 2rem; }
        .card { border-radius: 0.75rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1); }
        .table { border-radius: 0.5rem; overflow: hidden; }
        .table thead { background: #4da8da; color: white; }
        .status-badge {
            border-radius: 1rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            text-transform: capitalize;
        }
        .action-btn { min-width: 110px; transition: all 0.2s; }
        .action-btn:hover { transform: translateY(-1px); }
        .empty-state { background: #f8f9fa; border-radius: 0.5rem; padding: 2rem; text-align: center; }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mb-4">
            <div class="col-auto">
                <h2 class="text-primary text-center display-7 fw-bold">Student Courses</h2>
            </div>
        </div>
        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <?php if($isAdmin || $isFaculty): ?>
                    <a href="assign_student_courses.php" class="btn btn-primary">Assign Course</a>
                <?php endif; ?>
            </div>

            <?php displayMessages(); ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Student Name</th>
                            <th scope="col">Student ID</th>
                            <th scope="col">Course</th>
                            <th scope="col">Status</th>
                            <?php if($isAdmin || $isFaculty): ?>
                                <th scope="col" class="text-center">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($results)): ?>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['student_number'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['course_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge status-badge 
                                            <?= match(strtolower($row['status'])) {
                                                'start' => 'bg-primary',
                                                'in-progress' => 'bg-warning text-dark',
                                                'ended' => 'bg-secondary',
                                                default => 'bg-info'
                                            } ?>">
                                            <?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <?php if($isAdmin || $isFaculty): ?>
                                        <td class="text-center">
                                            <a href="update_student_courses.php?student_id=<?= $row['student_id'] ?>&course_id=<?= $row['course_id'] ?>" 
                                            class="btn btn-sm btn-outline-primary action-btn me-2">
                                            Manage
                                            </a>
                                            <form method="POST" action="delete_student_courses.php" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="student_id" value="<?= $row['student_id'] ?>">
                                                <input type="hidden" name="course_id" value="<?= $row['course_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger action-btn" 
                                                    onclick="return confirm('Are you sure you want to delete this assignment?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <h3 class="h5 text-muted">No active course assignments found</h3>
                                        <p class="text-muted mb-0">Start by assigning students to courses</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</body>
</html>
