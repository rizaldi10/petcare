<?php
require_once 'config/config.php';
requireRole(['admin']);

require_once 'models/Pembelian.php';

$database = new Database();
$db = $database->getConnection();
$pembelianModel = new Pembelian($db);

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$stmt = $pembelianModel->getLaporanPembelian($start_date, $end_date);

$total_pembelian = 0;
$total_transaksi = 0;
$laporan_data = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $total_pembelian += (float)$row['total'];
    $total_transaksi++;
    $laporan_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengadaan Pasokan - <?php echo APP_NAME; ?></title>
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
                        <h1 class="page-title"><i class="bi bi-graph-down-arrow"></i> Laporan Pengadaan Pasokan Barang & Pakan</h1>
                        <div class="breadcrumb-nav">Rekapitulasi Belanja Kulakan Supplier & Riwayat Faktur Masuk</div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="data-card" style="margin-bottom: 25px;">
                    <div class="data-card-body">
                        <form method="GET" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="start_date">Dari Tanggal</label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" class="form-control" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="end_date">Sampai Tanggal</label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter Periode</button>
                            <button type="button" onclick="window.print()" class="btn btn-secondary"><i class="bi bi-printer"></i> Cetak Rekap</button>
                        </form>
                    </div>
                </div>

                <!-- Summary Cards (Adminator Spec 4.3) -->
                <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr) !important; margin-bottom: 25px;">
                    <div class="stat-card card-warning">
                        <div class="stat-icon bg-warning-soft">
                            <i class="bi bi-truck"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Total Belanja Pasokan</span>
                            <div class="stat-value"><?php echo formatCurrency($total_pembelian); ?></div>
                        </div>
                    </div>
                    <div class="stat-card card-primary">
                        <div class="stat-icon bg-primary-soft">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Total Faktur Pasokan</span>
                            <div class="stat-value"><?php echo $total_transaksi; ?> Faktur Masuk</div>
                        </div>
                    </div>
                </div>

                <!-- Laporan Table -->
                <div class="data-card">
                    <div class="data-card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No. Faktur</th>
                                    <th>Tanggal</th>
                                    <th>Vendor Pemasok</th>
                                    <th>Total Tagihan</th>
                                    <th>Rincian Item</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($laporan_data)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 25px; color: #94a3b8;">
                                            Tidak ada data pengadaan untuk rentang tanggal yang dipilih.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($laporan_data as $row): 
                                        $details = $pembelianModel->getDetailPembelian($row['id_pembelian']);
                                    ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($row['no_faktur']); ?></code></td>
                                        <td><?php echo formatTanggal($row['tgl_pembelian']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['nama_vendor']); ?></strong></td>
                                        <td><strong><?php echo formatCurrency($row['total']); ?></strong></td>
                                        <td>
                                            <ul style="margin: 0; padding-left: 18px; font-size: 0.85rem;">
                                                <?php while ($d = $details->fetch(PDO::FETCH_ASSOC)): ?>
                                                    <li><?php echo htmlspecialchars($d['nama_barang']); ?> (<?php echo $d['jumlah']; ?> @ <?php echo formatCurrency($d['harga_beli']); ?>)</li>
                                                <?php endwhile; ?>
                                            </ul>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="assets/js/adminator.js"></script>
</body>
</html>
