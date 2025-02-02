<?php
require_once 'db_connect.php';
require_once 'security.php';

//Security & Authentication Checks
verifyAuthentication();
enforceSessionTimeout(300);

// Verify user role (admin and faculty only)
verifyAdminOrFacultyAccess();

$student_id = $_GET['student_id'] ?? null;
$course_id = $_GET['course_id'] ?? null;
$current_assignment = [];
$available_courses = [];
$has_active_courses = false;
$csrf_token = generateCsrfToken();

try {
// Get current assignment details
$stmt = $conn->prepare("SELECT sc.*, s.name AS student_name, c.course_name, sc.status
                        FROM student_courses sc
                        JOIN students s ON sc.student_id = s.student_id
                        JOIN courses c ON sc.course_id = c.course_id
                        WHERE sc.student_id = ? AND sc.course_id = ?");
$stmt->bind_param('ii', $student_id, $course_id);
$stmt->execute();
$current_assignment = $stmt->get_result()->fetch_assoc();

if (!$current_assignment) {
    $_SESSION['error'] = "Course assignment not found";
    header("Location: read_student_courses.php");
    exit();
}

// Retrieve status of the current assignment
$current_status = $current_assignment['status'];

// Fetch ENUM values for the status column from the database schema
$enum_query = "SHOW COLUMNS FROM student_courses LIKE 'status'";
$enum_result = $conn->query($enum_query);
$enum_row = $enum_result->fetch_assoc();
$enum_values = preg_replace("/^enum\('(.*)'\)$/", "$1", $enum_row['Type']);
$valid_statuses = explode("','", $enum_values);

// Check if the current status matches any of the valid statuses
$has_active_courses = in_array($current_status, $valid_statuses);

// Get available courses for reassignment (only if no active courses)
$course_query = "SELECT c.course_id, c.course_name 
                 FROM courses c
                 JOIN faculty f ON c.course_id = f.course_id
                 WHERE f.user_id = ?
                 AND c.course_id NOT IN (
                     SELECT course_id FROM student_courses 
                     WHERE student_id = ?
                 )";
$stmt = $conn->prepare($course_query);
$stmt->bind_param('ii', $_SESSION['user_id'], $student_id);
$stmt->execute();
$available_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading data";
    header("Location: read_student_courses.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token']);
    
    try {
        // Update Status Form
        if (isset($_POST['update_status'])) {
            $new_status = sanitizeInput($_POST['status']);
            
            $stmt = $conn->prepare("UPDATE student_courses 
                                  SET status = ? 
                                  WHERE student_id = ? AND course_id = ?");
            $stmt->bind_param('sii', $new_status, $student_id, $course_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Status updated successfully";
            header("Location: read_student_courses.php");
                exit();
            }
        }
        
        // Reassign Course Form
        if (isset($_POST['reassign_course']) && !$has_active_courses) {
            $new_course_id = sanitizeInput($_POST['new_course_id']);
            
            $conn->begin_transaction();
            
            // Remove old assignment
            $delete_stmt = $conn->prepare("DELETE FROM student_courses 
                                         WHERE student_id = ? AND course_id = ?");
            $delete_stmt->bind_param('ii', $student_id, $course_id);
            $delete_stmt->execute();
            
            // Add new assignment
            $insert_stmt = $conn->prepare("INSERT INTO student_courses 
                                         (student_id, course_id, status) 
                                         VALUES (?, ?, 'start')");
            $insert_stmt->bind_param('ii', $student_id, $new_course_id);
            $insert_stmt->execute();
            
            $conn->commit();
            
            $_SESSION['success'] = "Course reassigned successfully";
            header("Location: read_student_courses.php");
            exit();
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Update error: " . $e->getMessage());
        $_SESSION['error'] = "Error processing request";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student Course</title>
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; margin-top: 30px; }
        .card { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .nav-tabs .nav-link.active { background-color: #4da8da; color: white; }
        .disabled-form { opacity: 0.6; pointer-events: none; }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <!-- Centered Title -->
        <div class="row justify-content-center mb-4">
            <div class="col-auto">
                <h2 class="text-primary text-center display-7 fw-bold">Update Course Assignment</h2>
            </div>
        </div>
        <div class="card p-4">
            <div class="header-container mb-4">
                <a href="read_student_courses.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>

            <?php displayMessages(); ?>

            <h5 class="text-primary display-7">Student: <?= htmlspecialchars($current_assignment['student_name']) ?></h5>
            <h5 class="text-primary display-7">Current Course: <?= htmlspecialchars($current_assignment['course_name']) ?></h5>

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="status-tab" data-bs-toggle="tab" data-bs-target="#status" type="button" role="tab" aria-controls="status" aria-selected="true">Update Status</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reassign-tab" data-bs-toggle="tab" data-bs-target="#reassign" type="button" role="tab" aria-controls="reassign" aria-selected="false">Reassign Course</button>
                </li>
            </ul>

            <!-- Tabs Content -->
            <div class="tab-content mt-3" id="myTabContent">
                <!-- Status Update Tab -->
                <div class="tab-pane fade show active" id="status" role="tabpanel">
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Current Status</label>
                            <select name="status" class="form-select" required>
                                <option value="start" <?= $current_assignment['status'] === 'start' ? 'selected' : '' ?>>Start</option>
                                <option value="in-progress" <?= $current_assignment['status'] === 'in-progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="ended" <?= $current_assignment['status'] === 'ended' ? 'selected' : '' ?>>Ended</option>
                            </select>
                        </div>

                        <button type="submit" name="update_status" class="btn btn-primary">
                            Update Status
                        </button>
                    </form>
                </div>

                <!-- Reassign Course Tab -->
                <div class="tab-pane fade" id="reassign" role="tabpanel">
                    <div class="mt-3 <?= $has_active_courses ? 'disabled-form' : '' ?>">
                        <?php if ($has_active_courses): ?>
                            <div class="alert alert-warning">
                                Cannot reassign - student has active courses
                            </div>
                        <?php endif; ?>

                        <form method="POST" <?= $has_active_courses ? 'onsubmit="return false;"' : '' ?>>
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Select New Course</label>
                                <select name="new_course_id" class="form-select" required 
                                    <?= $has_active_courses ? 'disabled' : '' ?>>
                                    <?php foreach ($available_courses as $course): ?>
                                        <option value="<?= $course['course_id'] ?>">
                                            <?= htmlspecialchars($course['course_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" name="reassign_course" class="btn btn-primary"
                                <?= $has_active_courses ? 'disabled' : '' ?>>
                                Reassign Course
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS (Required for Tabs) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</body>
</html>