<?php
require_once 'db_connect.php';

// Authorization check
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'faculty'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = '';

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }

    // Input validation
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $index_number = htmlspecialchars(trim($_POST['index_number']));
    $class_id = (int)$_POST['class_id'];
    $password = $_POST['password'];

    // Validate inputs
    if (empty($name)) $errors[] = "Name is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (strlen($phone) < 8) $errors[] = "Invalid phone number";
    if (empty($index_number)) $errors[] = "Index number required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, hashed_password, role) VALUES (?, ?, 'student')");
            $stmt->execute([$email, $hashed_password]);
            $user_id = $pdo->lastInsertId();

            // Create student
            $stmt = $pdo->prepare("INSERT INTO students (user_id, name, email, phone, index_number, class_id) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $name, $email, $phone, $index_number, $class_id]);

            $pdo->commit();
            $success = "Student created successfully";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
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