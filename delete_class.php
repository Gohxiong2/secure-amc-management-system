<?php
require_once 'db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "DELETE FROM classes WHERE class_id=$id";

    if ($conn->query($sql) === TRUE) {
        header("Location: view_class.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "No class ID provided.";
}
?>