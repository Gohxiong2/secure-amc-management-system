<?php
require 'db_connect.php';

function register_user($username, $password, $role) {
    $conn = db_connect();

    // Validate role
    if (!in_array($role, ['admin', 'faculty', 'student'])) {
        echo "Invalid role selected.";
        return false;
    }

    // Check if username already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "Username already taken.";
        $stmt->close();
        $conn->close();
        return false;
    }

    $stmt->close();

    // Hash the password using bcrypt
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert new user with role
    $stmt = $conn->prepare("INSERT INTO users (username, hashed_password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $role);

    if ($stmt->execute()) {
        echo "Registration successful.";
        $stmt->close();
        $conn->close();
        return true;
    } else {
        echo "Error: " . $stmt->error;
        $stmt->close();
        $conn->close();
        return false;
    }
}

// Usage example
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    if (!empty($username) && !empty($password) && !empty($role)) {
        register_user($username, $password, $role);
    } else {
        echo "Please fill in all fields.";
    }
}

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    if (!empty($username) && !empty($password) && !empty($role)) {
        $message = register_user($username, $password, $role);
    } else {
        $message = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration</title>
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
            <option value="student">Student</option>
            <option value="faculty">Faculty</option>
            <option value="admin">Admin</option>
        </select><br><br>

        <input type="submit" value="Register">
    </form>
    <p><?php echo htmlspecialchars($message); ?></p>
    <p>Already have an account? <a href="login.php">Login here</a>.</p>
</body>
</html>
