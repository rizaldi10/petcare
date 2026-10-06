<?php
require_once 'config/config.php';
requireRole(['admin', 'kasir', 'groomer']);

require_once 'models/Grooming.php';
require_once 'models/Hewan.php';
require_once 'models/User.php';
require_once 'models/Barang.php';

$database = new Database();
$db = $database->getConnection();
$groomingModel = new Grooming($db);
$hewanModel = new Hewan($db);
$userModel = new User($db);
$barangModel = new Barang($db);

$message = '';
$message_type = '';

$current_role = $_SESSION['user_role'];
$current_user_id = $_SESSION['user_id'];

// Filter groomer if role is groomer
$filter_groomer = ($current_role === 'groomer') ? $current_user_id : ($_GET['groomer'] ?? null);
$filter_status = $_GET['status'] ?? 'aktif'; // 'aktif' shows Antre, Mandi, Pengeringan, Siap_Ambil

// Handle Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $id = (int)$_POST['id_grooming'];
        $status = $_POST['status'];
        $catatan = $_POST['catatan_kondisi'] ?? null;

        if ($groomingModel->updateStatus($id, $status, $catatan)) {
            $message = "Status pengerjaan grooming berhasil diperbarui ke: $status";
            $message_type = 'success';
        } else {
            $message = "Gagal memperbarui status pengerjaan.";
            $message_type = 'error';
        }
    } elseif ($action === 'create_queue') {
        $id_hewan = (int)$_POST['id_hewan'];
        $id_groomer = (int)$_POST['id_groomer'];
        $id_layanan = (int)$_POST['id_barang_layanan'];
        $catatan = $_POST['catatan_kondisi'] ?? '';

        $queueId = $groomingModel->createQueue([
            'id_hewan' => $id_hewan,
            'id_groomer' => $id_groomer,
            'id_barang_layanan' => $id_layanan,
            'status_pengerjaan' => 'Antre',
            'catatan_kondisi' => $catatan
        ]);

        if ($queueId) {
            $message = "Hewan berhasil didaftarkan ke antrean grooming!";
            $message_type = 'success';
        } else {
            $message = "Gagal mendaftarkan antrean.";
            $message_type = 'error';
        }
    }
}

$queueList = $groomingModel->readAll($filter_status, $filter_groomer);
$groomers = $userModel->getGroomers();
$services = $barangModel->getServices()->fetchAll(PDO::FETCH_ASSOC);
$pets = $hewanModel->readAll()->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papan Antrean Grooming - <?php echo APP_NAME; ?></title>
    <?php require_once 'head_inc.php'; ?>
    <style>
        .queue-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .queue-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .queue-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px -3px rgba(0,0,0,0.1);
        }
        .queue-card.antre { border-top: 5px solid #f59e0b; }
        .queue-card.mandi { border-top: 5px solid #0284c7; }
        .queue-card.pengeringan { border-top: 5px solid #8b5cf6; }
        .queue-card.siap_ambil { border-top: 5px solid #10b981; }
        .queue-card.selesai { border-top: 5px solid #64748b; opacity: 0.85; }

        .pet-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .pet-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .pet-badge {
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 9999px;
            font-weight: 600;
        }
        .alert-allergy {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .status-pipeline {
            display: flex;
            gap: 5px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .btn-step {
            padding: 6px 12px;
            font-size: 0.8rem;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-step:hover {
            background: #e2e8f0;
        }
        .btn-step.active {
            background: #0284c7;
            color: white;
            border-color: #0284c7;
            font-weight: bold;
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
                        <h1 class="page-title"><i class="bi bi-scissors"></i> Papan Antrean & Pengerjaan Grooming</h1>
                        <div class="breadcrumb-nav">Monitoring Alur Pengerjaan & Perhitungan Otomatis Komisi Staf Groomer</div>
                    </div>
                    <div class="page-actions">
                        <?php if ($current_role === 'admin' || $current_role === 'kasir'): ?>
                        <button class="btn btn-primary" onclick="openAddQueueModal()"><i class="bi bi-plus-lg"></i> Masukkan Antrean Manual</button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Bar Filter Status -->
                <div style="display: flex; justify-content: flex-start; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
                    <a href="antrean_grooming.php?status=aktif" class="btn btn-sm <?php echo ($filter_status === 'aktif') ? 'btn-primary' : 'btn-secondary'; ?>">Sedang Berjalan (Aktif)</a>
                    <a href="antrean_grooming.php?status=" class="btn btn-sm <?php echo ($filter_status === '') ? 'btn-primary' : 'btn-secondary'; ?>">Semua Riwayat</a>
                    <a href="antrean_grooming.php?status=Selesai" class="btn btn-sm <?php echo ($filter_status === 'Selesai') ? 'btn-primary' : 'btn-secondary'; ?>">Selesai Hari Ini</a>
                </div>

                <!-- Grid Antrean Grooming -->
                <div class="queue-grid">
                    <?php 
                    $hasData = false;
                    while ($row = $queueList->fetch(PDO::FETCH_ASSOC)): 
                        $hasData = true;
                        $statusClass = strtolower(str_replace('_', '', $row['status_pengerjaan']));
                    ?>
                    <div class="queue-card <?php echo $statusClass; ?>">
                        <div>
                            <div class="pet-header">
                                <div>
                                    <div class="pet-name">
                                        <?php echo ($row['spesies'] === 'Kucing') ? '🐱' : (($row['spesies'] === 'Anjing') ? '🐶' : '🐾'); ?>
                                        <?php echo htmlspecialchars($row['nama_hewan']); ?>
                                    </div>
                                    <small style="color: #64748b;">
                                        <?php echo htmlspecialchars($row['spesies']); ?> • <?php echo htmlspecialchars($row['ras'] ?? 'Mix Breed'); ?>
                                    </small>
                                </div>
                                <span class="badge badge-<?php 
                                    echo ($row['status_pengerjaan'] === 'Antre') ? 'warning' : 
                                         (($row['status_pengerjaan'] === 'Mandi') ? 'info' : 
                                         (($row['status_pengerjaan'] === 'Pengeringan') ? 'primary' : 
                                         (($row['status_pengerjaan'] === 'Siap_Ambil') ? 'success' : 'secondary'))); 
                                ?>">
                                    <?php echo str_replace('_', ' ', $row['status_pengerjaan']); ?>
                                </span>
                            </div>

                            <!-- Peringatan Alergi / Karakter Khusus -->
                            <?php if (!empty($row['catatan_alergi'])): ?>
                            <div class="alert-allergy">
                                ⚠️ ALERGI: <?php echo htmlspecialchars($row['catatan_alergi']); ?>
                            </div>
                            <?php endif; ?>

                            <div style="font-size: 0.9rem; line-height: 1.5; color: #334155; margin-bottom: 12px;">
                                <div><strong>Layanan:</strong> <?php echo htmlspecialchars($row['nama_layanan']); ?></div>
                                <div><strong>Groomer:</strong> ✂️ <?php echo htmlspecialchars($row['nama_groomer']); ?></div>
                                <div><strong>Pemilik:</strong> <?php echo htmlspecialchars($row['nama_customer']); ?> (<?php echo htmlspecialchars($row['customer_telepon']); ?>)</div>
                                <div><strong>Waktu Masuk:</strong> <?php echo formatTanggalWaktu($row['waktu_masuk']); ?></div>
                                <?php if (!empty($row['catatan_kondisi'])): ?>
                                    <div style="margin-top: 5px; font-style: italic; background: #f8fafc; padding: 6px; border-radius: 6px;">
                                        📝 Catatan: <?php echo htmlspecialchars($row['catatan_kondisi']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tombol Perubahan Tahapan Status Antrean -->
                        <div>
                            <div style="font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">Update Progres Antrean:</div>
                            <div class="status-pipeline">
                                <?php 
                                $steps = ['Antre', 'Mandi', 'Pengeringan', 'Siap_Ambil', 'Selesai'];
                                foreach ($steps as $st): 
                                    $isCur = ($row['status_pengerjaan'] === $st);
                                ?>
                                    <form method="POST" style="margin: 0; display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id_grooming" value="<?php echo $row['id_grooming']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $st; ?>">
                                        <button type="submit" class="btn-step <?php echo $isCur ? 'active' : ''; ?>" <?php echo $isCur ? 'disabled' : ''; ?>>
                                            <?php echo str_replace('_', ' ', $st); ?>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>

                    <?php if (!$hasData): ?>
                    <div style="grid-column: 1 / -1;" class="data-card">
                        <div class="empty-state">
                            <i class="bi bi-scissors" style="font-size: 42px; color: #cbd5e1;"></i>
                            <h4>Tidak Ada Antrean Grooming Aktif</h4>
                            <p>Semua anabul telah selesai dirawat atau belum ada anabul yang didaftarkan ke antrean saat ini.</p>
                            <?php if ($current_role === 'admin' || $current_role === 'kasir'): ?>
                            <div style="margin-top: 16px;">
                                <button type="button" class="btn btn-primary" onclick="openAddQueueModal()">
                                    <i class="bi bi-plus-lg"></i> Daftarkan Anabul Sekarang
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Form Tambah Antrean Grooming -->
    <div id="addQueueModal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="modal-content" style="background: white; border-radius: 12px; width: 100%; max-width: 500px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">Tambah Hewan ke Antrean Grooming</h3>
                <button type="button" onclick="closeAddModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_queue">

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="id_hewan">Pilih Pasien Hewan</label>
                    <select id="id_hewan" name="id_hewan" required class="form-control">
                        <option value="">-- Pilih Hewan --</option>
                        <?php foreach ($pets as $p): ?>
                            <option value="<?php echo $p['id_hewan']; ?>">
                                <?php echo htmlspecialchars($p['nama_hewan']); ?> (<?php echo htmlspecialchars($p['spesies']); ?> - Pemilik: <?php echo htmlspecialchars($p['nama_customer']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="id_barang_layanan">Pilih Paket Layanan Grooming</label>
                    <select id="id_barang_layanan" name="id_barang_layanan" required class="form-control">
                        <option value="">-- Pilih Paket Layanan --</option>
                        <?php foreach ($services as $srv): ?>
                            <option value="<?php echo $srv['id_barang']; ?>">
                                <?php echo htmlspecialchars($srv['nama_barang']); ?> - <?php echo formatCurrency($srv['harga_jual']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="id_groomer">Tugaskan Groomer</label>
                    <select id="id_groomer" name="id_groomer" required class="form-control">
                        <?php foreach ($groomers as $g): ?>
                            <option value="<?php echo $g['id_user']; ?>">
                                ✂️ <?php echo htmlspecialchars($g['nama']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="catatan_kondisi">Catatan Kondisi Awal Hewan (Kutu, Jamur, Luka)</label>
                    <textarea id="catatan_kondisi" name="catatan_kondisi" rows="3" class="form-control" placeholder="Contoh: Kutu banyak di leher, kuku panjang, sensitif bagian ekor..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Daftarkan Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/adminator.js"></script>
    <script>
        function openAddQueueModal() {
            document.getElementById('addQueueModal').style.display = 'flex';
        }
        function closeAddModal() {
            document.getElementById('addQueueModal').style.display = 'none';
        }
    </script>
</body>
</html>
