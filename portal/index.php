<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/models/Customer.php';
require_once dirname(__DIR__) . '/models/Hewan.php';
require_once dirname(__DIR__) . '/models/Grooming.php';
require_once dirname(__DIR__) . '/models/Inap.php';

$database = new Database();
$db = $database->getConnection();
$customerModel = new Customer($db);
$hewanModel = new Hewan($db);
$groomingModel = new Grooming($db);
$inapModel = new Inap($db);

$error_message = '';
$success_message = '';

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

        $newId = $customerModel->create([
            'nama_customer' => $nama,
            'telepon' => $telepon,
            'password' => $password,
            'alamat' => $alamat
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
}

$is_logged_in = isCustomerLoggedIn();
$customer_id = $_SESSION['customer_id'] ?? null;
$my_pets = [];
$active_groomings = [];
$active_stays = [];

if ($is_logged_in) {
    $my_pets = $customerModel->getPets($customer_id);
    $active_groomings = $groomingModel->getTrackByCustomer($customer_id);
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
                <a href="track.php" class="portal-link">Pantau Perawatan (Live)</a>
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
                        Masuk untuk mendaftarkan profil anabul, reservasi kamar hotel, & pelacakan live grooming.
                    </p>
                </div>

                <?php if ($error_message): ?>
                    <div style="background: #fee2e2; color: #b91c1c; padding: 10px 14px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem;">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

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
                        <a href="booking.php" class="portal-btn">+ Booking Hotel / Grooming</a>
                        <a href="my_pets.php" class="portal-btn portal-btn-secondary">Kelola Anabul</a>
                    </div>
                </div>
            </div>

            <!-- Status Perawatan Aktif Saat Ini (Live Alert) -->
            <?php if (!empty($active_groomings)): ?>
                <?php 
                $latestGrooming = $active_groomings[0];
                if ($latestGrooming['status_pengerjaan'] !== 'Selesai'):
                ?>
                <div class="portal-card" style="border-left: 6px solid #0284c7;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <span style="background: #0284c7; color: white; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">
                                🔴 Live Status Grooming
                            </span>
                            <h3 style="margin: 8px 0 4px 0; color: #0f172a;">
                                Anabul <strong><?php echo htmlspecialchars($latestGrooming['nama_hewan']); ?></strong> sedang dalam tahap: 
                                <span style="color: #0284c7;"><?php echo str_replace('_', ' ', $latestGrooming['status_pengerjaan']); ?></span>
                            </h3>
                            <div style="font-size: 0.9rem; color: #64748b;">
                                Groomer: ✂️ <?php echo htmlspecialchars($latestGrooming['nama_groomer']); ?> • Layanan: <?php echo htmlspecialchars($latestGrooming['nama_layanan']); ?>
                            </div>
                        </div>
                        <a href="track.php" class="portal-btn" style="background: #0284c7;">
                            Lihat Timeline Live &rarr;
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>

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
                        <a href="track.php?pet_id=<?php echo $pet['id_hewan']; ?>" style="font-size: 0.85rem; color: #10b981; font-weight: 600; text-decoration: none;">
                            ✂️ Riwayat Grooming
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
