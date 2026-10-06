<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/models/Customer.php';
require_once dirname(__DIR__) . '/models/Grooming.php';

requireCustomerLogin();

$database = new Database();
$db = $database->getConnection();
$customerModel = new Customer($db);
$groomingModel = new Grooming($db);

$customer_id = $_SESSION['customer_id'];
$groomings = $groomingModel->getTrackByCustomer($customer_id);

$steps = [
    'Antre' => ['label' => 'Antre', 'icon' => '⏳', 'desc' => 'Menunggu antrean bilik mandi'],
    'Mandi' => ['label' => 'Mandi', 'icon' => '🛁', 'desc' => 'Sedang dimandikan & dibersihkan'],
    'Pengeringan' => ['label' => 'Pengeringan', 'icon' => '💨', 'desc' => 'Blower, pengeringan bulu & sisir'],
    'Siap_Ambil' => ['label' => 'Siap Ambil', 'icon' => '✨', 'desc' => 'Selesai grooming, siap dijemput!'],
    'Selesai' => ['label' => 'Selesai', 'icon' => '🎉', 'desc' => 'Telah dijemput pemilik']
];

function getStepIndex($status) {
    $order = ['Antre' => 0, 'Mandi' => 1, 'Pengeringan' => 2, 'Siap_Ambil' => 3, 'Selesai' => 4];
    return $order[$status] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Tracking Perawatan - <?php echo APP_NAME; ?></title>
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
            <a href="booking.php" class="portal-link">Booking Online</a>
            <a href="track.php" class="portal-link active">Pantau Perawatan (Live)</a>
            <a href="riwayat.php" class="portal-link">Riwayat Struk</a>
            <a href="index.php?action=logout" class="portal-link" style="color: #ef4444;">Keluar</a>
        </div>
    </nav>

    <div class="portal-container" id="liveTrackContainer">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0; color: #0f172a;">Pelacak Status Grooming Real-Time</h2>
                <p style="margin: 4px 0 0 0; color: #64748b;">Halaman ini otomatis memperbarui status saat staf salon memperbarui progres.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #10b981; animation: pulse 1.5s infinite;"></span>
                <span style="font-size: 0.85rem; color: #10b981; font-weight: bold;">Live Tracker Aktif</span>
            </div>
        </div>

        <?php if (!empty($groomings)): ?>
            <?php foreach ($groomings as $g): 
                $curIdx = getStepIndex($g['status_pengerjaan']);
                $isCompleted = ($g['status_pengerjaan'] === 'Selesai');
                $isReady = ($g['status_pengerjaan'] === 'Siap_Ambil');
            ?>
            <div class="portal-card" style="<?php echo $isReady ? 'border: 2px solid #10b981; background: #f0fdf4;' : ''; ?>">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h3 style="margin: 0; font-size: 1.3rem; color: #0f172a;">
                            <?php echo ($g['spesies'] === 'Kucing') ? '🐱' : '🐶'; ?>
                            <?php echo htmlspecialchars($g['nama_hewan']); ?>
                        </h3>
                        <div style="font-size: 0.9rem; color: #64748b; margin-top: 4px;">
                            Layanan: <strong><?php echo htmlspecialchars($g['nama_layanan']); ?></strong> • Groomer: ✂️ <?php echo htmlspecialchars($g['nama_groomer']); ?>
                        </div>
                    </div>

                    <div>
                        <?php if ($isReady): ?>
                            <span style="background: #10b981; color: white; padding: 6px 12px; border-radius: 9999px; font-weight: bold; font-size: 0.85rem;">
                                ✨ SIAP DIJEMPUT!
                            </span>
                        <?php else: ?>
                            <span style="background: #0284c7; color: white; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 600;">
                                Tahap: <?php echo str_replace('_', ' ', $g['status_pengerjaan']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Visual Stepper Progress Bar -->
                <div class="stepper">
                    <?php 
                    $stepKeys = array_keys($steps);
                    foreach ($stepKeys as $idx => $stKey): 
                        $info = $steps[$stKey];
                        $stepClass = '';
                        if ($idx < $curIdx) {
                            $stepClass = 'completed';
                        } elseif ($idx === $curIdx) {
                            $stepClass = 'active';
                        }
                    ?>
                    <div class="step-item <?php echo $stepClass; ?>">
                        <div class="step-circle">
                            <?php echo $info['icon']; ?>
                        </div>
                        <div class="step-label">
                            <?php echo $info['label']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div style="background: white; border: 1px solid #e2e8f0; padding: 12px 16px; border-radius: 8px; font-size: 0.85rem; color: #475569; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div>
                        ⏱️ Masuk Antrean: <strong><?php echo formatTanggalWaktu($g['waktu_masuk']); ?></strong>
                    </div>
                    <?php if (!empty($g['waktu_selesai'])): ?>
                    <div>
                        ✅ Waktu Selesai: <strong><?php echo formatTanggalWaktu($g['waktu_selesai']); ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($g['catatan_kondisi'])): ?>
                    <div style="width: 100%; margin-top: 4px; color: #0284c7;">
                        📝 Catatan Groomer: <em>"<?php echo htmlspecialchars($g['catatan_kondisi']); ?>"</em>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="portal-card" style="text-align: center; padding: 40px;">
                <div style="font-size: 3rem; margin-bottom: 10px;">🛁</div>
                <h3 style="margin: 0; color: #64748b;">Belum Ada Antrean Perawatan Aktif</h3>
                <p style="color: #94a3b8; margin: 8px 0 20px 0;">Ketika anabul Anda dimasukkan ke antrean grooming salon di kasir, progres pengerjaannya akan muncul di sini.</p>
                <a href="booking.php" class="portal-btn">Booking Grooming Sekarang</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/tracker.js"></script>
    <script>
        initLiveTracker(8000); // Polling otomatis setiap 8 detik
    </script>
</body>
</html>
