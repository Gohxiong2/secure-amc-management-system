<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['regenerated_time']) || time() - $_SESSION['regenerated_time'] > 300) {
    session_regenerate_id(true);
    $_SESSION['regenerated_time'] = time();
}

$username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
$role = $_SESSION['role'];

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dashboard-card {
            border-radius: 15px;
            transition: transform 0.2s;
            background: #e3f2fd;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Dashboard</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">Welcome, <?php echo $username; ?>!</span>
                <a href="logout.php" class="btn btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card dashboard-card shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title text-primary">Your Role</h5>
                        <span class="badge bg-primary rounded-pill fs-6"><?php echo ucfirst($role); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card dashboard-card shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title text-primary">Manage Student Courses</h5>
                        <a href="read_student.php" class="btn btn-primary rounded-pill mt-3">Go to Panel</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card dashboard-card shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title text-primary">Manage Courses</h5>
                        <a href="courses_menu.php" class="btn btn-primary rounded-pill mt-3">Go to Panel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>