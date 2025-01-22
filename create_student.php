<?php
require_once 'db_connect.php';

// Authorization check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'faculty'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }

    // Input validation
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $index_number = htmlspecialchars(trim($_POST['index_number']));
    $class_id = (int)$_POST['class_id'];
    $password = $_POST['password'];

    // Validation
    if (empty($name)) $errors[] = "Name is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($index_number)) $errors[] = "Index number required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";

    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        
        try {
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (username, hashed_password, role) VALUES (?, ?, 'student')");
            mysqli_stmt_bind_param($stmt, 'ss', $email, $hashed_password);
            mysqli_stmt_execute($stmt);
            $user_id = mysqli_insert_id($conn);

            // Create student
            $stmt = mysqli_prepare($conn, "INSERT INTO students (user_id, name, email, phone, index_number, class_id) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'issssi', $user_id, $name, $email, $phone, $index_number, $class_id);
            mysqli_stmt_execute($stmt);

            mysqli_commit($conn);
            $success = "Student created successfully";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = "Database error: " . mysqli_error($conn);
        }
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Student</title>
</head>
<body>
    <h1>Create New Student</h1>
    <?php if ($errors): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?= $error ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>
    
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <label>Name: <input type="text" name="name" required></label><br>
        <label>Email: <input type="email" name="email" required></label><br>
        <label>Phone: <input type="tel" name="phone" required></label><br>
        <label>Index Number: <input type="text" name="index_number" required></label><br>
        <label>Class: 
            <select name="class_id" required>
                <?php 
                $classes = $pdo->query("SELECT class_id, class_name FROM classes")->fetchAll();
                foreach ($classes as $class): ?>
                    <option value="<?= $class['class_id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        
        <button type="submit">Create Student</button>
    </form>
</body>
</html>