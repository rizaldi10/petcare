<?php
require_once 'config/config.php';
requireRole(['admin', 'kasir']);

require_once 'models/Inap.php';
require_once 'models/Kandang.php';
require_once 'models/Hewan.php';
require_once 'models/Customer.php';
require_once 'models/Pengaturan.php';

$database = new Database();
$db = $database->getConnection();
$inapModel = new Inap($db);
$kandangModel = new Kandang($db);
$hewanModel = new Hewan($db);
$customerModel = new Customer($db);
$pengaturanModel = new Pengaturan($db);

$message = '';
$message_type = '';

$hourly_penalty_rate = (float)($pengaturanModel->get('tarif_denda_overstay_per_jam') ?? 15000);

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_booking') {
        $id_hewan = (int)$_POST['id_hewan'];
        $id_kandang = (int)$_POST['id_kandang'];
        $tgl_masuk = $_POST['tgl_masuk'];
        $tgl_estimasi = $_POST['tgl_estimasi_keluar'];
        $opsi_pakan = $_POST['opsi_pakan'];
        $dp = (float)($_POST['uang_muka_dp'] ?? 0);
        $status = $_POST['status_inap'] ?? 'Check-In';

        $id = $inapModel->createBooking([
            'id_hewan' => $id_hewan,
            'id_kandang' => $id_kandang,
            'tgl_masuk' => $tgl_masuk,
            'tgl_estimasi_keluar' => $tgl_estimasi,
            'opsi_pakan' => $opsi_pakan,
            'uang_muka_dp' => $dp,
            'status_inap' => $status
        ]);

        if ($id) {
            $message = "Reservasi berhasil disimpan dengan status: $status!";
            $message_type = 'success';
        } else {
            $message = "Gagal menyimpan reservasi pet hotel.";
            $message_type = 'error';
        }
    } elseif ($action === 'check_in') {
        $id = (int)$_POST['id_inap'];
        if ($inapModel->checkIn($id)) {
            $message = "Tamu anabul berhasil Check-In ke kamar hotel!";
            $message_type = 'success';
        } else {
            $message = "Gagal memproses check-in.";
            $message_type = 'error';
        }
    } elseif ($action === 'check_out') {
        $id = (int)$_POST['id_inap'];
        $total_bayar = (float)$_POST['total_biaya_akhir'];
        $denda = (float)$_POST['biaya_denda_overstay'];

        if ($inapModel->checkOut($id, $total_bayar, $denda)) {
            $message = "Check-Out berhasil! Kamar telah dibersihkan dan kembali tersedia.";
            $message_type = 'success';
        } else {
            $message = "Gagal memproses check-out.";
            $message_type = 'error';
        }
    } elseif ($action === 'cancel') {
        $id = (int)$_POST['id_inap'];
        if ($inapModel->cancelBooking($id)) {
            $message = "Reservasi berhasil dibatalkan.";
            $message_type = 'success';
        }
    }
}

$status_filter = $_GET['status'] ?? null;
$staysList = $inapModel->readAll($status_filter);
$availableCages = $kandangModel->readAll(true)->fetchAll(PDO::FETCH_ASSOC);
$pets = $hewanModel->readAll()->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservasi Pet Hotel - <?php echo APP_NAME; ?></title>
    <?php require_once 'head_inc.php'; ?>
    <style>
        .stay-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .stay-info h4 {
            margin: 0 0 5px 0;
            font-size: 1.15rem;
            color: #0f172a;
        }
        .overstay-badge {
            background: #fee2e2;
            color: #b91c1c;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-block;
            margin-top: 5px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
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
                        <h1 class="page-title"><i class="bi bi-building"></i> Front-Desk Pet Hotel & Boarding</h1>
                        <div class="breadcrumb-nav">Reservasi Kamar Kandang & Otomasi Denda Overstay Per Jam</div>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="openBookingModal()"><i class="bi bi-plus-lg"></i> Reservasi / Check-In Baru</button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: flex-start; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <a href="booking_inap.php" class="btn btn-sm <?php echo empty($status_filter) ? 'btn-primary' : 'btn-secondary'; ?>">Semua</a>
                    <a href="booking_inap.php?status=Check-In" class="btn btn-sm <?php echo ($status_filter === 'Check-In') ? 'btn-primary' : 'btn-secondary'; ?>">Sedang Inap (Aktif)</a>
                    <a href="booking_inap.php?status=Booking" class="btn btn-sm <?php echo ($status_filter === 'Booking') ? 'btn-primary' : 'btn-secondary'; ?>">Booking Menunggu Masuk</a>
                    <a href="booking_inap.php?status=Selesai" class="btn btn-sm <?php echo ($status_filter === 'Selesai') ? 'btn-primary' : 'btn-secondary'; ?>">Selesai Checkout</a>
                </div>

                <!-- List of Stays -->
                <div class="stays-container">
                    <?php 
                    $hasStay = false;
                    while ($stay = $staysList->fetch(PDO::FETCH_ASSOC)): 
                        $hasStay = true;
                        $now = time();
                        $estimasi = strtotime($stay['tgl_estimasi_keluar']);
                        $isOverstay = ($stay['status_inap'] === 'Check-In' && $now > $estimasi);
                        $overstayHours = $isOverstay ? ceil(($now - $estimasi) / 3600) : 0;
                        $dendaEst = $overstayHours * $hourly_penalty_rate;
                    ?>
                    <div class="stay-card" style="<?php echo $isOverstay ? 'border-left: 6px solid #ef4444;' : ''; ?>">
                        <div class="stay-info">
                            <h4>
                                <?php echo ($stay['spesies'] === 'Kucing') ? '🐱' : '🐶'; ?>
                                <?php echo htmlspecialchars($stay['nama_hewan']); ?> 
                                <span style="font-size: 0.9rem; font-weight: normal; color: #64748b;">(Pemilik: <?php echo htmlspecialchars($stay['nama_customer']); ?> - <?php echo htmlspecialchars($stay['customer_telepon']); ?>)</span>
                            </h4>
                            <div style="font-size: 0.9rem; color: #475569; display: flex; gap: 20px; flex-wrap: wrap; margin-top: 6px;">
                                <span>🏠 <strong>Kamar:</strong> <?php echo htmlspecialchars($stay['nomor_kandang']); ?> (<?php echo htmlspecialchars($stay['ukuran']); ?> - <?php echo formatCurrency($stay['tarif_per_malam']); ?>/malam)</span>
                                <span>🍱 <strong>Pakan:</strong> <?php echo str_replace('_', ' ', $stay['opsi_pakan']); ?></span>
                                <span>📥 <strong>Masuk:</strong> <?php echo formatTanggalWaktu($stay['tgl_masuk']); ?></span>
                                <span>📤 <strong>Est. Keluar:</strong> <?php echo formatTanggalWaktu($stay['tgl_estimasi_keluar']); ?></span>
                                <span>💰 <strong>DP:</strong> <?php echo formatCurrency($stay['uang_muka_dp']); ?></span>
                            </div>

                            <?php if ($isOverstay): ?>
                                <div class="overstay-badge">
                                    ⚠️ OVERSTAY TERDETEKSI: Telat <?php echo $overstayHours; ?> Jam (Estimasi Denda: +<?php echo formatCurrency($dendaEst); ?>)
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; gap: 8px; align-items: center;">
                            <span class="badge badge-<?php 
                                echo ($stay['status_inap'] === 'Check-In') ? 'success' : 
                                     (($stay['status_inap'] === 'Booking') ? 'warning' : 
                                     (($stay['status_inap'] === 'Selesai') ? 'secondary' : 'danger')); 
                            ?>">
                                <?php echo $stay['status_inap']; ?>
                            </span>

                            <?php if ($stay['status_inap'] === 'Booking'): ?>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="check_in">
                                    <input type="hidden" name="id_inap" value="<?php echo $stay['id_inap']; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">Check-In Sekarang</button>
                                </form>
                                <form method="POST" style="margin: 0;" onsubmit="return confirm('Batalkan booking ini?');">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="id_inap" value="<?php echo $stay['id_inap']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Batal</button>
                                </form>
                            <?php elseif ($stay['status_inap'] === 'Check-In'): ?>
                                <button class="btn btn-sm btn-info" onclick='openCheckoutModal(<?php echo $stay['id_inap']; ?>, <?php echo json_encode($stay); ?>)'>
                                    Proses Check-Out & Hitung Denda
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>

                    <?php if (!$hasStay): ?>
                    <div class="data-card">
                        <div class="empty-state">
                            <i class="bi bi-building-check" style="font-size: 42px; color: #cbd5e1;"></i>
                            <h4>Tidak Ada Data Rawat Inap</h4>
                            <p>Belum ada hewan anabul yang terdaftar pada filter reservasi ini.</p>
                            <div style="margin-top: 16px;">
                                <button type="button" class="btn btn-primary" onclick="openBookingModal()">
                                    <i class="bi bi-plus-lg"></i> Check-In / Booking Anabul
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Booking / Check-in Baru -->
    <div id="bookingModal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="modal-content" style="background: white; border-radius: 12px; width: 100%; max-width: 550px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Form Reservasi / Check-In Pet Hotel</h3>
                <button type="button" onclick="closeBookingModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_booking">

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="id_hewan">Pilih Hewan Tamu</label>
                    <select id="id_hewan" name="id_hewan" required class="form-control">
                        <option value="">-- Pilih Hewan --</option>
                        <?php foreach ($pets as $p): ?>
                            <option value="<?php echo $p['id_hewan']; ?>">
                                <?php echo htmlspecialchars($p['nama_hewan']); ?> (<?php echo htmlspecialchars($p['spesies']); ?> - Pemilik: <?php echo htmlspecialchars($p['nama_customer']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="id_kandang">Pilih Kamar / Kandang Tersedia</label>
                    <select id="id_kandang" name="id_kandang" required class="form-control">
                        <option value="">-- Pilih Kamar Ready --</option>
                        <?php foreach ($availableCages as $c): ?>
                            <option value="<?php echo $c['id_kandang']; ?>">
                                <?php echo htmlspecialchars($c['nomor_kandang']); ?> (Ukuran: <?php echo $c['ukuran']; ?> - <?php echo formatCurrency($c['tarif_per_malam']); ?>/malam)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label for="tgl_masuk">Waktu Masuk</label>
                        <input type="datetime-local" id="tgl_masuk" name="tgl_masuk" required value="<?php echo date('Y-m-d\TH:i'); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="tgl_estimasi_keluar">Estimasi Selesai / Keluar</label>
                        <input type="datetime-local" id="tgl_estimasi_keluar" name="tgl_estimasi_keluar" required value="<?php echo date('Y-m-d\TH:i', strtotime('+2 days')); ?>" class="form-control">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label for="opsi_pakan">Opsi Pakan Anabul</label>
                        <select id="opsi_pakan" name="opsi_pakan" class="form-control">
                            <option value="Bawa_Mandiri">Bawa Mandiri (Gratis)</option>
                            <option value="Disediakan_Toko">Disediakan Toko (+Rp 20.000/hari)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status_inap">Status Awal</label>
                        <select id="status_inap" name="status_inap" class="form-control">
                            <option value="Check-In">Langsung Check-In</option>
                            <option value="Booking">Booking Dulu</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="uang_muka_dp">Uang Muka / DP (Rp)</label>
                    <input type="number" id="uang_muka_dp" name="uang_muka_dp" min="0" step="10000" value="0" class="form-control">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="closeBookingModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Reservasi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Kalkulasi Check-Out & Denda Overstay -->
    <div id="checkoutModal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="modal-content" style="background: white; border-radius: 12px; width: 100%; max-width: 500px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0;">Kalkulasi Tagihan & Check-Out</h3>
                <button type="button" onclick="closeCheckoutModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            
            <div id="billSummary" style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 15px; font-size: 0.95rem; line-height: 1.6;">
                <!-- Dinamis diisi via JavaScript -->
            </div>

            <form method="POST" id="checkoutForm">
                <input type="hidden" name="action" value="check_out">
                <input type="hidden" name="id_inap" id="co_id_inap">
                <input type="hidden" name="biaya_denda_overstay" id="co_denda">
                <input type="hidden" name="total_biaya_akhir" id="co_total_akhir">

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="closeCheckoutModal()">Batal</button>
                    <button type="submit" class="btn btn-success">Pelunasan & Selesaikan Check-Out</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openBookingModal() {
            document.getElementById('bookingModal').style.display = 'flex';
        }
        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
        }
        function closeCheckoutModal() {
            document.getElementById('checkoutModal').style.display = 'none';
        }

        function openCheckoutModal(id, stay) {
            document.getElementById('co_id_inap').value = id;

            // Hitung durasi dan overstay
            const masuk = new Date(stay.tgl_masuk);
            const estimasi = new Date(stay.tgl_estimasi_keluar);
            const now = new Date();

            let diffDays = Math.ceil(Math.max(1, (estimasi - masuk) / (1000 * 60 * 60 * 24)));
            let biayaKamar = diffDays * parseFloat(stay.tarif_per_malam);
            let biayaPakan = (stay.opsi_pakan === 'Disediakan_Toko') ? (diffDays * 20000) : 0;

            let overstayHours = 0;
            let denda = 0;
            const hourlyRate = <?php echo $hourly_penalty_rate; ?>;

            if (now > estimasi) {
                overstayHours = Math.ceil((now - estimasi) / (1000 * 60 * 60));
                denda = overstayHours * hourlyRate;
            }

            const dp = parseFloat(stay.uang_muka_dp) || 0;
            const totalAkhir = biayaKamar + biayaPakan + denda;
            const sisaBayar = Math.max(0, totalAkhir - dp);

            document.getElementById('co_denda').value = denda;
            document.getElementById('co_total_akhir').value = totalAkhir;

            let html = `
                <div><strong>Nama Hewan:</strong> ${stay.nama_hewan} (${stay.spesies})</div>
                <div><strong>Kamar:</strong> ${stay.nomor_kandang} (${stay.ukuran})</div>
                <hr style="margin: 8px 0; border: none; border-top: 1px dashed #cbd5e1;">
                <div>Durasi Inap: <strong>${diffDays} Hari</strong> (${formatRp(biayaKamar)})</div>
                <div>Opsi Pakan: <strong>${stay.opsi_pakan.replace('_', ' ')}</strong> (${formatRp(biayaPakan)})</div>
            `;

            if (overstayHours > 0) {
                html += `<div style="color: #b91c1c; font-weight: bold;">Overstay: ${overstayHours} Jam (${formatRp(denda)})</div>`;
            } else {
                html += `<div style="color: #166534;">Overstay: Tepat Waktu (Rp 0)</div>`;
            }

            html += `
                <hr style="margin: 8px 0; border: none; border-top: 1px dashed #cbd5e1;">
                <div>Total Biaya Layanan: <strong>${formatRp(totalAkhir)}</strong></div>
                <div>Uang Muka (DP): -${formatRp(dp)}</div>
                <div style="font-size: 1.15rem; color: #0284c7; font-weight: bold; margin-top: 5px;">
                    Sisa Pelunasan: ${formatRp(sisaBayar)}
                </div>
            `;

            document.getElementById('billSummary').innerHTML = html;
            document.getElementById('checkoutModal').style.display = 'flex';
        }

        function formatRp(num) {
            return 'Rp ' + Number(num).toLocaleString('id-ID');
        }
    </script>
    <script src="assets/js/adminator.js"></script>
</body>
</html>
