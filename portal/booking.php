<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/models/Customer.php';
require_once dirname(__DIR__) . '/models/Inap.php';
require_once dirname(__DIR__) . '/models/Kandang.php';

requireCustomerLogin();

$database = new Database();
$db = $database->getConnection();
$customerModel = new Customer($db);
$inapModel = new Inap($db);
$kandangModel = new Kandang($db);

$customer_id = $_SESSION['customer_id'];
$message = '';
$message_type = '';

// Handle Booking Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_type = $_POST['booking_type'] ?? 'hotel';

    if ($booking_type === 'hotel') {
        $id_hewan = (int)$_POST['id_hewan'];
        $id_kandang = (int)$_POST['id_kandang'];
        $tgl_masuk = $_POST['tgl_masuk'];
        $tgl_estimasi = $_POST['tgl_estimasi_keluar'];
        $opsi_pakan = $_POST['opsi_pakan'];

        $id = $inapModel->createBooking([
            'id_hewan' => $id_hewan,
            'id_kandang' => $id_kandang,
            'tgl_masuk' => $tgl_masuk,
            'tgl_estimasi_keluar' => $tgl_estimasi,
            'opsi_pakan' => $opsi_pakan,
            'uang_muka_dp' => 0,
            'status_inap' => 'Booking'
        ]);

        if ($id) {
            $message = 'Reservasi kamar pet hotel berhasil diajukan! Staf kami akan menyiapkan kamar sebelum kedatangan anabul.';
            $message_type = 'success';
        } else {
            $message = 'Gagal mengajukan reservasi.';
            $message_type = 'error';
        }
    } else {
        $message = 'Jenis reservasi tidak valid.';
        $message_type = 'error';
    }
}

$my_pets = $customerModel->getPets($customer_id);
$cages = $kandangModel->readAll(true)->fetchAll(PDO::FETCH_ASSOC);
$my_bookings = $inapModel->getCustomerBookings($customer_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Online - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/portal.css">
</head>
<body class="portal-body">
    <nav class="portal-nav">
        <a href="index.php" class="portal-logo">
            <span>🐾</span> <?php echo APP_NAME; ?>
        </a>
        <div class="portal-menu">
            <a href="index.php" class="portal-link">Beranda</a>
            <a href="my_pets.php" class="portal-link">Anabul Saya</a>
            <a href="booking.php" class="portal-link active">Booking Online</a>
            <a href="riwayat.php" class="portal-link">Riwayat Struk</a>
            <a href="index.php?action=logout" class="portal-link" style="color: #ef4444;">Keluar</a>
        </div>
    </nav>

    <div class="portal-container">
        <h2 style="margin: 0 0 5px 0; color: #0f172a;">Reservasi Kamar Hotel</h2>
        <p style="margin: 0 0 20px 0; color: #64748b;">Pesan fasilitas kamar inap anabul secara daring.</p>

        <?php if ($message): ?>
            <div style="background: <?php echo ($message_type === 'success') ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo ($message_type === 'success') ? '#166534' : '#991b1b'; ?>; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($my_pets)): ?>
            <div class="portal-card" style="text-align: center; padding: 30px;">
                <p style="color: #64748b;">Anda belum memiliki profil hewan peliharaan.</p>
                <a href="my_pets.php" class="portal-btn">+ Daftarkan Anabul Dulu</a>
            </div>
        <?php else: ?>
            <div style="max-width: 720px; margin: 0 auto;">
                <!-- Form Booking Pet Hotel -->
                <div class="portal-card">
                    <h3 style="margin-top: 0; color: #0284c7; display: flex; align-items: center; gap: 8px;">
                        <span>🏨</span> Booking Pet Hotel
                    </h3>
                    <p style="font-size: 0.85rem; color: #64748b;">Fasilitas penitipan bersih, ber-AC, dan pemantauan harian.</p>

                    <form method="POST">
                        <input type="hidden" name="booking_type" value="hotel">

                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 4px;">Pilih Anabul Tamu</label>
                            <select name="id_hewan" required style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                <?php foreach ($my_pets as $pet): ?>
                                    <option value="<?php echo $pet['id_hewan']; ?>">
                                        <?php echo htmlspecialchars($pet['nama_hewan']); ?> (<?php echo $pet['spesies']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 4px;">Pilih Kamar / Kandang Ready</label>
                            <select name="id_kandang" required style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                <?php foreach ($cages as $cg): ?>
                                    <option value="<?php echo $cg['id_kandang']; ?>">
                                        <?php echo htmlspecialchars($cg['nomor_kandang']); ?> (Ukuran: <?php echo $cg['ukuran']; ?> - <?php echo formatCurrency($cg['tarif_per_malam']); ?>/malam)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                            <div>
                                <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 4px;">Waktu Masuk</label>
                                <input type="datetime-local" name="tgl_masuk" required value="<?php echo date('Y-m-d\TH:i'); ?>" style="width: 100%; box-sizing: border-box; padding: 8px 10px; border-radius: 6px; border: 1px solid #cbd5e1;">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 4px;">Estimasi Keluar</label>
                                <input type="datetime-local" name="tgl_estimasi_keluar" required value="<?php echo date('Y-m-d\TH:i', strtotime('+2 days')); ?>" style="width: 100%; box-sizing: border-box; padding: 8px 10px; border-radius: 6px; border: 1px solid #cbd5e1;">
                            </div>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 4px;">Pilihan Pakan</label>
                            <select name="opsi_pakan" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                <option value="Bawa_Mandiri">Bawa Mandiri (Gratis)</option>
                                <option value="Disediakan_Toko">Disediakan Toko (+Rp 20.000/hari)</option>
                            </select>
                        </div>

                        <button type="submit" class="portal-btn" style="width: 100%;">Ajukan Booking Hotel</button>
                    </form>
                </div>

            </div>

            <!-- Riwayat Booking Saya -->
            <div class="portal-card" style="margin-top: 10px;">
                <h3 style="margin-top: 0;">📜 Riwayat Reservasi Hotel Saya</h3>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e2e8f0; text-align: left;">
                                <th style="padding: 8px;">Anabul</th>
                                <th style="padding: 8px;">Kamar</th>
                                <th style="padding: 8px;">Waktu Inap</th>
                                <th style="padding: 8px;">Opsi Pakan</th>
                                <th style="padding: 8px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_bookings as $b): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 10px 8px;"><strong><?php echo htmlspecialchars($b['nama_hewan']); ?></strong></td>
                                <td style="padding: 10px 8px;"><?php echo htmlspecialchars($b['nomor_kandang']); ?> (<?php echo $b['ukuran']; ?>)</td>
                                <td style="padding: 10px 8px;">
                                    <?php echo formatTanggalWaktu($b['tgl_masuk']); ?> s/d <?php echo formatTanggalWaktu($b['tgl_estimasi_keluar']); ?>
                                </td>
                                <td style="padding: 10px 8px;"><?php echo str_replace('_', ' ', $b['opsi_pakan']); ?></td>
                                <td style="padding: 10px 8px;">
                                    <span style="background: #e2e8f0; padding: 3px 8px; border-radius: 6px; font-weight: bold; font-size: 0.8rem;">
                                        <?php echo $b['status_inap']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($my_bookings)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #94a3b8; padding: 20px;">
                                    Belum ada riwayat reservasi hotel.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
