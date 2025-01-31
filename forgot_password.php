<?php
require_once "db_connect.php"
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

                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="text" class="form-control rounded-pill" id="email" name="email" required>
                            </div>
                            <div class="d-grid mb-3">
                                <a href="password_link.php">
                                    <button type="submit" class="btn btn-primary rounded-pill">Send Link</button>
                                </a>
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