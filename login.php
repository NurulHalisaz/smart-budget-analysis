<?php
// login.php

// Require database configuration and authentication logic
require_once 'config/database.php';
require_once 'includes/auth.php';

// If user is already logged in, redirect them to the dashboard
redirect_if_logged_in();

$error_message = '';

// Process the login form when it is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Basic validation
    if (empty($email) || empty($password)) {
        $error_message = 'Email dan password wajib diisi.';
    } else {
        try {
            // Find the user by email
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $user = $stmt->fetch();

            // Verify password using PHP's native password_verify function
            if ($user && password_verify($password, $user['password'])) {
                // Password is correct, set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];

                // Handle "Remember Me" functionality
                if ($remember) {
                    // Generate a secure random token
                    $token = bin2hex(random_bytes(32));
                    
                    // Store the token in the database for this user
                    $update_stmt = $conn->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
                    $update_stmt->bindParam(':token', $token);
                    $update_stmt->bindParam(':id', $user['id']);
                    $update_stmt->execute();

                    // Set a cookie that expires in 30 days
                    // (name, value, expire, path, domain, secure, httponly)
                    setcookie('remember_token', $token, time() + (86400 * 30), "/", "", false, true);
                }

                // Redirect to the main dashboard after successful login
                header("Location: index.php");
                exit();
            } else {
                $error_message = 'Email atau password salah.';
            }
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Budget Analysis</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f8fafc;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        .login-header {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
            background-color: white;
        }
    </style>
</head>
<body>

    <div class="container d-flex justify-content-center">
        <div class="login-card">
            <div class="login-header">
                <div class="mb-3">
                    <i class="bi bi-wallet2 fs-1"></i>
                </div>
                <h3 class="fw-bold mb-0">Smart Budget</h3>
                <p class="text-white-50 mb-0 mt-1">Masuk untuk mengelola keuangan Anda</p>
            </div>
            <div class="login-body">
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                        <div>
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-medium text-secondary">Alamat Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                            <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required autofocus placeholder="nama@email.com">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label fw-medium text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                            <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" required placeholder="Masukkan password Anda">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" <?= isset($_POST['remember']) ? 'checked' : '' ?>>
                            <label class="form-check-label text-secondary" for="remember">Ingat Saya</label>
                        </div>
                        <a href="#" class="text-decoration-none small text-primary">Lupa password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3">
                        Masuk <i class="bi bi-box-arrow-in-right ms-1"></i>
                    </button>
                    
                    <div class="text-center">
                        <span class="text-secondary small">Belum punya akun?</span>
                        <a href="setup_user.php" class="text-decoration-none small fw-medium text-primary ms-1">Buat Admin Default</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
