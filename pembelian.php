<?php
require_once 'config/config.php';
requireRole(['admin']);

require_once 'models/Pembelian.php';
require_once 'models/Vendor.php';
require_once 'models/Barang.php';

$database = new Database();
$db = $database->getConnection();

$pembelianModel = new Pembelian($db);
$vendorModel = new Vendor($db);
$barangModel = new Barang($db);

$message = '';
$message_type = '';

// Handle Pembelian Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $id_vendor = (int)$_POST['id_vendor'];
    $tgl_pembelian = $_POST['tgl_pembelian'] ?? date('Y-m-d');
    $itemsRaw = json_decode($_POST['items_json'] ?? '[]', true);

    if (empty($itemsRaw)) {
        $message = 'Daftar item pengadaan pasokan masih kosong!';
        $message_type = 'error';
    } else {
        $no_faktur = $pembelianModel->generateNoFaktur();
        $res = $pembelianModel->createPurchase($id_vendor, $no_faktur, $tgl_pembelian, $itemsRaw);
        if ($res['success']) {
            $message = "Pengadaan pasokan berhasil disimpan dengan No. Faktur $no_faktur dan stok produk telah ditambah!";
            $message_type = 'success';
        } else {
            $message = "Gagal memproses pembelian: " . $res['error'];
            $message_type = 'error';
        }
    }
}

$vendors = $vendorModel->readAll()->fetchAll(PDO::FETCH_ASSOC);
$products = $barangModel->readAll('retail')->fetchAll(PDO::FETCH_ASSOC);
$purchases = $pembelianModel->readAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengadaan Pasokan - <?php echo APP_NAME; ?></title>
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
                        <h1 class="page-title"><i class="bi bi-truck"></i> Pengadaan Pasokan Barang & Pakan</h1>
                        <div class="breadcrumb-nav">Penerimaan Barang Masuk (Inbound), Faktur Supplier & Update HPP</div>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="openAddModal()"><i class="bi bi-plus-lg"></i> Catat Pasokan Masuk</button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Table Riwayat Pembelian -->
                <div class="data-card">
                    <div class="data-card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No. Faktur</th>
                                    <th>Tanggal</th>
                                    <th>Vendor Pemasok</th>
                                    <th>Total Pembelian</th>
                                    <th>Detail Item</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $hasP = false;
                                while ($p = $purchases->fetch(PDO::FETCH_ASSOC)): 
                                    $hasP = true;
                                    $details = $pembelianModel->getDetailPembelian($p['id_pembelian']);
                                ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($p['no_faktur']); ?></code></td>
                                    <td><?php echo formatTanggal($p['tgl_pembelian']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($p['nama_vendor']); ?></strong> (<?php echo htmlspecialchars($p['vendor_kontak'] ?? '-'); ?>)</td>
                                    <td><strong><?php echo formatCurrency($p['total']); ?></strong></td>
                                    <td>
                                        <ul style="margin: 0; padding-left: 18px; font-size: 0.85rem;">
                                            <?php while ($d = $details->fetch(PDO::FETCH_ASSOC)): ?>
                                                <li><?php echo htmlspecialchars($d['nama_barang']); ?> (<?php echo $d['jumlah']; ?> pcs @ <?php echo formatCurrency($d['harga_beli']); ?>)</li>
                                            <?php endwhile; ?>
                                        </ul>
                                    </td>
                                </tr>
                                <?php endwhile; ?>

                                <?php if (!$hasP): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 25px;">
                                        Belum ada data faktur pembelian / pasokan masuk.
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

    <!-- Modal Form Catat Pembelian -->
    <div id="pembelianModal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="modal-content" style="background: white; border-radius: 12px; width: 100%; max-width: 650px; padding: 25px; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Catat Pasokan Masuk dari Vendor</h3>
                <button type="button" onclick="closeModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            <form method="POST" id="pembelianForm" onsubmit="return submitPurchaseForm()">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="items_json" id="itemsJson" value="[]">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label for="id_vendor">Vendor Pemasok</label>
                        <select id="id_vendor" name="id_vendor" required class="form-control">
                            <?php foreach ($vendors as $v): ?>
                                <option value="<?php echo $v['id_vendor']; ?>">
                                    <?php echo htmlspecialchars($v['nama_vendor']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tgl_pembelian">Tanggal Faktur</label>
                        <input type="date" id="tgl_pembelian" name="tgl_pembelian" required value="<?php echo date('Y-m-d'); ?>" class="form-control">
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    <div style="font-weight: bold; font-size: 0.9rem; margin-bottom: 10px; color: #0f172a;">+ Tambah Item Pasokan</div>
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 8px; align-items: flex-end;">
                        <div>
                            <label style="font-size: 0.75rem;">Pilih Produk</label>
                            <select id="itemSelect" class="form-control">
                                <?php foreach ($products as $pr): ?>
                                    <option value="<?php echo $pr['id_barang']; ?>" data-name="<?php echo htmlspecialchars($pr['nama_barang']); ?>" data-harga="<?php echo $pr['harga_beli']; ?>">
                                        <?php echo htmlspecialchars($pr['nama_barang']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="font-size: 0.75rem;">Qty</label>
                            <input type="number" id="itemQty" value="1" min="1" class="form-control">
                        </div>
                        <div>
                            <label style="font-size: 0.75rem;">Harga Beli</label>
                            <input type="number" id="itemPrice" min="0" step="1000" class="form-control">
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="addItemToList()">Tambah</button>
                    </div>
                </div>

                <!-- Table Draft Item -->
                <table class="table" style="margin-bottom: 15px;">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Harga Beli</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="draftItemsTable">
                        <tr>
                            <td colspan="5" style="text-align: center; color: #94a3b8;">Belum ada item ditambahkan.</td>
                        </tr>
                    </tbody>
                </table>

                <div style="text-align: right; margin-bottom: 20px; font-size: 1.15rem; font-weight: bold;">
                    Total Pengadaan: <span id="grandTotalDisplay" style="color: #0284c7;">Rp 0</span>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Faktur & Update Stok</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let draftItems = [];

        function openAddModal() {
            document.getElementById('pembelianModal').style.display = 'flex';
            onItemSelectChange();
        }
        function closeModal() {
            document.getElementById('pembelianModal').style.display = 'none';
        }

        document.getElementById('itemSelect').addEventListener('change', onItemSelectChange);

        function onItemSelectChange() {
            const sel = document.getElementById('itemSelect');
            const opt = sel.options[sel.selectedIndex];
            if (opt) {
                document.getElementById('itemPrice').value = opt.getAttribute('data-harga') || 0;
            }
        }

        function addItemToList() {
            const sel = document.getElementById('itemSelect');
            const opt = sel.options[sel.selectedIndex];
            const id = parseInt(opt.value);
            const name = opt.getAttribute('data-name');
            const qty = parseInt(document.getElementById('itemQty').value) || 1;
            const price = parseFloat(document.getElementById('itemPrice').value) || 0;

            draftItems.push({
                id_barang: id,
                nama_barang: name,
                jumlah: qty,
                harga_beli: price
            });

            renderDraft();
        }

        function removeItem(idx) {
            draftItems.splice(idx, 1);
            renderDraft();
        }

        function renderDraft() {
            const tbody = document.getElementById('draftItemsTable');
            if (draftItems.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #94a3b8;">Belum ada item ditambahkan.</td></tr>';
                document.getElementById('grandTotalDisplay').innerText = 'Rp 0';
                return;
            }

            let html = '';
            let total = 0;
            draftItems.forEach((it, idx) => {
                const sub = it.jumlah * it.harga_beli;
                total += sub;
                html += `
                    <tr>
                        <td>${it.nama_barang}</td>
                        <td>${it.jumlah}</td>
                        <td>Rp ${it.harga_beli.toLocaleString('id-ID')}</td>
                        <td>Rp ${sub.toLocaleString('id-ID')}</td>
                        <td><button type="button" onclick="removeItem(${idx})" style="color: #ef4444; border: none; background: none; cursor: pointer;">&times;</button></td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
            document.getElementById('grandTotalDisplay').innerText = 'Rp ' + total.toLocaleString('id-ID');
        }

        function submitPurchaseForm() {
            if (draftItems.length === 0) {
                alert('Silakan tambahkan minimal 1 item pasokan barang!');
                return false;
            }
            document.getElementById('itemsJson').value = JSON.stringify(draftItems);
            return true;
        }
    </script>
    <script src="assets/js/adminator.js"></script>
</body>
</html>
