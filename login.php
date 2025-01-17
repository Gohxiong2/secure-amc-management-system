<?php
session_start();
require 'db_connect.php';

function login_user($username, $password) {
    $conn = db_connect();

    // Retrieve user data and role
    $stmt = $conn->prepare("SELECT user_id, hashed_password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $hashed_password, $role);
    $stmt->fetch();

    if ($id && password_verify($password, $hashed_password)) {
        // Password is correct, set session variables
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;

        $stmt->close();
        $conn->close();

        header("Location: menu.php");
        exit();
    } else {
        // Invalid credentials
        $stmt->close();
        $conn->close();
        echo "Invalid username or password.";
    }
}

// Usage example
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        login_user($username, $password);
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
