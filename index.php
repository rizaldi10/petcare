<?php
require_once 'config/config.php';

// Redirect ke dashboard jika staf sudah login
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Solusi Terpadu Pet Shop, Salon & Hotel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dynamic.php">
    <style>
        .hero {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: white;
            padding: 80px 20px 60px 20px;
            text-align: center;
        }
        .hero h1 {
            font-size: 2.8rem;
            margin-bottom: 15px;
            font-weight: 800;
        }
        .hero p {
            font-size: 1.2rem;
            max-width: 750px;
            margin: 0 auto 30px auto;
            opacity: 0.95;
            line-height: 1.6;
        }
        .hero-btns {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-hero {
            background: white;
            color: #0284c7;
            padding: 14px 28px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 1.05rem;
            text-decoration: none;
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
            transition: transform 0.2s;
        }
        .btn-hero:hover {
            transform: translateY(-2px);
        }
        .btn-hero-outline {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 2px solid white;
        }
        .btn-hero-outline:hover {
            background: white;
            color: #0284c7;
        }
        .features-grid {
            max-width: 1100px;
            margin: -40px auto 60px auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }
        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            transition: transform 0.2s;
        }
        .feature-card:hover {
            transform: translateY(-4px);
        }
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 12px;
        }
        .feature-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .feature-desc {
            font-size: 0.9rem;
            color: #64748b;
            line-height: 1.5;
        }
    </style>
</head>
<body style="background: #f8fafc; margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <!-- Hero Banner -->
    <header class="hero">
        <div style="font-size: 3.5rem; margin-bottom: 10px;">🐾</div>
        <h1><?php echo APP_NAME; ?></h1>
        <p>
            Platform operasional all-in-one untuk toko ritel pakan, pemecahan karung (*repack*), manajemen antrean grooming berhak komisi, serta reservasi kamar inap (*pet hotel*) dengan kalkulasi denda penjemputan otomatis.
        </p>
        <div class="hero-btns">
            <a href="login.php" class="btn-hero">
                🛒 Masuk ke Sistem Kasir & Staf (POS)
            </a>
            <a href="portal/index.php" class="btn-hero btn-hero-outline">
                🌐 Buka Portal Mandiri Pelanggan
            </a>
            <a href="install.php" class="btn-hero btn-hero-outline" style="background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.4);">
                ⚙️ Setup Database
            </a>
        </div>
    </header>

    <!-- 4 Pilar Fitur Utama -->
    <section class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">🛒</div>
            <div class="feature-title">Kasir POS Hibrida & Repack</div>
            <div class="feature-desc">
                Transaksi penjualan ritel terintegrasi dengan pemecahan karung pakan besar ke kemasan eceran dan pencatatan susut gram otomatis.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">✂️</div>
            <div class="feature-title">Papan Antrean Grooming</div>
            <div class="feature-desc">
                Kanban pengerjaan teknis salon anabul (Antre &rarr; Mandi &rarr; Kering &rarr; Siap Ambil) dengan rekap komisi otomatis per groomer.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">🏨</div>
            <div class="feature-title">Pet Hotel & Denda Overstay</div>
            <div class="feature-desc">
                Reservasi kamar inap berbagai ukuran (Small hingga VIP), opsi pakan, serta perhitungan denda keterlambatan penjemputan otomatis per jam.
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📱</div>
            <div class="feature-title">Portal Mandiri Pelanggan</div>
            <div class="feature-desc">
                Pendaftaran rekam alergi anabul, reservasi kamar & grooming daring, serta pelacakan progres perawatan salon secara live.
            </div>
        </div>
    </section>

    <footer style="text-align: center; padding: 30px; color: #64748b; font-size: 0.9rem; border-top: 1px solid #e2e8f0;">
        &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Native PHP Modern Architecture • Separation of Concerns • ACID Transactions.
    </footer>
</body>
</html>
