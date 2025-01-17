<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Course</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h1 class="text-center mb-4">Create a New Course</h1>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="name" class="form-label">Course Name:</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="code" class="form-label">Course Code:</label>
                        <input type="text" id="code" name="code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date:</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date:</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" required>
                    </div>
                    <button type="submit" name="create" class="btn btn-primary w-100">Create Course</button>
                </form>

                <?php
                require 'db_connect.php';
                $conn = db_connect();
                if (isset($_POST['create'])) {
                    $name = $_POST['name'];
                    $code = $_POST['code'];
                    $start_date = $_POST['start_date'];
                    $end_date = $_POST['end_date'];

                    $sql = "INSERT INTO courses (course_name, course_code, start_date, end_date) VALUES ('$name', '$code', '$start_date', '$end_date')";

                    if ($conn->query($sql) === TRUE) {
                        echo "<div class='alert alert-success mt-3'>Course created successfully!</div>";
                    } else {
                        echo "<div class='alert alert-danger mt-3'>Error: " . $conn->error . "</div>";
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
