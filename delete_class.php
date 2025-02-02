<?php
require_once 'db_connect.php';
require_once 'security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token'])
    $class_id = $_POST['class_id'];

    // Prepares and binds class_id
    $stmt = $conn->prepare("DELETE FROM classes WHERE class_id=?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();

    if ($stmt->execute()) {
        $stmt->close();
        $_SESSION['success'] = "Class deleted successfully";
        header("Location: read_class.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "No class ID provided.";
}
?>