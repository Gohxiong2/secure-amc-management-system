<?php
session_start();

// Determine the previous page or fallback to a dashboard
$previous_page = $_SERVER['HTTP_REFERER'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
</head>
<body>
    <h1>Access Denied</h1>
    <p>You do not have permission to access this page.</p>

    <!-- Logout and redirect to login -->
    <p><a href="logout.php?redirect=login">Return to Login</a></p>
    
    <!-- Go back to the previous page -->
    <p><a href="<?php echo htmlspecialchars($previous_page, ENT_QUOTES, 'UTF-8'); ?>">Go Back</a></p>
</body>
</html>
