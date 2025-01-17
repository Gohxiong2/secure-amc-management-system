<?php
require 'db_connect.php';
$conn = db_connect();

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "DELETE FROM courses WHERE course_id=$id";

    if ($conn->query($sql) === TRUE) {
        header("Location: read_course.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "No course ID provided.";
}
?>
