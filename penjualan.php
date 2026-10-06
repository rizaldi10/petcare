<?php
require_once 'config/config.php';
requireRole(['admin', 'kasir']);

require_once 'models/Penjualan.php';
require_once 'models/Barang.php';
require_once 'models/Customer.php';
require_once 'models/User.php';
require_once 'models/Hewan.php';

$database = new Database();
$db = $database->getConnection();
$penjualanModel = new Penjualan($db);
$barangModel = new Barang($db);
$customerModel = new Customer($db);
$userModel = new User($db);
$hewanModel = new Hewan($db);

$message = '';
$message_type = '';

// Handle Checkout Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $id_customer = (int)$_POST['id_customer'];
    $id_user = (int)$_SESSION['user_id'];
    $cartData = json_decode($_POST['cart_data'], true);
    $groomer_id = !empty($_POST['groomer_id']) ? (int)$_POST['groomer_id'] : null;
    $hewan_id = !empty($_POST['id_hewan']) ? (int)$_POST['id_hewan'] : null;
    $catatan_grooming = $_POST['catatan_grooming'] ?? '';

    if (empty($cartData) || !is_array($cartData)) {
        $message = 'Keranjang belanja masih kosong!';
        $message_type = 'error';
    } else {
        $result = $penjualanModel->processCheckout(
            $id_customer,
            $id_user,
            $cartData,
            $groomer_id,
            $hewan_id,
            $catatan_grooming
        );

        if ($result['success']) {
            header('Location: struk.php?id=' . $result['id_penjualan']);
            exit();
        } else {
            $message = 'Gagal memproses transaksi: ' . $result['error'];
            $message_type = 'error';
        }
    }
}

$customers = $customerModel->readAll()->fetchAll(PDO::FETCH_ASSOC);
$products = $barangModel->readAll()->fetchAll(PDO::FETCH_ASSOC);
$groomers = $userModel->getGroomers();
$allPets = $hewanModel->readAll()->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir Hibrida - <?php echo APP_NAME; ?></title>
    <?php require_once 'head_inc.php'; ?>
    <style>
        .pos-layout {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 20px;
            height: calc(100vh - 120px);
        }
        .catalog-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .cart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .filter-tabs {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .filter-pill {
            padding: 6px 14px;
            border-radius: 9999px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            font-size: 0.85rem;
            cursor: pointer;
            white-space: nowrap;
            font-weight: 500;
        }
        .filter-pill.active {
            background: #0284c7;
            color: white;
            border-color: #0284c7;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            overflow-y: auto;
            flex: 1;
            padding-right: 5px;
        }
        .product-item-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.15s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff;
        }
        .product-item-card:hover {
            border-color: #0284c7;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(2, 132, 199, 0.15);
        }
        .cart-scroll {
            flex: 1;
            overflow-y: auto;
            margin: 10px 0;
            padding-right: 5px;
        }
        .quick-cash-btn {
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #f8fafc;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
        }
        .quick-cash-btn:hover {
            background: #e2e8f0;
        }
    </style>
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
                        <h1 class="page-title"><i class="bi bi-cart3"></i> Kasir POS Hibrida</h1>
                        <div class="breadcrumb-nav">Transaksi Ritel Produk Hewan, Eceran Repack & Layanan Grooming</div>
                    </div>
                    <div class="page-actions">
                        <a href="repack.php" class="btn btn-sm btn-secondary"><i class="bi bi-boxes"></i> Konversi Repack</a>
                        <a href="antrean_grooming.php" class="btn btn-sm btn-secondary"><i class="bi bi-scissors"></i> Antrean Grooming</a>
                        <a href="booking_inap.php" class="btn btn-sm btn-secondary"><i class="bi bi-building"></i> Pet Hotel</a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

            <div class="pos-layout">
                <!-- SISI KIRI: KATALOG PRODUK & JASA -->
                <div class="catalog-container">
                    <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                        <input type="text" id="searchBox" class="form-control" placeholder="Cari nama barang, pakan, barcode, atau paket jasa..." oninput="searchProducts(this.value)">
                    </div>

                    <div class="filter-tabs">
                        <button type="button" class="filter-pill active" data-type="all" onclick="filterProducts('all')">Semua Item</button>
                        <button type="button" class="filter-pill" data-type="retail" onclick="filterProducts('retail')">Pakan & Ritel</button>
                        <button type="button" class="filter-pill" data-type="repack" onclick="filterProducts('repack')">Repack Eceran</button>
                        <button type="button" class="filter-pill" data-type="jasa" onclick="filterProducts('jasa')"><i class="bi bi-scissors"></i> Layanan Grooming</button>
                        <button type="button" class="filter-pill" data-type="kandang" onclick="filterProducts('kandang')"><i class="bi bi-building"></i> Fasilitas Inap</button>
                    </div>

                    <div class="product-grid" id="productGrid">
                        <?php foreach ($products as $p): ?>
                        <div class="product-item-card" 
                             data-type="<?php echo $p['tipe_item']; ?>"
                             data-name="<?php echo htmlspecialchars($p['nama_barang']); ?>"
                             data-code="<?php echo htmlspecialchars($p['kode_barang']); ?>"
                             onclick='addToCart(<?php echo json_encode($p); ?>)'>
                            <div>
                                <span class="badge badge-<?php 
                                    echo ($p['tipe_item'] === 'retail') ? 'primary' : 
                                         (($p['tipe_item'] === 'repack') ? 'warning' : 
                                         (($p['tipe_item'] === 'jasa') ? 'info' : 'secondary')); 
                                ?>" style="font-size: 0.7rem; margin-bottom: 6px; display: inline-block;">
                                    <?php echo strtoupper($p['tipe_item']); ?>
                                </span>
                                <div style="font-weight: 600; font-size: 0.95rem; color: #1e293b; margin-bottom: 6px; line-height: 1.3;">
                                    <?php echo htmlspecialchars($p['nama_barang']); ?>
                                </div>
                            </div>
                            <div style="margin-top: 10px;">
                                <div style="font-size: 1.05rem; font-weight: 700; color: #0284c7;">
                                    <?php echo formatCurrency($p['harga_jual']); ?>
                                </div>
                                <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                                    <?php if ($p['tipe_item'] === 'jasa'): ?>
                                        Paket Layanan
                                    <?php else: ?>
                                        Stok: <strong><?php echo $p['stok']; ?></strong> item
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- SISI KANAN: KERANJANG BELANJA & PEMBAYARAN -->
                <div class="cart-container">
                    <form method="POST" id="checkoutForm" onsubmit="return validateCheckout()">
                        <input type="hidden" name="action" value="checkout">
                        <input type="hidden" name="cart_data" id="cartInput" value="[]">

                        <!-- Data Pelanggan -->
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label for="id_customer" style="font-weight: 600; font-size: 0.85rem;">Pelanggan / Member</label>
                            <select id="id_customer" name="id_customer" required class="form-control" onchange="onCustomerChange(this.value)">
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?php echo $c['id_customer']; ?>">
                                        <?php echo htmlspecialchars($c['nama_customer']); ?> (<?php echo $c['telepon']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Data Penugasan Grooming (Tampil jika ada Jasa di Keranjang) -->
                        <div id="serviceAlert" style="display: none; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                            <div style="font-size: 0.85rem; font-weight: bold; color: #1e40af; margin-bottom: 8px;">
                                ✂️ Penugasan Layanan Grooming & Komisi
                            </div>
                            <div style="margin-bottom: 8px;">
                                <label style="font-size: 0.8rem; color: #1e3a8a;">Pilih Anabul Pasien</label>
                                <select id="id_hewan" name="id_hewan" class="form-control" style="font-size: 0.85rem;">
                                    <option value="">-- Pilih Pasien Hewan --</option>
                                </select>
                            </div>
                            <div style="margin-bottom: 8px;">
                                <label style="font-size: 0.8rem; color: #1e3a8a;">Pilih Groomer (Hak Komisi 20%)</label>
                                <select id="groomer_id" name="groomer_id" class="form-control" style="font-size: 0.85rem;">
                                    <?php foreach ($groomers as $g): ?>
                                        <option value="<?php echo $g['id_user']; ?>">✂️ <?php echo htmlspecialchars($g['nama']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 0.8rem; color: #1e3a8a;">Catatan Kondisi</label>
                                <input type="text" name="catatan_grooming" class="form-control" placeholder="Kutu/jamur/sensitivitas..." style="font-size: 0.85rem;">
                            </div>
                        </div>

                        <!-- List Items Keranjang -->
                        <div class="cart-scroll" id="cartItems">
                            <div style="text-align: center; color: #94a3b8; padding: 30px;">
                                Keranjang kosong. Klik produk di sebelah kiri untuk menambahkan.
                            </div>
                        </div>

                        <!-- Ringkasan & Pembayaran -->
                        <div style="border-top: 2px dashed #e2e8f0; padding-top: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px;">
                                <span style="font-size: 1rem; color: #475569; font-weight: 600;">Total Bayar:</span>
                                <span id="totalBayarDisplay" style="font-size: 1.5rem; font-weight: 800; color: #0284c7;">Rp 0</span>
                            </div>

                            <div class="form-group" style="margin-bottom: 8px;">
                                <label for="nominalBayar" style="font-size: 0.85rem; font-weight: 600;">Nominal Bayar / Uang Tunai</label>
                                <input type="number" id="nominalBayar" class="form-control" placeholder="0" min="0" oninput="calculateChange()">
                            </div>

                            <!-- Tombol Uang Pas / Cepat -->
                            <div style="display: flex; gap: 6px; margin-bottom: 10px;">
                                <button type="button" class="quick-cash-btn" onclick="setQuickCash('pas')">Uang Pas</button>
                                <button type="button" class="quick-cash-btn" onclick="setQuickCash(50000)">50K</button>
                                <button type="button" class="quick-cash-btn" onclick="setQuickCash(100000)">100K</button>
                                <button type="button" class="quick-cash-btn" onclick="setQuickCash(200000)">200K</button>
                            </div>

                            <div style="display: flex; justify-content: space-between; font-size: 0.95rem; margin-bottom: 15px;">
                                <span>Kembalian:</span>
                                <strong id="kembalianDisplay">Rp 0</strong>
                            </div>

                            <div style="display: flex; gap: 8px;">
                                <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="clearCart()">Batal</button>
                                <button type="submit" class="btn btn-success" style="flex: 2; font-weight: bold; font-size: 1rem;">
                                    <i class="bi bi-printer"></i> Bayar & Cetak Nota
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            </div>
        </main>
    </div>

    <script src="assets/js/adminator.js"></script>
    <script src="assets/js/kasir.js"></script>
    <script>
        const allPetsData = <?php echo json_encode($allPets); ?>;

        function onCustomerChange(custId) {
            custId = parseInt(custId);
            const petSelect = document.getElementById('id_hewan');
            petSelect.innerHTML = '<option value="">-- Pilih Pasien Hewan --</option>';

            const filteredPets = allPetsData.filter(p => parseInt(p.id_customer) === custId);
            filteredPets.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id_hewan;
                opt.textContent = `${p.nama_hewan} (${p.spesies} - ${p.ras || 'Mix'})`;
                petSelect.appendChild(opt);
            });
        }

        function setQuickCash(val) {
            let total = cart.reduce((sum, item) => sum + (item.harga_satuan * item.jumlah), 0);
            if (val === 'pas') {
                document.getElementById('nominalBayar').value = total;
            } else {
                document.getElementById('nominalBayar').value = val;
            }
            calculateChange();
        }

        function validateCheckout() {
            if (cart.length === 0) {
                alert('Keranjang belanja masih kosong!');
                return false;
            }
            let total = cart.reduce((sum, item) => sum + (item.harga_satuan * item.jumlah), 0);
            let bayar = parseFloat(document.getElementById('nominalBayar').value) || 0;
            if (bayar < total) {
                alert('Nominal pembayaran kurang!');
                return false;
            }
            return true;
        }

        // Inisialisasi dropdown hewan untuk customer pertama
        const firstCust = document.getElementById('id_customer').value;
        if (firstCust) {
            onCustomerChange(firstCust);
        }
    </script>
</body>
</html>
