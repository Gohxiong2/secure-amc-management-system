<?php
require 'db_connect.php';

function register_user($conn, $username, $password, $role) {
    // Validate role
    if (!in_array($role, ['admin', 'faculty', 'student'])) {
        return "Invalid role selected.";
    }

    // Sanitize user input
    $username = mysqli_real_escape_string($conn, $username);

    // Check if username already exists
    $query = "SELECT user_id FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_fetch_assoc($result)) {
            return "Username already taken.";
        }
    } else {
        return "Error checking username: " . mysqli_error($conn);
    }

    // Hash the password securely
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert new user with role
    $query = "INSERT INTO users (username, hashed_password, role) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sss', $username, $hashed_password, $role);
        if (mysqli_stmt_execute($stmt)) {
            return "Registration successful.";
        } else {
            return "Registration failed. Please try again.";
        }
    } else {
        return "Error during registration: " . mysqli_error($conn);
    }
}

// Handle form submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    if (!empty($username) && !empty($password) && !empty($role)) {
        $message = register_user($conn, $username, $password, $role);
    } else {
        $message = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .registration-card {
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .form-control, .form-select {
            border-radius: 25px !important;
            padding: 12px 20px;
        }
    </style>
</head>
<body class="bg-primary">
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card registration-card">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4 text-primary">Create New Account</h2>
                        
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo strpos($message, 'success') !== false ? 'success' : 'danger'; ?> rounded-pill">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="mb-4">
                                <label for="role" class="form-label">Select Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="admin">Admin</option>
                                    <option value="faculty">Faculty</option>
                                    <option value="student">Student</option>
                                </select>
                            </div>

                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill py-3">
                                    Register Now
                                </button>
                            </div>

                            <div class="text-center">
                                <p class="text-muted mb-0">Already have an account?</p>
                                <a href="login.php" class="text-primary text-decoration-none fw-bold">
                                    Login here
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>