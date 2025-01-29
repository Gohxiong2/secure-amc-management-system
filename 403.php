<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .access-denied-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 15px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .access-denied-title {
            color: #dc3545; /* Bootstrap's danger color */
            font-size: 2rem;
            font-weight: bold;
        }
        .access-denied-text {
            margin: 20px 0;
            font-size: 1.2rem;
        }
        .btn-custom {
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="access-denied-container">
            <h1 class="access-denied-title">Access Denied</h1>
            <p class="access-denied-text">You do not have permission to access this page.</p>

            <!-- Logout and redirect to login -->
            <a href="logout.php" class="btn btn-danger btn-custom">Return to Login</a>
            
            <!-- Go to dashboard -->
            <a href="dashboard.php" class="btn btn-primary btn-custom">Go To Dashboard</a>
        </div>
    </div>

    <!-- Bootstrap JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
