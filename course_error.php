<?php
session_start();// Start the session
require_once 'security_course.php'; 

enforceSessionTimeout(600);// Log out users after 10 minutes of inactivity
verifyAuthentication(); // Ensure the user is logged in.

// Get the previous page URL or default to 'dashboard.php' if unavailable.
$previous_page = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Title -->
    <title>Error Occurred</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .error-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 15px;    
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .error-title {
            color: #dc3545; 
            font-size: 2rem;
            font-weight: bold;
        }
        .error-text {
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
        <div class="error-container">
            <!-- Error title for this page-->
            <h1 class="error-title">Something Went Wrong</h1>
            <p class="error-text">We're sorry, but we couldn't process your request. Please try again later.</p>

            <!-- Logout and redirect to login -->
            <a href="logout.php?redirect=login" class="btn btn-danger btn-custom">Go to Login</a>

            <!-- Button to redirect user to previous page if theres any if not redirect to dashboard.php page -->
            <a href="<?php echo htmlspecialchars($previous_page, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary btn-custom">Go Back</a>

        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
