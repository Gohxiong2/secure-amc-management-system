<?php
require_once 'db_connect.php';
require_once 'security.php';

// Verifying level of access & session timeouts
verifyAuthentication();
verifyAdminOrFacultyAccess();
enforceSessionTimeout(300);

// Checks if id exists and casts it into integer
// if the id does not exist, $class_id = 0
$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid CSRF token";
        header("Location: update_class.php?id=" . $class_id);
        exit();
    }

    // Sanitization
    $class_name = sanitizeInput($_POST['class_name']);
    $duration = sanitizeInput($_POST['duration']);
    $start_date = sanitizeInput($_POST['start_date']);
    $end_date = sanitizeInput($_POST['end_date']);
    $class_id = (int)$_POST['class_id'];

    // Error handling
    if (empty($class_name) || empty($duration)) {
        $_SESSION['error'] = "All fields are required";
    } elseif (strtotime($start_date) > strtotime($end_date)){
        $_SESSION['error'] = "Start date cannot be later than end date!";
    } elseif ((preg_match('/^[a-zA-Z0-9.-]+$/', $class_name)) || (preg_match('/^[a-zA-Z0-9.-]+$/', $duration))) {
        $_SESSION['error'] = "No special characters allowed!";
    } else {
        
        //Preparing and binding parameters
        $stmt = $conn->prepare("UPDATE classes SET class_name=?, duration=?, start_date=?, end_date=? WHERE class_id=?");
        $stmt->bind_param("ssssi", $class_name, $duration, $start_date, $end_date, $class_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Class updated successfully";
            header("Location: read_class.php");
            exit();
        } else {
            $_SESSION['error'] = "Error updating class: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get class data
$stmt = $conn->prepare("SELECT * FROM classes WHERE class_id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();
$class = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Class</title>
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .container { max-width: 1200px; margin-top: 30px; }
        .table { border-radius: 10px; overflow: hidden; }
        .table thead { background-color: #4da8da; color: white; }
        .header-container { display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mb-4">
            <div class="col-auto">
                <h2 class="text-primary text-center display-7 fw-bold">Update Class</h2>
            </div>
        </div>
        <div class="card p-4">
            <?php displayMessages(); ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                <div class="mb-3">
                    <label for="class_name" class="form-label">Class Name:</label>
                    <input type="text" class="form-control" id="class_name" name="class_name" 
                           value="<?php echo htmlspecialchars($class['class_name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="duration" class="form-label">Duration:</label>
                    <input type="text" class="form-control" id="duration" name="duration" 
                           value="<?php echo htmlspecialchars($class['duration']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="start_date" class="form-label">Start Date:</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo htmlspecialchars($class['start_date']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="end_date" class="form-label">End Date:</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo htmlspecialchars($class['end_date']); ?>" required>
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Update Class</button>
                    <a href="read_class.php" class="btn btn-outline-primary">Back to Classes</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>