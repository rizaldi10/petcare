<?php
require_once 'config/config.php';

// Jika sudah login staf, redirect ke dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $database = new Database();
        $db = $database->getConnection();
        
        $user = new User($db);
        
        if ($user->login($username, $password)) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $_SESSION['nama_lengkap'] = $user->nama;
            $_SESSION['user_role'] = $user->role;
            
            // Redirect sesuai role
            if ($user->role === 'groomer') {
                header('Location: antrean_grooming.php');
            } else {
                header('Location: dashboard.php');
            }
            exit();
        } else {
            $error_message = 'Username atau password salah!';
        }
    } else {
        $error_message = 'Username dan password harus diisi!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Staf - <?php echo APP_NAME; ?></title>
    <?php require_once 'head_inc.php'; ?>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div style="width: 54px; height: 54px; border-radius: 14px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; display: inline-flex; align-items: center; justify-content: center; font-size: 26px; box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35); margin-bottom: 12px;">
                    <i class="bi bi-heart-pulse-fill"></i>
                </div>
                <h1><?php echo APP_NAME; ?></h1>
                <p>Masuk ke portal operasional staf internal</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username Staf</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : 'admin'; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required value="password">
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk ke Sistem
                </button>
            </form>

            <div class="login-footer">
                <p><strong>Akun Demo Staf Internal (Password: <code>password</code>):</strong></p>
                <p>• Admin: <code>admin</code></p>
                <p>• Kasir: <code>kasir1</code></p>
                <p>• Groomer: <code>groomer1</code></p>

                <div style="margin-top: 15px; padding-top: 12px; border-top: 1px dashed #e2e8f0;">
                    <a href="portal/index.php" style="color: #0284c7; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="bi bi-globe2"></i> Buka Portal Mandiri Pelanggan &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
