<?php
session_start();
require_once "db_connect.php";
require_once "security.php";

$csrf_token = generateCsrfToken();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    validateCsrfToken($_POST['csrf_token']);
    $user_id = $_SESSION['reset_user_id'];
    $new_password= sanitizeInput($_POST['new_password']);
    $confirm_password = sanitizeInput($_POST['confirm_password']);

    if (!isset($_SESSION['reset_token']) || !isset($_SESSION['reset_user_id']) || time() > $_SESSION['reset_expiry']) {
        die("Invalid or expired token!");
    }

    if (empty($new_password) || empty($confirm_password)) {
        $message = "Password fields cannot be empty!";
        $message .= "USER ID: $user_id";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match!";
    } else {
        $hashed_password = password_hash($confirm_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET hashed_password = ? WHERE user_id = ?");
        $stmt->bind_param('si', $hashed_password, $user_id);
        $stmt->execute();
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit();
    }
}

// If accessed via link, check for valid session token
if (!isset($_SESSION['reset_token']) || !isset($_GET['token']) || $_SESSION['reset_token'] !== $_GET['token']) {
    die("Invalid or expired token!");
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-card {
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .btn-custom {
            background: #0d6efd;
            color: white;
            border-radius: 25px;
            padding: 10px 30px;
        }
    </style>
</head>
<body class="bg-primary">
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card login-card">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4 text-primary">Reset Password</h2>
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>

                        <form method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control rounded-pill" id="new_password" name="new_password">
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control rounded-pill" id="confirm_password" name="confirm_password">
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary rounded-pill">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>