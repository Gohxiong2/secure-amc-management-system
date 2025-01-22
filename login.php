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
</head>
<body>
    <h1>Login</h1>
    <form method="post" action="">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>

        <input type="submit" value="Login">
    </form>
    <p><?php echo htmlspecialchars($message); ?></p>
    <p>Register new account? <a href="register.php">Register here</a>.</p>
</body>
</html>
