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
                $stmt = "SELECT * FROM classes WHERE class_id=$id";
                $result = $conn->query($stmt);

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                }
                else {
                    echo "<p>Class not found.</p>";
                }
            }
        ?>

        <form method="POST" action="">
            <label for="name">Class Name:</label>
            <input type="text" id="name" name="name" value="<?php echo $row['class_name']; ?>" required><br>
            
            <label for="duration">Semester/Term:</label>
            <select id="duration" name="duration" required>
                <option value="SEMESTER" <?php echo ($row['duration'] === 'semester') ? 'selected' : ''; ?>>Semester</option>
                <option value="TERM" <?php echo ($row['duration'] === 'term') ? 'selected' : ''; ?>>Term</option>
            </select><br>
            
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $row['start_date']; ?>" required><br>
            
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $row['end_date']; ?>" required><br>
            
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <button type="submit" name="update">Update Class</button>
        </form>
        
        <?php
            if (isset($_POST['update'])) {
                $id = $_POST['id'];
                $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES);
                $duration = htmlspecialchars(trim($_POST['duration']), ENT_QUOTES);
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                
                $errors = 0;

                if ($errors === 0){
                    $stmt = $conn->prepare("UPDATE classes SET class_name=?, duration=?, start_date=?, end_date=? WHERE class_id=?");
                    if ($stmt){
                        $stmt->bind_param('ssssi', $name, $duration, $start_date, $end_date, $id);
                        if ($stmt->execute()) {
                            echo "<p>Class updated successfully!</p>";
                            header("Location: view_class.php");
                            exit();
                        } else {
                            echo "<p>Error: " . $stmt->error . "</p>";
                        }
                    }
                }
            }
        ?>
    </body>
</html>