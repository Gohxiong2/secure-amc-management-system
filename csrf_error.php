<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Error</title>
    <!-- Bootstrap CSS -->
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light text-dark">
    <div class="container text-center mt-5">
        <div class="alert alert-danger" role="alert">
            <h1 class="display-4">Request Denied</h1>
            <p>Your session expired or the form was submitted incorrectly.</p>
        </div>

        <!-- Logout and redirect to login -->
        <p>
            <a href="logout.php?redirect=login" class="btn btn-outline-primary btn-lg">Return to Login</a>
        </p>

        <!-- Go to dashboard -->
        <a href="dashboard.php" class="btn btn-primary btn-lg">Go To Dashboard</a>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
