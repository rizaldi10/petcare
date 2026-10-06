<?php
require_once 'config/config.php';
requireLogin();

require_once 'models/Penjualan.php';
require_once 'models/Barang.php';
require_once 'models/Grooming.php';
require_once 'models/Kandang.php';
require_once 'models/Inap.php';

$database = new Database();
$db = $database->getConnection();

$penjualanModel = new Penjualan($db);
$barangModel = new Barang($db);
$groomingModel = new Grooming($db);
$kandangModel = new Kandang($db);
$inapModel = new Inap($db);

$role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Data Analitik
$totalPenjualanHari = $penjualanModel->getTotalPenjualanHari();
$totalPenjualanBulan = $penjualanModel->getTotalPenjualanBulan();
$totalTransaksiHari = $penjualanModel->getTotalTransaksiHari();
$totalProduk = $barangModel->getTotalBarang();
$stokMenipis = $barangModel->getStokMenipis(5);
$occupancy = $kandangModel->getOccupancyStats();

// Antrean Grooming Aktif
$groomer_filter = ($role === 'groomer') ? $user_id : null;
$activeQueues = $groomingModel->readAll('aktif', $groomer_filter);

// Komisi Groomer
$commissionReport = $groomingModel->getCommissionReport($groomer_filter);
$totalKomisi = 0;
$commissionsList = [];
while ($comm = $commissionReport->fetch(PDO::FETCH_ASSOC)) {
    $commissionsList[] = $comm;
    $totalKomisi += (float)$comm['nominal_komisi'];
}

// Transaksi Terbaru
$recentSales = $penjualanModel->readRecent(5);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analitik - <?php echo APP_NAME; ?></title>
    <?php require_once 'head_inc.php'; ?>
</head>
<body>
    <div class="main-container">
        <!-- Sidebar Adminator -->
        <?php require_once 'sidebar.php'; ?>

        <main class="main-content">
            <?php require_once 'topbar.php'; ?>

            <div class="content">
                <!-- Page Header & Actions (Adminator Spec 4.2) -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">
                            <i class="bi bi-speedometer2"></i> Dashboard Analitik & Operasional
                        </h1>
                        <nav class="breadcrumb-nav">
                            <span>PetCare</span> / <span class="active">Overview Operasional Hari Ini</span>
                        </nav>
                    </div>
                    <div class="page-actions">
                        <?php if ($role === 'admin' || $role === 'kasir'): ?>
                            <a href="penjualan.php" class="btn btn-primary">
                                <i class="bi bi-cart-plus-fill"></i> Kasir POS Baru
                            </a>
                            <a href="booking_inap.php" class="btn btn-secondary">
                                <i class="bi bi-building"></i> Reservasi Hotel
                            </a>
                        <?php else: ?>
                            <a href="antrean_grooming.php" class="btn btn-primary">
                                <i class="bi bi-scissors"></i> Papan Antrean Saya
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- KPI Metric Cards / Stat Widgets (Adminator Spec 4.1) -->
                <div class="stat-grid">
                    <?php if ($role === 'admin' || $role === 'kasir'): ?>
                    <div class="stat-card card-success">
                        <div class="stat-icon bg-success-soft">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Omzet Hari Ini</span>
                            <div class="stat-value"><?php echo formatCurrency($totalPenjualanHari); ?></div>
                            <span class="stat-pill success">
                                <i class="bi bi-arrow-up-right"></i> <?php echo $totalTransaksiHari; ?> Transaksi Kasir
                            </span>
                        </div>
                    </div>

                    <div class="stat-card card-primary">
                        <div class="stat-icon bg-primary-soft">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Omzet Bulan Ini</span>
                            <div class="stat-value"><?php echo formatCurrency($totalPenjualanBulan); ?></div>
                            <span class="stat-pill neutral">
                                <i class="bi bi-calendar3"></i> Periode <?php echo date('M Y'); ?>
                            </span>
                        </div>
                    </div>

                    <div class="stat-card card-warning">
                        <div class="stat-icon bg-warning-soft">
                            <i class="bi bi-building"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Okupansi Kamar Hotel</span>
                            <div class="stat-value"><?php echo $occupancy['terisi'] ?? 0; ?> / <?php echo $occupancy['total'] ?? 0; ?> Kamar</div>
                            <span class="stat-pill warning">
                                <i class="bi bi-check-circle-fill"></i> <?php echo $occupancy['tersedia'] ?? 0; ?> Kamar Tersedia
                            </span>
                        </div>
                    </div>

                    <div class="stat-card card-danger">
                        <div class="stat-icon bg-danger-soft">
                            <i class="bi bi-exclamation-octagon-fill"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Peringatan Stok (&le; 5)</span>
                            <div class="stat-value"><?php echo $stokMenipis; ?> Item Kritis</div>
                            <a href="stok.php?filter=low_stock" class="stat-pill danger" style="text-decoration: none;">
                                <i class="bi bi-box-seam"></i> Cek Gudang &rarr;
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- KPI Khusus Groomer -->
                    <div class="stat-card card-success">
                        <div class="stat-icon bg-success-soft">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Total Hak Komisi Saya</span>
                            <div class="stat-value"><?php echo formatCurrency($totalKomisi); ?></div>
                            <span class="stat-pill success">
                                <i class="bi bi-scissors"></i> <?php echo count($commissionsList); ?> Order Grooming
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Action Launcher Tiles (Modern SaaS Feature) -->
                <?php if ($role === 'admin' || $role === 'kasir'): ?>
                <div class="quick-launcher-grid">
                    <a href="penjualan.php" class="quick-tile">
                        <div class="tile-icon" style="background: #e0f2fe; color: #0284c7;">
                            <i class="bi bi-cart3"></i>
                        </div>
                        <div class="tile-info">
                            <h4>Kasir POS Hibrida</h4>
                            <p>Transaksi retail, jasa & paket anabul</p>
                        </div>
                    </a>
                    <a href="antrean_grooming.php" class="quick-tile">
                        <div class="tile-icon" style="background: #ede9fe; color: #7c3aed;">
                            <i class="bi bi-scissors"></i>
                        </div>
                        <div class="tile-info">
                            <h4>Papan Antrean Grooming</h4>
                            <p>Monitor cuci, potong, & komisi</p>
                        </div>
                    </a>
                    <a href="booking_inap.php" class="quick-tile">
                        <div class="tile-icon" style="background: #fef3c7; color: #d97706;">
                            <i class="bi bi-building"></i>
                        </div>
                        <div class="tile-info">
                            <h4>Reservasi Pet Hotel</h4>
                            <p>Check-in / out penitipan kamar</p>
                        </div>
                    </a>
                    <a href="customer.php" class="quick-tile">
                        <div class="tile-icon" style="background: #d1fae5; color: #059669;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="tile-info">
                            <h4>Pelanggan & Anabul</h4>
                            <p>Database member & profil hewan</p>
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Grid Data Table Cards (Adminator Spec 4.3) -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(460px, 1fr)); gap: 24px;">
                    <!-- Card 1: Antrean Grooming Berjalan -->
                    <div class="data-card">
                        <div class="data-card-header">
                            <h3 class="data-card-title">
                                <span class="pulse-dot"></span> Antrean Grooming Salon Hari Ini
                            </h3>
                            <div class="data-card-tools">
                                <a href="antrean_grooming.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-lg"></i> Antrean Baru
                                </a>
                            </div>
                        </div>
                        <div class="data-card-body" style="padding: 0;">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Pasien Anabul</th>
                                            <th>Layanan</th>
                                            <th>Groomer</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $hasQueue = false;
                                        while ($q = $activeQueues->fetch(PDO::FETCH_ASSOC)): 
                                            $hasQueue = true;
                                            $st = $q['status_pengerjaan'];
                                            $badgeClass = ($st === 'Antre') ? 'badge-warning' : 
                                                         (($st === 'Mandi') ? 'badge-info' : 
                                                         (($st === 'Pengeringan') ? 'badge-purple' : 'badge-success'));
                                        ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 6px;">
                                                    <i class="bi bi-heart-fill text-danger" style="font-size: 11px;"></i>
                                                    <?php echo htmlspecialchars($q['nama_hewan']); ?>
                                                </div>
                                                <small class="text-muted"><?php echo htmlspecialchars($q['nama_customer']); ?></small>
                                            </td>
                                            <td>
                                                <span style="font-weight: 600; color: var(--text-secondary);">
                                                    <?php echo htmlspecialchars($q['nama_layanan']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($q['nama_groomer']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <?php echo str_replace('_', ' ', $st); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>

                                        <?php if (!$hasQueue): ?>
                                        <tr>
                                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 40px 20px;">
                                                <div class="empty-state" style="padding: 0;">
                                                    <i class="bi bi-check2-circle" style="font-size: 38px; color: #10b981;"></i>
                                                    <h4>Semua Anabul Selesai Dirawat</h4>
                                                    <p>Tidak ada antrean pengerjaan aktif saat ini. Anda dapat mendaftarkan hewan baru.</p>
                                                    <div style="margin-top: 14px;">
                                                        <a href="antrean_grooming.php" class="btn btn-sm btn-secondary">
                                                            <i class="bi bi-clipboard-plus"></i> Buka Antrean Salon
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="data-card-footer">
                            <span class="text-muted">
                                <i class="bi bi-arrow-repeat"></i> Alur: Antre &rarr; Mandi &rarr; Kering &rarr; Selesai
                            </span>
                            <a href="antrean_grooming.php" style="font-weight: 700; font-size: 12px;">
                                Kelola Antrean &rarr;
                            </a>
                        </div>
                    </div>

                    <!-- Card 2: Transaksi Penjualan Terkini / Komisi -->
                    <div class="data-card">
                        <?php if ($role === 'admin' || $role === 'kasir'): ?>
                            <div class="data-card-header">
                                <h3 class="data-card-title">
                                    <i class="bi bi-receipt text-primary"></i> Transaksi Kasir Terkini
                                </h3>
                                <div class="data-card-tools">
                                    <a href="penjualan.php" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-lg"></i> Kasir Baru
                                    </a>
                                </div>
                            </div>
                            <div class="data-card-body" style="padding: 0;">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>No. Faktur</th>
                                                <th>Pelanggan</th>
                                                <th>Kasir</th>
                                                <th>Total</th>
                                                <th style="text-align: right;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $hasSale = false;
                                            while ($s = $recentSales->fetch(PDO::FETCH_ASSOC)): 
                                                $hasSale = true;
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-secondary" style="font-family: var(--font-mono);">
                                                        <?php echo htmlspecialchars($s['no_faktur']); ?>
                                                    </span>
                                                </td>
                                                <td><strong><?php echo htmlspecialchars($s['nama_customer']); ?></strong></td>
                                                <td><small class="text-muted"><?php echo htmlspecialchars($s['kasir']); ?></small></td>
                                                <td><strong style="color: var(--color-success);"><?php echo formatCurrency($s['total_bayar']); ?></strong></td>
                                                <td style="text-align: right;">
                                                    <a href="struk.php?id=<?php echo $s['id_penjualan']; ?>" target="_blank" class="btn btn-sm btn-secondary" title="Cetak Struk">
                                                        <i class="bi bi-printer"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>

                                            <?php if (!$hasSale): ?>
                                            <tr>
                                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px 20px;">
                                                    <div class="empty-state" style="padding: 0;">
                                                        <i class="bi bi-receipt-cutoff" style="font-size: 38px; color: #94a3b8;"></i>
                                                        <h4>Belum Ada Transaksi Kasir</h4>
                                                        <p>Mulai catat transaksi penjualan barang atau layanan melalui Kasir POS.</p>
                                                        <div style="margin-top: 14px;">
                                                            <a href="penjualan.php" class="btn btn-sm btn-primary">
                                                                <i class="bi bi-cart3"></i> Buka Kasir POS
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="data-card-footer">
                                <span class="text-muted">Menampilkan 5 transaksi kasir terbaru</span>
                                <a href="laporan_penjualan.php" style="font-weight: 700; font-size: 12px;">
                                    Laporan Lengkap &rarr;
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Rekap Komisi Khusus Groomer -->
                            <div class="data-card-header">
                                <h3 class="data-card-title">
                                    <i class="bi bi-scissors text-primary"></i> Riwayat Pembagian Komisi
                                </h3>
                            </div>
                            <div class="data-card-body" style="padding: 0;">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Waktu</th>
                                                <th>Layanan</th>
                                                <th>Persen</th>
                                                <th style="text-align: right;">Komisi Diterima</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($commissionsList, 0, 5) as $cm): ?>
                                            <tr>
                                                <td><?php echo formatTanggalWaktu($cm['tgl_transaksi']); ?></td>
                                                <td><?php echo htmlspecialchars($cm['nama_layanan']); ?></td>
                                                <td><span class="badge badge-info"><?php echo $cm['persentase_komisi']; ?>%</span></td>
                                                <td style="text-align: right;"><strong style="color: var(--color-success);"><?php echo formatCurrency($cm['nominal_komisi']); ?></strong></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($commissionsList)): ?>
                                            <tr>
                                                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 40px 20px;">
                                                    <div class="empty-state" style="padding: 0;">
                                                        <i class="bi bi-wallet2" style="font-size: 38px; color: #94a3b8;"></i>
                                                        <h4>Belum Ada Catatan Komisi</h4>
                                                        <p>Komisi akan dihitung otomatis saat transaksi grooming diselesaikan di kasir.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="data-card-footer">
                                <span class="text-muted">Komisi dihitung otomatis per nota kasir</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Adminator Micro-Interactions JS -->
    <script src="assets/js/adminator.js"></script>
</body>
</html>
