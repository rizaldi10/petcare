<?php
require_once 'config/config.php';
requireRole(['admin', 'kasir']);

require_once 'models/Penjualan.php';
require_once 'models/Customer.php';
require_once 'models/Pengaturan.php';

$database = new Database();
$db = $database->getConnection();

$penjualanModel = new Penjualan($db);
$customerModel = new Customer($db);
$pengaturanModel = new Pengaturan($db);

$penjualan_id = (int)($_GET['id'] ?? 0);
$trx = $penjualanModel->readOne($penjualan_id);

if (!$trx) {
    header('Location: penjualan.php');
    exit();
}

$nama_toko = $pengaturanModel->get('nama_toko') ?? APP_NAME;
$alamat_toko = $pengaturanModel->get('alamat_toko') ?? 'Jl. Pet Kingdom No. 45';
$telepon_toko = $pengaturanModel->get('telepon_toko') ?? '0812-3456-7890';
$footer_struk = $pengaturanModel->get('footer_struk') ?? 'Terima kasih telah mempercayakan anabul kepada kami!';

$details = $penjualanModel->getDetailPenjualan($penjualan_id);
$hasGrooming = false;
$itemsList = [];
while ($d = $details->fetch(PDO::FETCH_ASSOC)) {
    $itemsList[] = $d;
    if ($d['tipe_item'] === 'jasa') {
        $hasGrooming = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran - <?php echo htmlspecialchars($trx['no_faktur']); ?></title>
    <link rel="stylesheet" href="assets/css/print.css">
</head>
<body class="print-receipt">
    <div class="no-print" style="max-width: 320px; margin: 10px auto; display: flex; gap: 8px;">
        <a href="penjualan.php" style="flex: 1; text-align: center; background: #64748b; color: white; padding: 10px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold;">
            ← Kembali ke Kasir
        </a>
        <button onclick="window.print()" style="flex: 1; background: #0284c7; color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: bold;">
            🖨️ Cetak Struk
        </button>
    </div>

    <div class="receipt-container">
        <div class="receipt-header">
            <h2>🐾 <?php echo htmlspecialchars($nama_toko); ?></h2>
            <p><?php echo htmlspecialchars($alamat_toko); ?></p>
            <p>Telp: <?php echo htmlspecialchars($telepon_toko); ?></p>
        </div>

        <div class="receipt-meta">
            <div><strong>No. Faktur:</strong> <?php echo htmlspecialchars($trx['no_faktur']); ?></div>
            <div><strong>Tanggal:</strong> <?php echo formatTanggal($trx['tgl_penjualan']); ?> <?php echo date('H:i'); ?></div>
            <div><strong>Kasir:</strong> <?php echo htmlspecialchars($trx['kasir']); ?></div>
            <div><strong>Pelanggan:</strong> <?php echo htmlspecialchars($trx['nama_customer']); ?> (<?php echo htmlspecialchars($trx['customer_telepon']); ?>)</div>
        </div>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Item Layanan / Produk</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itemsList as $it): ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($it['nama_barang']); ?>
                        <?php if ($it['tipe_item'] === 'jasa'): ?>
                            <br><small><em>(Paket Grooming Salon)</em></small>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;"><?php echo $it['jumlah']; ?></td>
                    <td style="text-align: right;"><?php echo number_format($it['subtotal'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="receipt-totals">
            <div class="row grand-total">
                <span>TOTAL BAYAR:</span>
                <span><?php echo formatCurrency($trx['total_bayar']); ?></span>
            </div>
            <div class="row">
                <span>METODE:</span>
                <span>TUNAI / LUNAS</span>
            </div>
        </div>

        <?php if ($hasGrooming): ?>
        <div class="pickup-ticket">
            <div class="ticket-title">TIKET PENGAMBILAN ANABUL</div>
            <div class="ticket-number">#<?php echo substr($trx['no_faktur'], -4); ?></div>
            <div style="font-size: 10px; margin-top: 4px;">
                Tunjukkan nomor tiket ini kepada staf kasir/groomer saat menjemput anabul Anda.
            </div>
        </div>
        <?php endif; ?>

        <div class="receipt-footer">
            <p><?php echo htmlspecialchars($footer_struk); ?></p>
            <p style="font-size: 10px; color: #555;">Simpan struk ini sebagai bukti pembayaran yang sah.</p>
        </div>
    </div>

    <script>
        // Auto print jika dibuka langsung dari checkout
        window.addEventListener('load', () => {
            // Uncomment jika ingin auto-print saat halaman terbuka:
            // window.print();
        });
    </script>
</body>
</html>
