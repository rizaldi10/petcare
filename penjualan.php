<?php
require_once 'config/config.php';
requireRole(['admin', 'kasir']);

require_once 'models/Penjualan.php';
require_once 'models/Barang.php';
require_once 'models/Customer.php';
require_once 'models/Inap.php';
require_once 'models/Pengaturan.php';

$database = new Database();
$db = $database->getConnection();
$penjualanModel = new Penjualan($db);
$barangModel = new Barang($db);
$customerModel = new Customer($db);
$walkInCustomerId = $customerModel->ensureWalkInCustomer();
$inapModel = new Inap($db);
$pengaturanModel = new Pengaturan($db);
$hourly_penalty_rate = (float)($pengaturanModel->get('tarif_denda_overstay_per_jam') ?? 15000);

$message = '';
$message_type = '';

// Handle Checkout Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $id_customer = (int)$_POST['id_customer'];
    $id_user = (int)$_SESSION['user_id'];
    $cartData = json_decode($_POST['cart_data'], true);
    $stay_checkout_id = !empty($_POST['stay_checkout_id']) ? (int)$_POST['stay_checkout_id'] : null;

    if ($stay_checkout_id && $_SESSION['user_role'] !== 'kasir') {
        $message = 'Hanya kasir yang dapat memproses pembayaran check-out Pet Hotel.';
        $message_type = 'error';
    } elseif (empty($cartData) || !is_array($cartData)) {
        $message = 'Keranjang belanja masih kosong!';
        $message_type = 'error';
    } else {
        $result = $penjualanModel->processCheckout(
            $id_customer,
            $id_user,
            $cartData,
            $stay_checkout_id,
            (float)($_POST['nominal_bayar'] ?? 0)
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

$customers = $customerModel->readAllForPOS()->fetchAll(PDO::FETCH_ASSOC);
$products = array_values(array_filter(
    $barangModel->readAll()->fetchAll(PDO::FETCH_ASSOC),
    function ($product) { return in_array($product['tipe_item'], ['retail', 'repack'], true); }
));
$stayCheckoutPayload = null;
if (!empty($_GET['stay_checkout']) && $_SESSION['user_role'] !== 'kasir') {
    $message = 'Hanya kasir yang dapat memproses pembayaran check-out Pet Hotel.';
    $message_type = 'error';
} elseif (!empty($_GET['stay_checkout'])) {
    $pendingStay = $inapModel->getPendingCheckout((int)$_GET['stay_checkout']);
    if ($pendingStay) {
        $hotelItemStmt = $db->query("SELECT id_barang, kode_barang, nama_barang FROM barang WHERE kode_barang = 'HOTEL-SETTLE' LIMIT 1");
        $hotelItem = $hotelItemStmt->fetch(PDO::FETCH_ASSOC);
        if ($hotelItem) {
            $stayCheckoutPayload = [
                'id_inap' => (int)$pendingStay['id_inap'],
                'id_customer' => (int)$pendingStay['id_customer'],
                'nama_customer' => $pendingStay['nama_customer'],
                'nama_hewan' => $pendingStay['nama_hewan'],
                'nomor_kandang' => $pendingStay['nomor_kandang'],
                'ukuran' => $pendingStay['ukuran'],
                'total_biaya_akhir' => (float)$pendingStay['total_biaya_akhir'],
                'uang_muka_dp' => (float)$pendingStay['uang_muka_dp'],
                'sisa_bayar' => (float)$pendingStay['sisa_bayar'],
                'hotel_item' => $hotelItem
            ];
        } else {
            $message = 'Item pelunasan Pet Hotel belum tersedia. Jalankan install.php untuk memperbarui database.';
            $message_type = 'error';
        }
    } else {
        $message = 'Tagihan inap tidak ditemukan atau sudah dibayar.';
        $message_type = 'error';
    }
}
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
                        <div class="breadcrumb-nav">Transaksi ritel produk hewan, repack eceran, dan pelunasan Pet Hotel</div>
                    </div>
                    <div class="page-actions">
                        <a href="repack.php" class="btn btn-sm btn-secondary"><i class="bi bi-boxes"></i> Konversi Repack</a>
                        <a href="booking_inap.php" class="btn btn-sm btn-secondary"><i class="bi bi-building"></i> Pet Hotel</a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

            <div class="pos-layout">
                <!-- SISI KIRI: KATALOG PRODUK -->
                <div class="catalog-container">
                    <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                        <input type="text" id="searchBox" class="form-control" placeholder="Cari nama barang, pakan, atau barcode..." oninput="searchProducts(this.value)">
                    </div>

                    <div class="filter-tabs">
                        <button type="button" class="filter-pill active" data-type="all" onclick="filterProducts('all')">Semua Produk</button>
                        <button type="button" class="filter-pill" data-type="retail" onclick="filterProducts('retail')">Pakan & Ritel</button>
                        <button type="button" class="filter-pill" data-type="repack" onclick="filterProducts('repack')">Repack Eceran</button>
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
                                         (($p['tipe_item'] === 'repack') ? 'warning' : 'secondary');
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
                                    Stok: <strong><?php echo $p['stok']; ?></strong> item
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- SISI KANAN: KERANJANG BELANJA & PEMBAYARAN -->
                <div class="cart-container">
                    <form method="POST" id="checkoutForm" onsubmit="return validateCheckout()">
                        <input type="hidden" name="action" id="checkoutActionInput" value="checkout">
                        <input type="hidden" name="cart_data" id="cartInput" value="[]">
                        <input type="hidden" name="stay_checkout_id" id="stayCheckoutInput" value="">
                        <input type="hidden" name="id_customer" id="checkoutCustomerInput" value="">

                        <?php if ($stayCheckoutPayload): ?>
                        <div class="alert alert-info" style="margin-bottom: 10px;">
                            <strong>Pelunasan Pet Hotel</strong><br>
                            Pelanggan: <?php echo htmlspecialchars($stayCheckoutPayload['nama_customer']); ?> ·
                            Anabul: <?php echo htmlspecialchars($stayCheckoutPayload['nama_hewan']); ?> ·
                            Kamar: <?php echo htmlspecialchars($stayCheckoutPayload['nomor_kandang']); ?>
                            <br>Sisa pembayaran: <strong><?php echo formatCurrency($stayCheckoutPayload['sisa_bayar']); ?></strong>
                            (total <?php echo formatCurrency($stayCheckoutPayload['total_biaya_akhir']); ?>, DP <?php echo formatCurrency($stayCheckoutPayload['uang_muka_dp']); ?>)
                        </div>
                        <?php endif; ?>

                        <!-- Data Pelanggan -->
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label for="id_customer" style="font-weight: 600; font-size: 0.85rem;">Pelanggan</label>
                            <select id="id_customer" required class="form-control" onchange="syncCheckoutAssignments()">
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?php echo (int)$c['id_customer']; ?>" <?php echo ((int)$c['id_customer'] === $walkInCustomerId) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['nama_customer']); ?><?php if ((int)$c['id_customer'] !== $walkInCustomerId): ?> (<?php echo htmlspecialchars($c['telepon']); ?>)<?php else: ?> — tanpa data pribadi<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color: #64748b;">Pembeli dapat tetap anonim atau memilih pelanggan tertentu untuk pencatatan riwayat.</small>
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
                                <input type="number" id="nominalBayar" name="nominal_bayar" class="form-control" placeholder="0" min="0" oninput="calculateChange()">
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
    <script src="assets/js/kasir.js?v=3.2"></script>
    <script>
        const stayCheckoutPayload = <?php echo json_encode($stayCheckoutPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        let activeStayCheckoutId = null;

        function syncCheckoutAssignments() {
            document.getElementById('checkoutCustomerInput').value = document.getElementById('id_customer').value;
        }

        function activateStayCheckout() {
            if (!stayCheckoutPayload) return;

            activeStayCheckoutId = String(stayCheckoutPayload.id_inap);
            document.getElementById('stayCheckoutInput').value = stayCheckoutPayload.id_inap;
            document.getElementById('id_customer').value = stayCheckoutPayload.id_customer;
            document.getElementById('id_customer').disabled = true;
            syncCheckoutAssignments();

            cart.push({
                id_barang: parseInt(stayCheckoutPayload.hotel_item.id_barang),
                kode_barang: stayCheckoutPayload.hotel_item.kode_barang,
                nama_barang: stayCheckoutPayload.hotel_item.nama_barang + ' — ' + stayCheckoutPayload.nama_hewan,
                tipe_item: 'kandang',
                harga_satuan: parseFloat(stayCheckoutPayload.sisa_bayar),
                jumlah: 1,
                stay_checkout_id: activeStayCheckoutId
            });
            renderCart();
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

        syncCheckoutAssignments();
        filterProducts('all');
        activateStayCheckout();
    </script>
</body>
</html>
