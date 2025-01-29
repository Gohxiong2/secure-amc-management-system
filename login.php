<?php
session_start();
// Regenerate session ID periodically (every 5 minutes)
if (!isset($_SESSION['regenerated_time']) || time() - $_SESSION['regenerated_time'] > 300) {
    session_regenerate_id(true);
    $_SESSION['regenerated_time'] = time();
}

require 'db_connect.php';

function login_user($conn, $username, $password) {
    // Sanitize user input
    $username = mysqli_real_escape_string($conn, $username);

    // Prepare the SQL statement
    $query = "SELECT user_id, hashed_password, role FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        // Bind parameters and execute the query
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);

        // Fetch the result
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        // Verify password and handle login
        if ($user && password_verify($password, $user['hashed_password'])) {
            // Check if the user already has an active session
            if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] !== $user['user_id']) {
                // New login: Regenerate session ID for security
                session_regenerate_id(true);
            }

            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            // Redirect to the dashboard page
            header("Location: dashboard.php");
            exit();
        } else {
            return "Invalid username or password.";
        }
    } else {
        return "An error occurred. Please try again later.";
    }
}

// Handle form submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $message = login_user($conn, $username, $password);
    } else {
        $message = "Please fill in all fields.";
    }
}

// Handle session expiration message
if (isset($_GET['error']) && $_GET['error'] === 'session_expired') {
    $message = "Your session has expired. Please log in again.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-card {
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .btn-custom {
            background: #0d6efd;
            color: white;
            border-radius: 25px;
            padding: 10px 30px;
        }
    </style>
</head>
<body class="bg-primary">
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card login-card">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4 text-primary">Login</h2>
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>

                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control rounded-pill" id="username" name="username" required>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control rounded-pill" id="password" name="password" required>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary rounded-pill">Login</button>
                            </div>

                            <div class="text-center">
                            <a href="" class="text-primary text-decoration-none">Forgot password?</a>
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