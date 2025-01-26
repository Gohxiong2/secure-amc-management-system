<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Update Class</title>
    </head>
    <body>
        <h1>Update Class</h1>
        <?php
        require_once 'db_connect.php';

        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM classes WHERE class_id=$id";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
        ?>

        <form method="POST" action="">
            <label for="name">Class Name:</label>
            <input type="text" id="name" name="name" value="<?php echo $row['class_name']; ?>" required><br>
            
            <label for="semesterorterm">Semester/Term:</label>
            <select id="semesterorterm" name="semesterorterm" required>
                <option value="semester" <?php echo ($row['semester'] === 'semester') ? 'selected' : ''; ?>>Semester</option>
                <option value="term" <?php echo ($row['semester'] === 'term') ? 'selected' : ''; ?>>Term</option>
            </select><br>
            
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $row['start_date']; ?>" required><br>
            
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $row['end_date']; ?>" required><br>
            
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <button type="submit" name="update">Update Class</button>
        </form>

                <?php
                } else {
                    echo "<p>Class not found.</p>";
                }
            }

            if (isset($_POST['update'])) {
                $id = $_POST['id'];
                $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES);
                $semesterorterm = $_POST['semesterorterm'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];

                $sql = "UPDATE classes SET class_name='$name', semesterorterm='$semesterorterm', start_date='$start_date', end_date='$end_date' WHERE class_id=$id";

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