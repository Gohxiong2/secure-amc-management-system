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
</head>
<body>
    <h1>Register a New User</h1>
    <form method="post" action="">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>

        <label for="role">Role:</label>
        <select id="role" name="role" required>
            <option value="admin">Admin</option>
            <option value="faculty">Faculty</option>
            <option value="student">Student</option>
        </select><br><br>

        <input type="submit" value="Register">
    </form>
    <p><?php echo htmlspecialchars($message); ?></p>
    <p>Already have an account? <a href="login.php">Login here</a>.</p>
</body>
</html>
