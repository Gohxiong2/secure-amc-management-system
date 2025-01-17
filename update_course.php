<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Course</title>
</head>
<body>
    <h1>Update Course</h1>

    <?php
    require 'db_connect.php';
    $conn = db_connect();

    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $sql = "SELECT * FROM courses WHERE course_id=$id";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
        ?>

        <form method="POST" action="">
            <label for="name">Course Name:</label>
            <input type="text" id="name" name="name" value="<?php echo $row['course_name']; ?>" required><br>
            <label for="code">Course Code:</label>
            <input type="text" id="code" name="code" value="<?php echo $row['course_code']; ?>" required><br>
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $row['start_date']; ?>" required><br>
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $row['end_date']; ?>" required><br>
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <button type="submit" name="update">Update Course</button>
        </form>

        <?php
        } else {
            echo "<p>Course not found.</p>";
        }
    }

    if (isset($_POST['update'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $code = $_POST['code'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        $sql = "UPDATE courses SET course_name='$name', course_code='$code', start_date='$start_date', end_date='$end_date' WHERE course_id=$id";

        if ($conn->query($sql) === TRUE) {
            echo "<p>Course updated successfully!</p>";
            header("Location: read.php");
            exit();
        } else {
            echo "<p>Error: " . $conn->error . "</p>";
        }
    }
    ?>

</body>
</html>
