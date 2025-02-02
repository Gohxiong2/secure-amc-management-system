<?php
session_start();

if (!isset($_SESSION['reset_token']) || !isset($_SESSION['reset_user_id'])) {
    echo "Invalid password reset request!";
    exit();
}

// dynamic reset link
$reset_link = "reset_password.php?token=" . $_SESSION['reset_token'];
?>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Classes</title>
</head>
<body>
    <h1>Click <a href="<?= htmlspecialchars($reset_link) ?>">here</a> to reset your password.</h1>
</body>
</html>