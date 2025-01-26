<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>View Class</title>
    </head>
    <body>
        <h1>All Classes</h1>
        <table>
            <thead>
                <tr>
                    <th>Class ID</th>
                    <th>Class Name</th>
                    <th>Semester/Term</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                </tr> 
            </thead>   
            <tbody>
                <?php
                    require_once 'db_connect.php';

                    $sql = "SELECT * FROM classes";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>{$row['class_id']}</td>
                                <td>{$row['class_name']}</td>
                                <td>{$row['semesterorterm']}</td>
                                <td>{$row['start_date']}</td>
                                <td>{$row['end_date']}</td>
                                <td>
                                    <a href='update_class.php?id={$row['class_id']}' class='btn btn-warning btn-sm'>Update</a>
                                    <a href='delete_class.php?id={$row['class_id']}' class='btn btn-danger btn-sm' onclick='return confirm('Are you sure you want to delete this class?')'>Delete</a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td>No classes found.</td></tr>";
                    }
                ?>
            </tbody>
        </table>
    </body>
</html>