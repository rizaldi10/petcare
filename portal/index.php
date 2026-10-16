<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/models/Customer.php';
require_once dirname(__DIR__) . '/models/Hewan.php';
require_once dirname(__DIR__) . '/models/Inap.php';

$database = new Database();
$db = $database->getConnection();
$customerModel = new Customer($db);
$hewanModel = new Hewan($db);
$inapModel = new Inap($db);

$error_message = '';
$success_message = '';
$resetToken = $_GET['reset'] ?? '';

// Upgrade database portal yang sudah terpasang sebelum fitur reset password.
try {
    $db->exec("ALTER TABLE customer ADD COLUMN email VARCHAR(150) NULL");
} catch (PDOException $e) {
    if (strpos(strtolower($e->getMessage()), 'duplicate column') === false) throw $e;
}
$db->exec("CREATE TABLE IF NOT EXISTS customer_password_resets (
    id_reset INT AUTO_INCREMENT PRIMARY KEY,
    id_customer INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_customer) REFERENCES customer(id_customer) ON DELETE CASCADE,
    INDEX idx_customer_reset_expiry (expires_at)
) ENGINE=InnoDB");

// Handle Portal Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['customer_id']);
    unset($_SESSION['customer_nama']);
    unset($_SESSION['customer_telepon']);
    header('Location: index.php');
    exit();
}

// Handle Login / Register
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth_action = $_POST['auth_action'] ?? 'login';

    if ($auth_action === 'login') {
        $telepon = sanitizeInput($_POST['telepon']);
        $password = $_POST['password'];

        $user = $customerModel->loginPortal($telepon, $password);
        if ($user) {
            $_SESSION['customer_id'] = $user['id_customer'];
            $_SESSION['customer_nama'] = $user['nama_customer'];
            $_SESSION['customer_telepon'] = $user['telepon'];
            header('Location: index.php');
            exit();
        } else {
            $error_message = 'Nomor telepon atau password salah!';
        }
    } elseif ($auth_action === 'register') {
        $nama = sanitizeInput($_POST['nama_customer']);
        $telepon = sanitizeInput($_POST['telepon']);
        $password = $_POST['password'];
        $alamat = sanitizeInput($_POST['alamat'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Masukkan alamat email yang valid untuk akun Anda.';
        } else {

        $newId = $customerModel->create([
            'nama_customer' => $nama,
            'telepon' => $telepon,
            'password' => $password,
            'alamat' => $alamat,
            'email' => $email
        ]);

        if ($newId) {
            $_SESSION['customer_id'] = $newId;
            $_SESSION['customer_nama'] = $nama;
            $_SESSION['customer_telepon'] = $telepon;
            header('Location: index.php');
            exit();
        } else {
            $error_message = 'Pendaftaran gagal. Pastikan data terisi lengkap.';
        }
        }
    } elseif ($auth_action === 'forgot_password') {
        $email = trim($_POST['email'] ?? '');
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $token = bin2hex(random_bytes(32));
            if ($customerModel->createPasswordReset($email, hash('sha256', $token))) {
                $link = rtrim(BASE_URL, '/') . '/portal/index.php?reset=' . urlencode($token);
                $subject = 'Reset password akun PetCare';
                $body = "Halo,\n\nGunakan tautan berikut untuk membuat password baru (berlaku 1 jam):\n$link\n\nJika Anda tidak meminta reset, abaikan email ini.";
                $headers = "From: " . (getenv('PETCARE_FROM_EMAIL') ?: 'noreply@petcare.local') . "\r\n" . "Content-Type: text/plain; charset=UTF-8";
                @mail($email, $subject, $body, $headers);
            }
        }
        $success_message = 'Jika email terdaftar, tautan reset password akan dikirim.';
    } elseif ($auth_action === 'reset_password') {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        if (strlen($password) < 8) {
            $error_message = 'Password baru minimal 8 karakter.';
        } elseif ($customerModel->resetPassword(hash('sha256', $token), $password)) {
            $resetToken = '';
            $success_message = 'Password berhasil diubah. Silakan masuk dengan password baru.';
        } else {
            $error_message = 'Tautan reset tidak valid atau sudah kedaluwarsa.';
        }
    }
}

$is_logged_in = isCustomerLoggedIn();
$customer_id = $_SESSION['customer_id'] ?? null;
$my_pets = [];
$active_stays = [];

if ($is_logged_in) {
    $my_pets = $customerModel->getPets($customer_id);
    $active_stays = $inapModel->getCustomerBookings($customer_id);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Mandiri Pelanggan - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/portal.css">
</head>
<body class="portal-body">
    <!-- Navbar Portal -->
    <nav class="portal-nav">
        <a href="index.php" class="portal-logo">
            <span>🐾</span> <?php echo APP_NAME; ?>
        </a>
        <div class="portal-menu">
            <?php if ($is_logged_in): ?>
                <a href="index.php" class="portal-link active">Beranda</a>
                <a href="my_pets.php" class="portal-link">Anabul Saya</a>
                <a href="booking.php" class="portal-link">Booking Online</a>
                <a href="riwayat.php" class="portal-link">Riwayat Struk</a>
                <a href="index.php?action=logout" class="portal-link" style="color: #ef4444;">Keluar</a>
            <?php else: ?>
                <a href="../login.php" class="portal-link" style="color: #64748b;">Masuk Staf (POS) &rarr;</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="portal-container">
        <?php if (!$is_logged_in): ?>
            <!-- Form Login / Registrasi Pelanggan -->
            <div style="max-width: 480px; margin: 40px auto;" class="portal-card">
                <div style="text-align: center; margin-bottom: 25px;">
                    <div style="font-size: 3rem; margin-bottom: 10px;">🐶🐱</div>
                    <h2 style="margin: 0; color: #0284c7;">Portal Mandiri Pelanggan</h2>
                    <p style="color: #64748b; font-size: 0.95rem; margin-top: 5px;">
                        Masuk untuk mendaftarkan profil anabul dan reservasi kamar hotel.
                    </p>
                </div>

                <?php if ($error_message): ?>
                    <div style="background: #fee2e2; color: #b91c1c; padding: 10px 14px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem;">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div style="background: #dcfce7; color: #166534; padding: 10px 14px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem;"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <?php if ($resetToken !== ''): ?>
                    <h3>Buat Password Baru</h3>
                    <form method="POST">
                        <input type="hidden" name="auth_action" value="reset_password">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($resetToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <label>Password baru (minimal 8 karakter)</label>
                        <input type="password" name="password" minlength="8" required style="width:100%;box-sizing:border-box;padding:10px;margin:10px 0 16px">
                        <button type="submit" class="portal-btn" style="width:100%;padding:12px">Simpan Password Baru</button>
                    </form>
                <?php else: ?>

                <!-- Tab Login / Daftar -->
                <div style="display: flex; border-bottom: 2px solid #e2e8f0; margin-bottom: 20px;">
                    <button type="button" id="tabLoginBtn" onclick="switchAuthTab('login')" style="flex: 1; padding: 10px; background: none; border: none; font-weight: bold; border-bottom: 2px solid #0284c7; color: #0284c7; cursor: pointer;">
                        Masuk Akun
                    </button>
                    <button type="button" id="tabRegisterBtn" onclick="switchAuthTab('register')" style="flex: 1; padding: 10px; background: none; border: none; font-weight: bold; color: #64748b; cursor: pointer;">
                        Daftar Baru
                    </button>
                </div>

                <!-- Form Login -->
                <form method="POST" id="formLogin">
                    <input type="hidden" name="auth_action" value="login">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Nomor Telepon / WhatsApp</label>
                        <input type="text" name="telepon" required class="portal-btn-secondary" style="width: 100%; box-sizing: border-box; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; background: white;" placeholder="Contoh: 081234567890" value="081234567890">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Password</label>
                        <input type="password" name="password" required class="portal-btn-secondary" style="width: 100%; box-sizing: border-box; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; background: white;" placeholder="Password Anda" value="password">
                    </div>
                    <button type="submit" class="portal-btn" style="width: 100%; padding: 12px; font-size: 1rem;">
                        Masuk ke Akun Saya
                    </button>

                    <div style="margin-top: 15px; font-size: 0.85rem; color: #64748b; text-align: center;">
                        Demo: Telp <code>081234567890</code> / Password: <code>password</code>
                    </div>
                    <button type="button" onclick="document.getElementById('forgotForm').style.display='block'" style="display:block;margin:12px auto 0;background:none;border:0;color:#0284c7;cursor:pointer">Lupa password?</button>
                </form>
                <form method="POST" id="forgotForm" style="display:none;margin-top:16px">
                    <input type="hidden" name="auth_action" value="forgot_password">
                    <label>Email yang terdaftar</label>
                    <input type="email" name="email" required style="width:100%;box-sizing:border-box;padding:10px;margin:8px 0 12px">
                    <button type="submit" class="portal-btn" style="width:100%;padding:10px">Kirim Tautan Reset</button>
                </form>

                <!-- Form Register -->
                <form method="POST" id="formRegister" style="display: none;">
                    <input type="hidden" name="auth_action" value="register">
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Nama Lengkap Anda</label>
                        <input type="text" name="nama_customer" required style="width: 100%; box-sizing: border-box; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1;" placeholder="Nama pemilik">
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Nomor Telepon / WhatsApp</label>
                        <input type="text" name="telepon" required style="width: 100%; box-sizing: border-box; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1;" placeholder="08xxxxxxxxxx">
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Email (untuk pemulihan akun)</label>
                        <input type="email" name="email" required style="width: 100%; box-sizing: border-box; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1;" placeholder="nama@email.com">
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Password</label>
                        <input type="password" name="password" required style="width: 100%; box-sizing: border-box; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1;" placeholder="Minimal 6 karakter">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Alamat Domisili</label>
                        <textarea name="alamat" rows="2" style="width: 100%; box-sizing: border-box; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1;" placeholder="Alamat rumah..."></textarea>
                    </div>
                    <button type="submit" class="portal-btn" style="width: 100%; padding: 12px; font-size: 1rem; background: #10b981;">
                        Daftar Akun Baru
                    </button>
                </form>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Dashboard Pelanggan Setelah Login -->
            <div class="portal-card" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-color: #bae6fd;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h2 style="margin: 0 0 5px 0; color: #0369a1;">
                            Halo, <?php echo htmlspecialchars($_SESSION['customer_nama']); ?>! 🐾
                        </h2>
                        <p style="margin: 0; color: #0284c7; font-size: 0.95rem;">
                            Selamat datang di Portal Layanan Mandiri PetCare. Pantau kondisi anabul kesayangan secara real-time.
                        </p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="booking.php" class="portal-btn">+ Booking Hotel</a>
                        <a href="my_pets.php" class="portal-btn portal-btn-secondary">Kelola Anabul</a>
                    </div>
                </div>
            </div>

            <!-- Grid Ringkasan Anabul -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; color: #0f172a;">Profil Anabul Saya (<?php echo count($my_pets); ?> Ekor)</h3>
                <a href="my_pets.php" style="color: #0284c7; font-weight: 600; text-decoration: none;">+ Daftarkan Anabul Baru</a>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <?php foreach ($my_pets as $pet): ?>
                <div class="portal-card" style="margin-bottom: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <h4 style="margin: 0; font-size: 1.15rem; color: #0f172a;">
                                <?php echo ($pet['spesies'] === 'Kucing') ? '🐱' : '🐶'; ?>
                                <?php echo htmlspecialchars($pet['nama_hewan']); ?>
                            </h4>
                            <small style="color: #64748b;">
                                <?php echo htmlspecialchars($pet['spesies']); ?> • <?php echo htmlspecialchars($pet['ras'] ?? 'Mix'); ?>
                            </small>
                        </div>
                        <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: bold;">
                            <?php echo $pet['berat_badan'] ? $pet['berat_badan'].' kg' : 'Berat -'; ?>
                        </span>
                    </div>

                    <?php if (!empty($pet['catatan_alergi'])): ?>
                    <div style="background: #fef2f2; color: #991b1b; padding: 6px 10px; border-radius: 6px; font-size: 0.8rem; margin-top: 10px; font-weight: 600;">
                        ⚠️ Alergi: <?php echo htmlspecialchars($pet['catatan_alergi']); ?>
                    </div>
                    <?php endif; ?>

                    <div style="margin-top: 15px; border-top: 1px solid #f1f5f9; padding-top: 10px; display: flex; justify-content: space-between;">
                        <a href="booking.php?pet_id=<?php echo $pet['id_hewan']; ?>" style="font-size: 0.85rem; color: #0284c7; font-weight: 600; text-decoration: none;">
                            🏨 Reservasi Inap &rarr;
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($my_pets)): ?>
                <div style="grid-column: 1 / -1; background: white; padding: 30px; text-align: center; border-radius: 12px; border: 1px dashed #cbd5e1;">
                    <p style="color: #64748b; margin-bottom: 15px;">Anda belum mendaftarkan hewan peliharaan.</p>
                    <a href="my_pets.php" class="portal-btn">+ Daftarkan Anabul Pertama</a>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function switchAuthTab(type) {
            const loginForm = document.getElementById('formLogin');
            const regForm = document.getElementById('formRegister');
            const tabLogin = document.getElementById('tabLoginBtn');
            const tabReg = document.getElementById('tabRegisterBtn');

            if (type === 'login') {
                loginForm.style.display = 'block';
                regForm.style.display = 'none';
                tabLogin.style.borderBottom = '2px solid #0284c7';
                tabLogin.style.color = '#0284c7';
                tabReg.style.borderBottom = 'none';
                tabReg.style.color = '#64748b';
            } else {
                loginForm.style.display = 'none';
                regForm.style.display = 'block';
                tabReg.style.borderBottom = '2px solid #10b981';
                tabReg.style.color = '#10b981';
                tabLogin.style.borderBottom = 'none';
                tabLogin.style.color = '#64748b';
            }
        }
    </script>
</body>
</html>
