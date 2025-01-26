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
                <input type='text' name='name'>
            </div>
            <div>
                <label for='semester'>
                    Semester:
                </label>
                <input type='text' name='semester'>
            </div>
            <div>
                <label for='start_date'>
                    Start date:
                </label>
                <input type='date' name='start_date'>
            </div>
            <div>
                <label for='end_date'>
                    End Date:
                </label>
                <input type='date' name='end_date'>
            </div>
            <button type='submit' name='create'>Create</button>
        </form>
        <?php
            require_once 'db_connect.php';

            if (isset($_POST['create'])) {
                $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES);
                $semester = htmlspecialchars(trim($_POST['semester']), ENT_QUOTES);
                $start_date = trim($_POST['start_date']);
                $end_date = trim($_POST['end_date']);

                $errors = 0;

                if (empty($name)){
                    echo "Class name required!<br>";
                    $errors++;
                }
                else{
                    if ((!preg_match('/^[a-zA-Z0-9\s.]+$/', $name))) {
                        echo "Name should not have special characters!<br>";
                        $errors++;
                    }
                }
                if (empty($semester)){
                    echo "Semester required!<br>";
                    $errors++;
                }
                else{
                    if ((!preg_match('/^[a-zA-Z0-9\s.]+$/', $semester))){
                        echo "Semester should not have special characters!<br>";
                        $errors++;
                    }
                }
                if (empty($start_date)){
                    echo "Start date required!<br>";
                    $errors++;
                }
                if (empty($end_date)){
                    echo "End date required!<br>";
                    $errors++;
                }
                else{
                    if ($start_date >= $end_date){
                        echo "Start date should not be later than end date!<br>";
                        $errors++;
                    }
                }
                if ($errors === 0){
                    $stmt = $conn->prepare("insert into classes(class_id, class_name, semester, start_date, end_date) values
                    (NULL,?,?,?,?)");
                    $stmt->bind_param('ssss', $name, $semester, $start_date, $end_date );//bind the parameters
                    if ($stmt->execute()) {
                        echo "Class created successfully!";
                    } 
                    else {
                        echo "Error: " . $stmt->error;
                    }
                }
                else{
                    echo "You have $errors errors.";
                }
            }
        ?>
    </body>
</html>