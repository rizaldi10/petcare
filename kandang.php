<?php
require_once 'config/config.php';
requireRole(['admin']);

require_once 'models/Kandang.php';

$database = new Database();
$db = $database->getConnection();
$kandangModel = new Kandang($db);

$message = '';
$message_type = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        if ($kandangModel->create($_POST)) {
            $message = 'Fasilitas kandang berhasil ditambahkan!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menambahkan kandang. Pastikan nomor kandang unik.';
            $message_type = 'error';
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id_kandang'];
        if ($kandangModel->update($id, $_POST)) {
            $message = 'Data kandang berhasil diperbarui!';
            $message_type = 'success';
        } else {
            $message = 'Gagal memperbarui data kandang.';
            $message_type = 'error';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id_kandang'];
        if ($kandangModel->delete($id)) {
            $message = 'Kandang berhasil dihapus!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menghapus kandang (mungkin sedang terkait dengan data reservasi).';
            $message_type = 'error';
        }
    }
}

$kandangList = $kandangModel->readAll();
$stats = $kandangModel->getOccupancyStats();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kandang - <?php echo APP_NAME; ?></title>
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
                        <h1 class="page-title"><i class="bi bi-door-open-fill"></i> Fasilitas Kamar & Kandang</h1>
                        <div class="breadcrumb-nav">Monitoring Unit Kamar Pet Hotel, Kategori Ukuran & Tarif Sewa</div>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="openAddModal()"><i class="bi bi-plus-lg"></i> Tambah Kamar Baru</button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Stats Overview (Adminator Spec 4.3) -->
                <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr) !important; margin-bottom: 25px;">
                    <div class="stat-card card-primary">
                        <div class="stat-icon bg-primary-soft">
                            <i class="bi bi-door-closed-fill"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Total Unit Kamar</span>
                            <div class="stat-value"><?php echo $stats['total'] ?? 0; ?> Unit</div>
                        </div>
                    </div>
                    <div class="stat-card card-success">
                        <div class="stat-icon bg-success-soft">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Kamar Tersedia</span>
                            <div class="stat-value"><?php echo $stats['tersedia'] ?? 0; ?> Unit</div>
                        </div>
                    </div>
                    <div class="stat-card card-warning">
                        <div class="stat-icon bg-warning-soft">
                            <i class="bi bi-heart-fill"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Kamar Terisi (Tamu)</span>
                            <div class="stat-value"><?php echo $stats['terisi'] ?? 0; ?> Tamu</div>
                        </div>
                    </div>
                </div>

                <div class="data-card">
                    <div class="data-card-body" style="padding: 0;">

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No. Kamar</th>
                                <th>Ukuran</th>
                                <th>Tarif per Malam</th>
                                <th>Status Kamar</th>
                                <th>Tamu Aktif</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $kandangList->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['nomor_kandang']); ?></strong></td>
                                <td>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($row['ukuran']); ?></span>
                                </td>
                                <td><strong><?php echo formatCurrency($row['tarif_per_malam']); ?></strong> / malam</td>
                                <td>
                                    <?php if ($row['status_tersedia'] == 1): ?>
                                        <span class="badge badge-success">Tersedia (Ready)</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Terisi (Occupied)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['active_guest_name'])): ?>
                                        <strong>🐾 <?php echo htmlspecialchars($row['active_guest_name']); ?></strong>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">- Kosong -</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick='openEditModal(<?php echo json_encode($row); ?>)'>Edit</button>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Yakin ingin menghapus kandang ini?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_kandang" value="<?php echo $row['id_kandang']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
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

    <!-- Modal Form Tambah / Edit -->
    <div id="kandangModal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="modal-content" style="background: white; border-radius: 12px; width: 100%; max-width: 500px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 id="modalTitle" style="margin: 0;">Tambah Kamar Baru</h3>
                <button type="button" onclick="closeModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            <form method="POST" id="kandangForm">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id_kandang" id="id_kandang" value="">

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="nomor_kandang">Nomor / Kode Kamar</label>
                    <input type="text" id="nomor_kandang" name="nomor_kandang" required placeholder="Contoh: KD-S01, VIP-01" class="form-control">
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="ukuran">Ukuran Fasilitas</label>
                    <select id="ukuran" name="ukuran" required class="form-control">
                        <option value="Small">Small (Kucing / Anjing Kecil)</option>
                        <option value="Medium">Medium (Anjing Sedang / Kucing Multi)</option>
                        <option value="Large">Large (Anjing Besar)</option>
                        <option value="VIP">VIP (AC, Kamera, Ruang Luas)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="tarif_per_malam">Tarif per Malam (Rp)</label>
                    <input type="number" id="tarif_per_malam" name="tarif_per_malam" required min="0" step="5000" placeholder="50000" class="form-control">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="status_tersedia">Status Ketersediaan</label>
                    <select id="status_tersedia" name="status_tersedia" class="form-control">
                        <option value="1">Tersedia (Ready)</option>
                        <option value="0">Tidak Tersedia / Terisi</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmit">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').innerText = 'Tambah Kamar Baru';
            document.getElementById('formAction').value = 'create';
            document.getElementById('id_kandang').value = '';
            document.getElementById('nomor_kandang').value = '';
            document.getElementById('ukuran').value = 'Small';
            document.getElementById('tarif_per_malam').value = '50000';
            document.getElementById('status_tersedia').value = '1';
            document.getElementById('kandangModal').style.display = 'flex';
        }

        function openEditModal(data) {
            document.getElementById('modalTitle').innerText = 'Edit Data Kamar';
            document.getElementById('formAction').value = 'update';
            document.getElementById('id_kandang').value = data.id_kandang;
            document.getElementById('nomor_kandang').value = data.nomor_kandang;
            document.getElementById('ukuran').value = data.ukuran;
            document.getElementById('tarif_per_malam').value = data.tarif_per_malam;
            document.getElementById('status_tersedia').value = data.status_tersedia;
            document.getElementById('kandangModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('kandangModal').style.display = 'none';
        }
    </script>
    <script src="assets/js/adminator.js"></script>
</body>
</html>
