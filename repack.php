<?php
require_once 'config/config.php';
requireRole(['admin']);

require_once 'models/Repack.php';
require_once 'models/Barang.php';

$database = new Database();
$db = $database->getConnection();
$repackModel = new Repack($db);
$barangModel = new Barang($db);

$message = '';
$message_type = '';

// Handle Eksekusi Repack
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'execute_repack') {
        $id_karung = (int)$_POST['id_barang_karung_asal'];
        $id_eceran = (int)$_POST['id_barang_eceran_target'];
        $jml_karung = (int)$_POST['jumlah_karung_asal'];
        $hasil_pack = (int)$_POST['total_hasil_eceran_pack'];
        $id_admin = (int)$_SESSION['user_id'];

        $result = $repackModel->executeRepack($id_karung, $id_eceran, $jml_karung, $hasil_pack, $id_admin);

        if ($result['success']) {
            $susutFormatted = number_format($result['susut_gram'], 0, ',', '.') . ' gram';
            $message = "Proses konversi repack berhasil! Susut bahan: $susutFormatted.";
            $message_type = 'success';
        } else {
            $message = "Gagal memproses repack: " . $result['error'];
            $message_type = 'error';
        }
    }
}

$karungSources = $barangModel->getRepackSources();
$eceranTargets = $barangModel->getRepackTargets();
$logsList = $repackModel->readAllLogs();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konversi Repack Pakan - <?php echo APP_NAME; ?></title>
    <?php require_once 'head_inc.php'; ?>
    <style>
        .repack-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .calc-preview {
            background: #f1f5f9;
            border-left: 4px solid #0284c7;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .calc-val {
            font-size: 1.25rem;
            font-weight: bold;
            color: #0f172a;
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
                        <h1 class="page-title"><i class="bi bi-box-seam-fill"></i> Modul Konversi Repack Pakan</h1>
                        <div class="breadcrumb-nav">Pecah Karung Sak Pakan ke Eceran & Pencatatan Susut Gram</div>
                    </div>
                    <div class="page-actions">
                        <a href="barang.php" class="btn btn-sm btn-secondary"><i class="bi bi-tags"></i> Master Barang</a>
                        <a href="stok.php" class="btn btn-sm btn-secondary"><i class="bi bi-boxes"></i> Monitoring Stok</a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Form Eksekusi Repack -->
                <div class="repack-card">
                    <h2 style="margin-top: 0; font-size: 1.3rem; display: flex; align-items: center; gap: 8px;">
                        <span>📦</span> Form Pemecahan Pakan Karung (Bulk to Retail)
                    </h2>
                    <p style="color: #64748b; font-size: 0.95rem;">
                        Konversikan karung pakan besar ke kemasan eceran siap jual. Sistem akan otomatis memotong stok karung, menambah stok kemasan eceran, dan mencatat gram susut/tumpahan pakan.
                    </p>

                    <form method="POST" id="repackForm">
                        <input type="hidden" name="action" value="execute_repack">

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="id_karung">Pilih Pakan Karung Asal (Bulk Source)</label>
                                <select id="id_karung" name="id_barang_karung_asal" required class="form-control" onchange="updateCalculations()">
                                    <option value="">-- Pilih Karung Asal --</option>
                                    <?php foreach ($karungSources as $ks): ?>
                                        <option value="<?php echo $ks['id_barang']; ?>" 
                                                data-gram="<?php echo $ks['berat_gram']; ?>" 
                                                data-stok="<?php echo $ks['stok']; ?>">
                                            <?php echo htmlspecialchars($ks['nama_barang']); ?> (Stok: <?php echo $ks['stok']; ?> Karung • <?php echo $ks['berat_gram']; ?> gr)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="id_eceran">Pilih Kemasan Eceran Target (Retail Pack)</label>
                                <select id="id_eceran" name="id_barang_eceran_target" required class="form-control" onchange="updateCalculations()">
                                    <option value="">-- Pilih Kemasan Target --</option>
                                    <?php foreach ($eceranTargets as $et): ?>
                                        <option value="<?php echo $et['id_barang']; ?>" 
                                                data-gram="<?php echo $et['berat_gram']; ?>">
                                            <?php echo htmlspecialchars($et['nama_barang']); ?> (Isi: <?php echo $et['berat_gram']; ?> gr/pack • Stok Saat Ini: <?php echo $et['stok']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                            <div class="form-group">
                                <label for="jumlah_karung">Jumlah Karung yang Dipecah</label>
                                <input type="number" id="jumlah_karung" name="jumlah_karung_asal" required min="1" value="1" class="form-control" oninput="updateCalculations()">
                            </div>

                            <div class="form-group">
                                <label for="hasil_pack">Hasil Kemasan Eceran Aktual (Bungkus/Pack)</label>
                                <input type="number" id="hasil_pack" name="total_hasil_eceran_pack" required min="1" value="1" class="form-control" oninput="updateCalculations()">
                            </div>
                        </div>

                        <!-- Panel Kalkulasi Live -->
                        <div class="calc-preview">
                            <div>
                                <small style="color: #64748b; font-weight: bold;">TOTAL BAHAN BAKU</small>
                                <div class="calc-val" id="lblBahanBaku">0 gr</div>
                            </div>
                            <div>
                                <small style="color: #64748b; font-weight: bold;">ESTIMASI HASIL TEORITIS</small>
                                <div class="calc-val" id="lblTeoritis">0 pack</div>
                            </div>
                            <div>
                                <small style="color: #64748b; font-weight: bold;">TOTAL BERAT DIKEMAS</small>
                                <div class="calc-val" id="lblBeratHasil">0 gr</div>
                            </div>
                            <div>
                                <small style="color: #64748b; font-weight: bold;">SELISIH SUSUT (SHRINKAGE)</small>
                                <div class="calc-val" id="lblSusut" style="color: #ef4444;">0 gr</div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="padding: 12px 25px; font-size: 1rem;">
                            ⚙️ Eksekusi Konversi & Mutasi Stok
                        </button>
                    </form>
                </div>

                <!-- Riwayat Repack -->
                <div class="card">
                    <h2 style="font-size: 1.2rem; margin-top: 0;">📜 Riwayat Log Audit Repack Pakan</h2>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Waktu Eksekusi</th>
                                    <th>Karung Asal</th>
                                    <th>Jml Karung</th>
                                    <th>Kemasan Target</th>
                                    <th>Hasil Pack</th>
                                    <th>Susut (Shrinkage)</th>
                                    <th>Eksekutor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $hasLog = false;
                                while ($log = $logsList->fetch(PDO::FETCH_ASSOC)): 
                                    $hasLog = true;
                                ?>
                                <tr>
                                    <td><?php echo formatTanggalWaktu($log['tgl_eksekusi']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($log['nama_karung']); ?></strong> (<?php echo $log['gram_karung']; ?>g)</td>
                                    <td><span class="badge badge-info"><?php echo $log['jumlah_karung_asal']; ?> Karung</span></td>
                                    <td><strong><?php echo htmlspecialchars($log['nama_eceran']); ?></strong> (<?php echo $log['gram_eceran']; ?>g)</td>
                                    <td><span class="badge badge-success">+<?php echo $log['total_hasil_eceran_pack']; ?> Pack</span></td>
                                    <td>
                                        <span class="badge badge-danger">
                                            -<?php echo number_format($log['selisih_susut_gram'], 0, ',', '.'); ?> gr
                                        </span>
                                    </td>
                                    <td>👤 <?php echo htmlspecialchars($log['nama_admin']); ?></td>
                                </tr>
                                <?php endwhile; ?>

                                <?php if (!$hasLog): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 25px;">
                                        Belum ada riwayat konversi repack pakan.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function updateCalculations() {
            const karungSel = document.getElementById('id_karung');
            const eceranSel = document.getElementById('id_eceran');
            const jmlKarung = parseInt(document.getElementById('jumlah_karung').value) || 0;
            const hasilPack = parseInt(document.getElementById('hasil_pack').value) || 0;

            const karungOpt = karungSel.options[karungSel.selectedIndex];
            const eceranOpt = eceranSel.options[eceranSel.selectedIndex];

            const gramKarung = karungOpt ? (parseFloat(karungOpt.getAttribute('data-gram')) || 0) : 0;
            const gramEceran = eceranOpt ? (parseFloat(eceranOpt.getAttribute('data-gram')) || 0) : 0;

            const totalBahanBaku = gramKarung * jmlKarung;
            const estimasiTeoritis = gramEceran > 0 ? Math.floor(totalBahanBaku / gramEceran) : 0;
            const totalBeratHasil = gramEceran * hasilPack;
            const susut = Math.max(0, totalBahanBaku - totalBeratHasil);

            document.getElementById('lblBahanBaku').innerText = totalBahanBaku.toLocaleString('id-ID') + ' gr';
            document.getElementById('lblTeoritis').innerText = estimasiTeoritis.toLocaleString('id-ID') + ' pack';
            document.getElementById('lblBeratHasil').innerText = totalBeratHasil.toLocaleString('id-ID') + ' gr';
            document.getElementById('lblSusut').innerText = susut.toLocaleString('id-ID') + ' gr';
        }
    </script>
    <script src="assets/js/adminator.js"></script>
</body>
</html>
