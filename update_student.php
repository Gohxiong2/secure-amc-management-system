<?php
require_once 'db_connect.php';

// Authorization check
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'faculty'])) {
    header('Location: login.php');
    exit;
}

// Get student ID
$student_id = $_GET['id'] ?? null;
if (!$student_id) die("Invalid student ID");

// Fetch student data
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

$errors = [];
$success = '';

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }

    // Validate inputs
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $index_number = htmlspecialchars(trim($_POST['index_number']));
    $class_id = (int)$_POST['class_id'];

    // Update database
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE students SET 
                            name = ?, email = ?, phone = ?, index_number = ?, class_id = ?
                            WHERE student_id = ?");
        $stmt->execute([$name, $email, $phone, $index_number, $class_id, $student_id]);

        $pdo->commit();
        $success = "Student updated successfully";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errors[] = "Update failed: " . $e->getMessage();
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Student</title>
</head>
<body>
    <h1>Update Student</h1>
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
        
        <label>Name: <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required></label><br>
        <label>Email: <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required></label><br>
        <label>Phone: <input type="tel" name="phone" value="<?= htmlspecialchars($student['phone']) ?>" required></label><br>
        <label>Index Number: <input type="text" name="index_number" value="<?= htmlspecialchars($student['index_number']) ?>" required></label><br>
        <label>Class: 
            <select name="class_id" required>
                <?php 
                $classes = $pdo->query("SELECT class_id, class_name FROM classes")->fetchAll();
                foreach ($classes as $class): ?>
                    <option value="<?= $class['class_id'] ?>" <?= $class['class_id'] == $student['class_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($class['class_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>
        
        <button type="submit">Update Student</button>
    </form>
</body>
</html>