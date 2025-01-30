<?php
require_once 'db_connect.php';
require_once 'security.php';

verifyAuthentication();
verifyAdminOrFacultyAccess();
enforceSessionTimeout();

// Updated query to remove course_id since it's not in the schema
$query = "SELECT c.* 
          FROM classes c 
          ORDER BY c.start_date DESC";
$result = mysqli_query($conn, $query);

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Classes</title>
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .container { max-width: 1200px; margin-top: 30px; }
        .table { border-radius: 10px; overflow: hidden; }
        .table thead { background-color: #4da8da; color: white; }
        .header-container { display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mb-4">
            <div class="col-auto">
                <h2 class="text-primary text-center display-7 fw-bold">Class Management</h2>
            </div>
        </div>
        <div class="card p-4">
            <div class="header-container mb-4">
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <?php if (isAdmin() || isFaculty()): ?>
                    <a href="create_class.php" class="btn btn-primary">Create New Class</a>
                <?php endif; ?>
            </div>
            <?php displayMessages(); ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Semester</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['duration']); ?></td>
                                <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['end_date']); ?></td>
                                <td>
                                    <?php if (isAdmin() || isFaculty()): ?>
                                        <a href="update_class.php?id=<?php echo $row['class_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary me-2">Manage</a>
                                        <?php if (isAdmin()): ?>
                                            <form method="POST" action="delete_class.php" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="class_id" value="<?php echo $row['class_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                        onclick="return confirm('Are you sure you want to delete this class?')">
                                                    Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>