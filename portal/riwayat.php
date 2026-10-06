<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/models/Customer.php';
require_once dirname(__DIR__) . '/models/Penjualan.php';

requireCustomerLogin();

$database = new Database();
$db = $database->getConnection();
$customerModel = new Customer($db);
$penjualanModel = new Penjualan($db);

$customer_id = $_SESSION['customer_id'];
$history = $penjualanModel->getCustomerHistory($customer_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi & Struk - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/portal.css">
</head>
<body class="portal-body">
    <nav class="portal-nav">
        <a href="index.php" class="portal-logo">
            <span>🐾</span> <?php echo APP_NAME; ?>
        </a>
        <div class="portal-menu">
            <a href="index.php" class="portal-link">Beranda</a>
            <a href="my_pets.php" class="portal-link">Anabul Saya</a>
            <a href="booking.php" class="portal-link">Booking Online</a>
            <a href="track.php" class="portal-link">Pantau Perawatan (Live)</a>
            <a href="riwayat.php" class="portal-link active">Riwayat Struk</a>
            <a href="index.php?action=logout" class="portal-link" style="color: #ef4444;">Keluar</a>
        </div>
    </nav>

    <div class="portal-container">
        <h2 style="margin: 0 0 5px 0; color: #0f172a;">Arsip Transaksi & Struk Digital</h2>
        <p style="margin: 0 0 20px 0; color: #64748b;">Lihat arsip pembelian pakan, obat, layanan salon, dan penginapan anabul Anda.</p>

        <div class="portal-card">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.95rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e2e8f0; text-align: left;">
                            <th style="padding: 10px;">No. Faktur</th>
                            <th style="padding: 10px;">Tanggal</th>
                            <th style="padding: 10px;">Kasir</th>
                            <th style="padding: 10px;">Total Pembayaran</th>
                            <th style="padding: 10px; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 10px;"><code><?php echo htmlspecialchars($h['no_faktur']); ?></code></td>
                            <td style="padding: 12px 10px;"><?php echo formatTanggal($h['tgl_penjualan']); ?></td>
                            <td style="padding: 12px 10px;"><?php echo htmlspecialchars($h['kasir']); ?></td>
                            <td style="padding: 12px 10px;"><strong><?php echo formatCurrency($h['total_bayar']); ?></strong></td>
                            <td style="padding: 12px 10px; text-align: right;">
                                <a href="../struk.php?id=<?php echo $h['id_penjualan']; ?>" target="_blank" class="portal-btn" style="padding: 6px 14px; font-size: 0.85rem;">
                                    Buka Struk Digital ↗
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">
                                Belum ada riwayat transaksi tercatat untuk akun Anda.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
