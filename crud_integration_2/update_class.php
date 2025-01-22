<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Update Class</title>
    </head>
    <body>
        <h1>Update Class</h1>
        <?php
        require 'db-connect.php';
        $conn = db_connect();

        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $sql = "SELECT * FROM classes WHERE class_id=$id";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
        ?>

        <form method="POST" action="">
            <label for="name">Class Name:</label>
            <input type="text" id="name" name="name" value="<?php echo $row['class_name']; ?>" required><br>
            <label for="course_id">Course ID:</label>
            <input type="text" id="course_id" name="course_id" value="<?php echo $row['course_id']; ?>" required><br>
            <label for="semester">Semester:</label>
            <input type="text" id="semester" name="semester" value="<?php echo $row['semester']; ?>" required><br>
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $row['start_date']; ?>" required><br>
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $row['end_date']; ?>" required><br>
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <button type="submit" name="update">Update Class</button>
        </form>

                <?php
                } else {
                    echo "<p>Course not found.</p>";
                }
            }

            if (isset($_POST['update'])) {
                $id = $_POST['id'];
                $name = $_POST['name'];
                $course_id = $_POST['course_id'];
                $semester = $_POST['semester'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];

                $sql = "UPDATE courses SET class_name='$name', course_id='$course_id', semester='$semester', start_date='$start_date', end_date='$end_date' WHERE class_id=$id";

                if ($conn->query($sql) === TRUE) {
                    echo "<p>Class updated successfully!</p>";
                    header("Location: view_class.php");
                    exit();
                } else {
                    echo "<p>Error: " . $conn->error . "</p>";
                }
            }
        ?>
    </body>
</html>