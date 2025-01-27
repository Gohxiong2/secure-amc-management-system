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
                <label for='duration'>
                    Semester/Term:
                </label>
                <select type='text' name='duration'>
                    <option></option>
                    <option value='SEMESTER'>Semester</option>
                    <option value='TERM'>Term</option>
                </select>
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
                $duration = htmlspecialchars(trim($_POST['duration']), ENT_QUOTES);
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
                if (empty($duration)){
                    echo "Semester/term required!<br>";
                    $errors++;
                }
                else{
                    if ((!preg_match('/^[a-zA-Z0-9\s.]+$/', $duration))) {
                        echo "Invalid semester/term!<br>";
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
                    $stmt = $conn->prepare("insert into classes(class_id, class_name, duration, start_date, end_date) values
                    (NULL,?,?,?,?)");
                    $stmt->bind_param('ssss', $name, $duration, $start_date, $end_date );//bind the parameters
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