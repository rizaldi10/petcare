<?php
require_once 'config/config.php';
requireRole(['admin']);

require_once 'models/Barang.php';

$database = new Database();
$db = $database->getConnection();
$barangModel = new Barang($db);

$message = '';
$message_type = '';

// Handle stock adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjust_stock') {
    $barang_id = (int)$_POST['barang_id'];
    $adjustment = (int)$_POST['adjustment'];
    $reason = sanitizeInput($_POST['reason']);
    
    if ($barangModel->updateStok($barang_id, $adjustment)) {
        $message = 'Stok berhasil disesuaikan!';
        $message_type = 'success';
    } else {
        $message = 'Gagal menyesuaikan stok!';
        $message_type = 'error';
    }
}

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build Query
$query = "SELECT b.*, b.id_barang as id, k.nama_kategori 
          FROM barang b
          LEFT JOIN kategori_barang k ON b.id_kategori = k.id_kategori 
          WHERE b.tipe_item IN ('retail', 'repack') ";

if ($filter === 'low_stock') {
    $query .= " AND b.stok <= 5 ";
} elseif ($filter === 'repack') {
    $query .= " AND b.tipe_item = 'repack' ";
} elseif ($filter === 'retail') {
    $query .= " AND b.tipe_item = 'retail' ";
}

if (!empty($search)) {
    $query .= " AND (b.nama_barang LIKE :search OR b.kode_barang LIKE :search) ";
}

$query .= " ORDER BY b.stok ASC, b.nama_barang ASC";

$stmt = $db->prepare($query);
if (!empty($search)) {
    $term = "%{$search}%";
    $stmt->bindParam(':search', $term);
}
$stmt->execute();

$allRetailItems = $barangModel->getRetailAndRepack();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Stok Pakan & Ritel - <?php echo APP_NAME; ?></title>
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
                        <h1 class="page-title"><i class="bi bi-boxes"></i> Monitoring & Opname Stok Gudang</h1>
                        <div class="breadcrumb-nav">Pengawasan Persediaan Ritel, Repack & Koreksi Stok Fisik</div>
                    </div>
                    <div class="page-actions">
                        <a href="repack.php" class="btn btn-sm btn-secondary"><i class="bi bi-box-seam"></i> Konversi Repack</a>
                        <a href="pembelian.php" class="btn btn-sm btn-secondary"><i class="bi bi-truck"></i> Pengadaan Pasokan</a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 25px;">
                    <!-- Filter Section -->
                    <div class="data-card">
                        <div class="data-card-body">
                            <h3 style="margin-top: 0; font-size: 1.1rem;"><i class="bi bi-funnel text-primary"></i> Filter Inventori</h3>
                        <form method="GET" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                                <label for="filter">Status Kategori Stok</label>
                                <select id="filter" name="filter" class="form-control" onchange="this.form.submit()">
                                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Semua Produk Ritel & Repack</option>
                                    <option value="low_stock" <?php echo $filter === 'low_stock' ? 'selected' : ''; ?>>⚠️ Stok Menipis (&le; 5 item)</option>
                                    <option value="repack" <?php echo $filter === 'repack' ? 'selected' : ''; ?>>Kemasan Repack Eceran Saja</option>
                                    <option value="retail" <?php echo $filter === 'retail' ? 'selected' : ''; ?>>Karung / Pabrikan Saja</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                                <label for="search">Cari Barang</label>
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Kode atau nama produk..." class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="stok.php" class="btn btn-secondary">Reset</a>
                        </form>
                        </div>
                    </div>

                    <!-- Stock Adjustment Form -->
                    <div class="data-card">
                        <div class="data-card-body">
                            <h3 style="margin-top: 0; font-size: 1.1rem;">⚖️ Koreksi / Opname Stok</h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="adjust_stock">
                                
                                <div class="form-group" style="margin-bottom: 10px;">
                                    <label for="barang_id">Pilih Produk</label>
                                    <select id="barang_id" name="barang_id" required class="form-control">
                                        <option value="">-- Pilih Barang --</option>
                                        <?php while ($row = $allRetailItems->fetch(PDO::FETCH_ASSOC)): ?>
                                            <option value="<?php echo $row['id_barang']; ?>">
                                                <?php echo htmlspecialchars($row['nama_barang']); ?> (Stok: <?php echo $row['stok']; ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div class="form-group">
                                        <label for="adjustment">Selisih (+/-)</label>
                                        <input type="number" id="adjustment" name="adjustment" required 
                                               placeholder="+10 atau -5" class="form-control" step="1">
                                    </div>
                                    <div class="form-group">
                                        <label for="reason">Alasan</label>
                                        <input type="text" id="reason" name="reason" required 
                                               placeholder="Opname/rusak" class="form-control">
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Terapkan Penyesuaian</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Stock Table -->
                <div class="data-card">
                    <div class="data-card-body" style="padding: 0;">
                    <h3 style="margin-top: 0; font-size: 1.15rem;">Daftar Stok Produk Fisik</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Produk</th>
                                    <th>Kategori</th>
                                    <th>Tipe</th>
                                    <th>Berat Bersih</th>
                                    <th>Harga Jual</th>
                                    <th>Stok Saat Ini</th>
                                    <th>Status Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $hasItem = false;
                                while ($item = $stmt->fetch(PDO::FETCH_ASSOC)): 
                                    $hasItem = true;
                                    $isLow = ($item['stok'] <= 5);
                                ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($item['kode_barang']); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($item['nama_barang']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['nama_kategori'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo ($item['tipe_item'] === 'retail') ? 'primary' : 'warning'; ?>">
                                            <?php echo strtoupper($item['tipe_item']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $item['berat_gram'] ? number_format($item['berat_gram'], 0, ',', '.') . ' g' : '-'; ?></td>
                                    <td><?php echo formatCurrency($item['harga_jual']); ?></td>
                                    <td>
                                        <strong style="font-size: 1.1rem; <?php echo $isLow ? 'color: #ef4444;' : 'color: #166534;'; ?>">
                                            <?php echo $item['stok']; ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if ($isLow): ?>
                                            <span class="badge badge-danger">⚠️ Stok Menipis</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Aman (Ready)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>

                                <?php if (!$hasItem): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 25px;">
                                        Tidak ada data stok ditemukan untuk kriteria ini.
                                    </td>
                                </tr>
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
