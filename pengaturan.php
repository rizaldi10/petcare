<?php
require_once 'config/config.php';
requireRole(['admin']);

require_once 'models/Pengaturan.php';

$database = new Database();
$db = $database->getConnection();
$pengaturan = new Pengaturan($db);

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $settings = [
        'nama_toko' => sanitizeInput($_POST['nama_toko'] ?? APP_NAME),
        'alamat_toko' => sanitizeInput($_POST['alamat_toko'] ?? ''),
        'telepon_toko' => sanitizeInput($_POST['telepon_toko'] ?? ''),
        'email_toko' => sanitizeInput($_POST['email_toko'] ?? ''),
        'persentase_komisi_groomer' => sanitizeInput($_POST['persentase_komisi_groomer'] ?? '20'),
        'tarif_denda_overstay_per_jam' => sanitizeInput($_POST['tarif_denda_overstay_per_jam'] ?? '15000'),
        'footer_struk' => sanitizeInput($_POST['footer_struk'] ?? ''),
        'warna_primary' => sanitizeInput($_POST['warna_primary'] ?? '#0284c7'),
        'warna_secondary' => sanitizeInput($_POST['warna_secondary'] ?? '#0369a1'),
        'warna_sidebar' => sanitizeInput($_POST['warna_sidebar'] ?? '#0f172a'),
        'warna_sidebar_header' => sanitizeInput($_POST['warna_sidebar_header'] ?? '#1e293b'),
        'warna_success' => sanitizeInput($_POST['warna_success'] ?? '#10b981'),
        'warna_danger' => sanitizeInput($_POST['warna_danger'] ?? '#ef4444'),
        'warna_warning' => sanitizeInput($_POST['warna_warning'] ?? '#f59e0b'),
        'warna_info' => sanitizeInput($_POST['warna_info'] ?? '#06b6d4')
    ];
    
    if ($pengaturan->updateAll($settings)) {
        $message = 'Pengaturan PetCare berhasil diperbarui!';
        $message_type = 'success';
    } else {
        $message = 'Gagal memperbarui pengaturan!';
        $message_type = 'error';
    }
}

// Get all settings
$settings = $pengaturan->getAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Sistem - <?php echo APP_NAME; ?></title>
    <?php require_once 'head_inc.php'; ?>
</head>
<body>
    <div class="main-container">
        <?php 
        $role = $_SESSION['user_role'];
        require_once 'sidebar.php'; 
        ?>
        <main class="main-content">
            <?php require_once 'topbar.php'; ?>

            <div class="content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title"><i class="bi bi-sliders2"></i> Pengaturan Bisnis & Tampilan Sistem</h1>
                        <div class="breadcrumb-nav">Konfigurasi Toko, Tarif Denda Overstay, Persentase Komisi Groomer & Warna Tema</div>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="data-card" style="max-width: 800px;">
                    <div class="data-card-body" style="padding: 25px;">
                        <form method="POST">
                            <input type="hidden" name="action" value="update">
                            
                            <h3 style="margin-top: 0; color: #0284c7; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">
                                🏢 Informasi PetCare & Toko
                            </h3>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="nama_toko">Nama Usaha / PetCare</label>
                            <input type="text" id="nama_toko" name="nama_toko" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['nama_toko'] ?? APP_NAME); ?>" required>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="alamat_toko">Alamat Klinik / Pet Shop</label>
                            <textarea id="alamat_toko" name="alamat_toko" rows="2" class="form-control"><?php echo htmlspecialchars($settings['alamat_toko'] ?? ''); ?></textarea>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                            <div class="form-group">
                                <label for="telepon_toko">Nomor Telepon / WhatsApp</label>
                                <input type="text" id="telepon_toko" name="telepon_toko" class="form-control"
                                       value="<?php echo htmlspecialchars($settings['telepon_toko'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email_toko">Email Toko</label>
                                <input type="email" id="email_toko" name="email_toko" class="form-control"
                                       value="<?php echo htmlspecialchars($settings['email_toko'] ?? ''); ?>">
                            </div>
                        </div>

                        <h3 style="color: #0284c7; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">
                            🐾 Pengaturan Komisi Groomer & Denda Pet Hotel
                        </h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                            <div class="form-group">
                                <label for="persentase_komisi_groomer">Persentase Komisi Groomer (%)</label>
                                <input type="number" id="persentase_komisi_groomer" name="persentase_komisi_groomer" class="form-control"
                                       value="<?php echo htmlspecialchars($settings['persentase_komisi_groomer'] ?? '20'); ?>" min="0" max="100" step="1" required>
                                <small style="color: #64748b;">Dihitung otomatis saat transaksi jasa grooming selesai di kasir.</small>
                            </div>
                            <div class="form-group">
                                <label for="tarif_denda_overstay_per_jam">Tarif Denda Overstay Hotel (Rp/Jam)</label>
                                <input type="number" id="tarif_denda_overstay_per_jam" name="tarif_denda_overstay_per_jam" class="form-control"
                                       value="<?php echo htmlspecialchars($settings['tarif_denda_overstay_per_jam'] ?? '15000'); ?>" min="0" step="1000" required>
                                <small style="color: #64748b;">Dihitung otomatis jika anabul dijemput melebihi jam estimasi checkout.</small>
                            </div>
                        </div>

                        <h3 style="color: #0284c7; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">
                            🧾 Footer Struk Kasir
                        </h3>
                        <div class="form-group" style="margin-bottom: 25px;">
                            <label for="footer_struk">Pesan Bawah Nota</label>
                            <textarea id="footer_struk" name="footer_struk" rows="2" class="form-control"><?php echo htmlspecialchars($settings['footer_struk'] ?? 'Terima kasih telah mempercayakan anabul kepada kami!'); ?></textarea>
                        </div>

                        <h3 style="color: #0284c7; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">
                            🎨 Palet Warna Tema (Dynamic CSS)
                        </h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                            <div class="form-group">
                                <label for="warna_primary">Warna Primary (Aksen Utama)</label>
                                <input type="color" id="warna_primary" name="warna_primary" 
                                       value="<?php echo htmlspecialchars($settings['warna_primary'] ?? '#0284c7'); ?>" 
                                       style="width: 100%; height: 40px; border-radius: 6px; cursor: pointer; border: 1px solid #cbd5e1;">
                            </div>
                            <div class="form-group">
                                <label for="warna_secondary">Warna Secondary</label>
                                <input type="color" id="warna_secondary" name="warna_secondary" 
                                       value="<?php echo htmlspecialchars($settings['warna_secondary'] ?? '#0369a1'); ?>" 
                                       style="width: 100%; height: 40px; border-radius: 6px; cursor: pointer; border: 1px solid #cbd5e1;">
                            </div>
                            <div class="form-group">
                                <label for="warna_sidebar">Warna Sidebar Menu</label>
                                <input type="color" id="warna_sidebar" name="warna_sidebar" 
                                       value="<?php echo htmlspecialchars($settings['warna_sidebar'] ?? '#0f172a'); ?>" 
                                       style="width: 100%; height: 40px; border-radius: 6px; cursor: pointer; border: 1px solid #cbd5e1;">
                            </div>
                            <div class="form-group">
                                <label for="warna_success">Warna Tombol Sukses / Bayar</label>
                                <input type="color" id="warna_success" name="warna_success" 
                                       value="<?php echo htmlspecialchars($settings['warna_success'] ?? '#10b981'); ?>" 
                                       style="width: 100%; height: 40px; border-radius: 6px; cursor: pointer; border: 1px solid #cbd5e1;">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="padding: 12px 25px; font-size: 1rem;">
                            💾 Simpan Seluruh Pengaturan
                        </button>
                    </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="assets/js/adminator.js"></script>
</body>
</html>
