<?php
require_once 'config/config.php';
requireRole(['admin', 'kasir']);

require_once 'models/Barang.php';
require_once 'models/KategoriBarang.php';

$database = new Database();
$db = $database->getConnection();
$barangModel = new Barang($db);
$kategoriModel = new KategoriBarang($db);

$message = '';
$message_type = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        if ($barangModel->create($_POST)) {
            $message = 'Item produk / layanan berhasil ditambahkan!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menambahkan item. Pastikan kode barang unik.';
            $message_type = 'error';
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id_barang'];
        if ($barangModel->update($id, $_POST)) {
            $message = 'Data produk / layanan berhasil diperbarui!';
            $message_type = 'success';
        } else {
            $message = 'Gagal memperbarui data.';
            $message_type = 'error';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id_barang'];
        if ($barangModel->delete($id)) {
            $message = 'Item berhasil dihapus!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menghapus item (mungkin sudah ada transaksi terkait).';
            $message_type = 'error';
        }
    }
}

$tipe_filter = $_GET['tipe'] ?? null;
$items = $barangModel->readAll($tipe_filter);
$categories = $kategoriModel->readAll()->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Produk & Layanan - <?php echo APP_NAME; ?></title>
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
                        <h1 class="page-title"><i class="bi bi-tags-fill"></i> Master Produk & Layanan</h1>
                        <div class="breadcrumb-nav">Kelola Katalog Ritel Karung, Pakan Repack, Jasa Grooming & Kamar</div>
                    </div>
                    <div class="page-actions">
                        <?php if ($role === 'admin'): ?>
                        <button class="btn btn-primary" onclick="openAddModal()"><i class="bi bi-plus-lg"></i> Tambah Item Baru</button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: flex-start; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 20px;">
                    <a href="barang.php" class="btn btn-sm <?php echo empty($tipe_filter) ? 'btn-primary' : 'btn-secondary'; ?>">Semua Tipe</a>
                    <a href="barang.php?tipe=retail" class="btn btn-sm <?php echo ($tipe_filter === 'retail') ? 'btn-primary' : 'btn-secondary'; ?>">Ritel / Karung</a>
                    <a href="barang.php?tipe=repack" class="btn btn-sm <?php echo ($tipe_filter === 'repack') ? 'btn-primary' : 'btn-secondary'; ?>">Repack Eceran</a>
                    <a href="barang.php?tipe=jasa" class="btn btn-sm <?php echo ($tipe_filter === 'jasa') ? 'btn-primary' : 'btn-secondary'; ?>">✂️ Jasa Grooming</a>
                    <a href="barang.php?tipe=kandang" class="btn btn-sm <?php echo ($tipe_filter === 'kandang') ? 'btn-primary' : 'btn-secondary'; ?>">🏨 Item Kamar</a>
                </div>

                <div class="data-card">
                    <div class="data-card-body" style="padding: 0;">

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Produk / Layanan</th>
                                <th>Kategori</th>
                                <th>Tipe</th>
                                <th>Berat (gr)</th>
                                <th>Stok</th>
                                <th>Harga Beli</th>
                                <th>Harga Jual</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $items->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($row['kode_barang']); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($row['nama_barang']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['nama_kategori'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo ($row['tipe_item'] === 'retail') ? 'primary' : 
                                             (($row['tipe_item'] === 'repack') ? 'warning' : 
                                             (($row['tipe_item'] === 'jasa') ? 'info' : 'secondary')); 
                                    ?>">
                                        <?php echo strtoupper($row['tipe_item']); ?>
                                    </span>
                                </td>
                                <td><?php echo $row['berat_gram'] ? number_format($row['berat_gram'], 0, ',', '.') . ' g' : '-'; ?></td>
                                <td>
                                    <?php if ($row['tipe_item'] === 'jasa' || $row['tipe_item'] === 'kandang'): ?>
                                        <span style="color: #64748b;">Unlimited</span>
                                    <?php else: ?>
                                        <strong style="<?php echo ($row['stok'] <= 5) ? 'color: #ef4444;' : ''; ?>">
                                            <?php echo $row['stok']; ?>
                                        </strong>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatCurrency($row['harga_beli']); ?></td>
                                <td><strong><?php echo formatCurrency($row['harga_jual']); ?></strong></td>
                                <td>
                                    <?php if ($role === 'admin'): ?>
                                    <button class="btn btn-sm btn-info" onclick='openEditModal(<?php echo json_encode($row); ?>)'>Edit</button>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Hapus item ini?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_barang" value="<?php echo $row['id_barang']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
            </div>
        </main>
    </div>

    <!-- Modal Form Tambah / Edit Produk -->
    <div id="itemModal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="modal-content" style="background: white; border-radius: 12px; width: 100%; max-width: 550px; padding: 25px; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 id="modalTitle" style="margin: 0;">Tambah Produk / Layanan Baru</h3>
                <button type="button" onclick="closeModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            <form method="POST" id="itemForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id_barang" id="id_barang" value="">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label for="kode_barang">Kode / Barcode</label>
                        <input type="text" id="kode_barang" name="kode_barang" required class="form-control" placeholder="Contoh: RC-BABY-10KG">
                    </div>
                    <div class="form-group">
                        <label for="tipe_item">Tipe Item</label>
                        <select id="tipe_item" name="tipe_item" class="form-control">
                            <option value="retail">Ritel (Kemasan Pabrik / Karung)</option>
                            <option value="repack">Repack (Hasil Pecahan Eceran Toko)</option>
                            <option value="jasa">Jasa Grooming (Layanan Salon)</option>
                            <option value="kandang">Fasilitas Hotel (Kandang)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="nama_barang">Nama Produk / Layanan</label>
                    <input type="text" id="nama_barang" name="nama_barang" required class="form-control" placeholder="Contoh: Royal Canin Mother & Babycat 10kg">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label for="id_kategori">Kategori</label>
                        <select id="id_kategori" name="id_kategori" required class="form-control">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id_kategori']; ?>">
                                    <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="berat_gram">Berat Bersih (gram)</label>
                        <input type="number" id="berat_gram" name="berat_gram" class="form-control" placeholder="10000" min="0">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label for="stok">Stok Awal</label>
                        <input type="number" id="stok" name="stok" class="form-control" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label for="harga_beli">Harga Beli (Rp)</label>
                        <input type="number" id="harga_beli" name="harga_beli" class="form-control" value="0" min="0" step="500">
                    </div>
                    <div class="form-group">
                        <label for="harga_jual">Harga Jual (Rp)</label>
                        <input type="number" id="harga_jual" name="harga_jual" required class="form-control" placeholder="0" min="0" step="500">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Item</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').innerText = 'Tambah Produk / Layanan Baru';
            document.getElementById('formAction').value = 'create';
            document.getElementById('id_barang').value = '';
            document.getElementById('kode_barang').value = '';
            document.getElementById('nama_barang').value = '';
            document.getElementById('tipe_item').value = 'retail';
            document.getElementById('berat_gram').value = '0';
            document.getElementById('stok').value = '0';
            document.getElementById('harga_beli').value = '0';
            document.getElementById('harga_jual').value = '';
            document.getElementById('itemModal').style.display = 'flex';
        }

        function openEditModal(item) {
            document.getElementById('modalTitle').innerText = 'Edit Data Produk / Layanan';
            document.getElementById('formAction').value = 'update';
            document.getElementById('id_barang').value = item.id_barang;
            document.getElementById('kode_barang').value = item.kode_barang;
            document.getElementById('nama_barang').value = item.nama_barang;
            document.getElementById('id_kategori').value = item.id_kategori;
            document.getElementById('tipe_item').value = item.tipe_item;
            document.getElementById('berat_gram').value = item.berat_gram;
            document.getElementById('stok').value = item.stok;
            document.getElementById('harga_beli').value = item.harga_beli;
            document.getElementById('harga_jual').value = item.harga_jual;
            document.getElementById('itemModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('itemModal').style.display = 'none';
        }
    </script>
    <script src="assets/js/adminator.js"></script>
</body>
</html>
