<?php
require_once "db_connect.php";
require_once "security.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitizeInput($_POST['email']);
    if (empty($email)){
        $message = "Email cannot be empty!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $message = "Invalid email format!";
    } else {
        $stmt = $conn -> prepare("SELECT user_id FROM students WHERE email = ?");

        $stmt->bind_param('s', $email);
        $stmt->execute();

        $stmt->store_result();
        if ($stmt->num_rows == 0) {
            $message = "Email not found in the database!";
        } else {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            $reset_token = bin2hex(random_bytes(32));
            $_SESSION['reset_token'] = $reset_token;
            $_SESSION['reset_user_id'] = $user_id;
            $_SESSION['reset_expiry'] = time() + 900; // 15 minutes expiry
            header("Location: password_link.php");
            exit();
        }
    }
}
?>


<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
                        <h2 class="text-center mb-4 text-primary">Reset password</h2>
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="text" class="form-control rounded-pill" id="email" name="email" required>
                            </div>
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary rounded-pill">Send Link</button>
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