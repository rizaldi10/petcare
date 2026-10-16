<?php
require_once 'config/config.php';
requireRole(['admin', 'kasir']);

require_once 'models/Penjualan.php';
require_once 'models/Barang.php';
require_once 'models/Kandang.php';

$database = new Database();
$db = $database->getConnection();

$penjualanModel = new Penjualan($db);
$barangModel = new Barang($db);
$kandangModel = new Kandang($db);

$role = $_SESSION['user_role'];

// Data Analitik
$totalPenjualanHari = $penjualanModel->getTotalPenjualanHari();
$totalPenjualanBulan = $penjualanModel->getTotalPenjualanBulan();
$totalTransaksiHari = $penjualanModel->getTotalTransaksiHari();
$totalProduk = $barangModel->getTotalBarang();
$stokMenipis = $barangModel->getStokMenipis(5);
$stokPakan = $barangModel->getPakanStockStats(5);
$occupancy = $kandangModel->getOccupancyStats();

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
                <?php if ($role === 'kasir'): ?>
                <div class="page-header">
                    <div>
                        <h1 class="page-title"><i class="bi bi-speedometer2"></i> Dashboard Kasir</h1>
                        <nav class="breadcrumb-nav"><span>PetCare</span> / <span class="active">Ringkasan Operasional Hari Ini</span></nav>
                    </div>
                    <div class="page-actions">
                        <a href="penjualan.php" class="btn btn-primary"><i class="bi bi-cart-plus-fill"></i> Buka POS</a>
                    </div>
                </div>

                <div class="stat-grid">
                    <div class="stat-card card-success">
                        <div class="stat-icon bg-success-soft"><i class="bi bi-cash-stack"></i></div>
                        <div class="stat-details">
                            <span class="stat-label">Omzet Hari Ini</span>
                            <div class="stat-value"><?php echo formatCurrency($totalPenjualanHari); ?></div>
                            <span class="stat-pill success"><i class="bi bi-receipt"></i> <?php echo $totalTransaksiHari; ?> transaksi</span>
                        </div>
                    </div>
                    <div class="stat-card card-warning">
                        <div class="stat-icon bg-warning-soft"><i class="bi bi-building-check"></i></div>
                        <div class="stat-details">
                            <span class="stat-label">Sisa Kamar Penitipan</span>
                            <div class="stat-value"><?php echo (int)($occupancy['tersedia'] ?? 0); ?></div>
                            <span class="stat-pill warning">dari <?php echo (int)($occupancy['total'] ?? 0); ?> kamar</span>
                        </div>
                    </div>
                    <div class="stat-card card-primary">
                        <div class="stat-icon bg-primary-soft"><i class="bi bi-box-seam"></i></div>
                        <div class="stat-details">
                            <span class="stat-label">Stok Pakan</span>
                            <div class="stat-value"><?php echo (int)($stokPakan['total_unit'] ?? 0); ?> unit</div>
                            <span class="stat-pill neutral">
                                <?php echo (int)($stokPakan['total_sku'] ?? 0); ?> produk ·
                                <?php echo (int)($stokPakan['sku_menipis'] ?? 0); ?> stok menipis
                            </span>
                        </div>
                    </div>
                </div>

                <div class="page-actions" style="justify-content: flex-start; flex-wrap: wrap; gap: 10px; margin: -8px 0 22px;">
                    <a href="penjualan.php" class="btn btn-primary">
                        <i class="bi bi-cart3"></i> POS Kasir Hibrida
                    </a>
                    <a href="booking_inap.php" class="btn btn-secondary">
                        <i class="bi bi-building"></i> Pet Hotel
                    </a>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(460px, 1fr)); gap: 24px;">
                    <div class="data-card">
                        <div class="data-card-header">
                            <h3 class="data-card-title"><i class="bi bi-receipt text-primary"></i> Transaksi Terbaru</h3>
                            <div class="data-card-tools"><a href="penjualan.php" class="btn btn-sm btn-primary">Kasir POS</a></div>
                        </div>
                        <div class="data-card-body" style="padding: 0;">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead><tr><th>No. Faktur</th><th>Pelanggan</th><th>Total</th><th>Aksi</th></tr></thead>
                                    <tbody>
                                    <?php $hasCashierSale = false; while ($s = $recentSales->fetch(PDO::FETCH_ASSOC)): $hasCashierSale = true; ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($s['no_faktur']); ?></td>
                                            <td><?php echo htmlspecialchars($s['nama_customer']); ?></td>
                                            <td><strong><?php echo formatCurrency($s['total_bayar']); ?></strong></td>
                                            <td><a href="struk.php?id=<?php echo (int)$s['id_penjualan']; ?>" target="_blank" class="btn btn-sm btn-secondary" title="Lihat struk"><i class="bi bi-printer"></i></a></td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if (!$hasCashierSale): ?><tr><td colspan="4" style="text-align:center;padding:28px;color:var(--text-muted);">Belum ada transaksi.</td></tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="data-card-footer"><span class="text-muted">5 transaksi terakhir</span><a href="laporan_penjualan.php">Laporan Penjualan</a></div>
                    </div>
                </div>
                <?php else: ?>
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
                            <a href="penjualan.php" class="btn btn-primary">
                                <i class="bi bi-cart-plus-fill"></i> Kasir POS Baru
                            </a>
                            <a href="booking_inap.php" class="btn btn-secondary">
                                <i class="bi bi-building"></i> Reservasi Hotel
                            </a>
                    </div>
                </div>

                <!-- KPI Metric Cards / Stat Widgets (Adminator Spec 4.1) -->
                <div class="stat-grid">
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
                </div>

                <!-- Quick Action Launcher Tiles (Modern SaaS Feature) -->
                <div class="quick-launcher-grid">
                    <a href="penjualan.php" class="quick-tile">
                        <div class="tile-icon" style="background: #e0f2fe; color: #0284c7;">
                            <i class="bi bi-cart3"></i>
                        </div>
                        <div class="tile-info">
                            <h4>Kasir POS Hibrida</h4>
                            <p>Transaksi produk dan pelunasan inap</p>
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

                <!-- Grid Data Table Cards (Adminator Spec 4.3) -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(460px, 1fr)); gap: 24px;">
                    <!-- Transaksi Penjualan Terkini -->
                    <div class="data-card">
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
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Adminator Micro-Interactions JS -->
    <script src="assets/js/adminator.js"></script>
</body>
</html>
