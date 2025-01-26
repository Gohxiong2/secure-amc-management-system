<?php
session_start();
$previous_page = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php'; // Fallback to 'dashboard.php' if HTTP_REFERER is not set.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSRF Error</title>
</head>
<body>
    <h1>Request Denied</h1>
    <p>Your request could not be processed due to a security issue.</p>
    <p>This may have occurred because your session expired or the form was submitted incorrectly.</p>
    
    <!-- Logout and redirect to login -->
    <p><a href="logout.php?redirect=login">Return to Login</a></p>
    
    <!-- Go back to the previous page -->
    <p><a href="<?php echo htmlspecialchars($previous_page, ENT_QUOTES, 'UTF-8'); ?>">Go Back</a></p>
</body>
</html>
