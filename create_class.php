<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Create Class</title>
    </head>
    <body>
        <form method='POST' action=''>
            <div>
                <label for='name'>
                    Class Name:
                </label>
                <input type='text' id='name' name='name' required>
            </div>
            <div>
                <label for='semester'>
                    Semester:
                </label>
                <input type='text' id='semester' name='semester' required>
            </div>
            <div>
                <label for='start_date'>
                    Start date:
                </label>
                <input type='date' id='start_date' name='start_date' required>
            </div>
            <div>
                <label for='end_date'>
                    End Date:
                </label>
                <input type='date' id='end_date' name='end_date' required>
            </div>
            <button type='submit' name='create'></button>
        </form>
        <?php
            require 'db-connect.php';
            $conn = db_connect();
            if (isset($_POST['create'])) {
                $name = $_POST['name'];
                $semester = $_POST['semester'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];

                $sql = "INSERT INTO classes (class_name, semester, start_date, end_date) VALUES ('$name', '$course_id', '$semester', '$start_date', '$end_date')";

                if ($conn->query($sql) === TRUE) {
                    echo "Class created successfully!";
                } 
                else {
                    echo "Error: " . $conn->error;
                }
            }
        ?>
    </body>
</html>