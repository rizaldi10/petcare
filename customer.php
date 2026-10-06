<?php
require_once 'config/config.php';
requireRole(['admin', 'kasir', 'groomer']);

require_once 'models/Customer.php';
require_once 'models/Hewan.php';

$database = new Database();
$db = $database->getConnection();
$customerModel = new Customer($db);
$hewanModel = new Hewan($db);

$message = '';
$message_type = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_customer') {
        $id = $customerModel->create($_POST);
        if ($id) {
            // Jika ada hewan pertama yang didaftarkan sekaligus
            if (!empty($_POST['nama_hewan'])) {
                $hewanModel->create([
                    'id_customer' => $id,
                    'nama_hewan' => $_POST['nama_hewan'],
                    'spesies' => $_POST['spesies'] ?? 'Kucing',
                    'ras' => $_POST['ras'] ?? '',
                    'berat_badan' => $_POST['berat_badan'] ?? null,
                    'catatan_alergi' => $_POST['catatan_alergi'] ?? '',
                    'catatan_kebiasaan' => $_POST['catatan_kebiasaan'] ?? ''
                ]);
            }
            $message = 'Data pelanggan berhasil didaftarkan!';
            $message_type = 'success';
        } else {
            $message = 'Gagal mendaftarkan pelanggan.';
            $message_type = 'error';
        }
    } elseif ($action === 'add_pet') {
        $id_hewan = $hewanModel->create($_POST);
        if ($id_hewan) {
            $message = 'Anabul hewan peliharaan berhasil ditambahkan!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menambahkan profil hewan.';
            $message_type = 'error';
        }
    } elseif ($action === 'delete_customer') {
        $id = (int)$_POST['id_customer'];
        if ($customerModel->delete($id)) {
            $message = 'Data pelanggan berhasil dihapus.';
            $message_type = 'success';
        }
    } elseif ($action === 'delete_pet') {
        $id = (int)$_POST['id_hewan'];
        if ($hewanModel->delete($id)) {
            $message = 'Profil hewan berhasil dihapus.';
            $message_type = 'success';
        }
    }
}

$customersList = $customerModel->readAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Pelanggan & Hewan - <?php echo APP_NAME; ?></title>
    <?php require_once 'head_inc.php'; ?>
    <style>
        .customer-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04);
        }
        .customer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .pet-badge-list {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .pet-chip {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.9rem;
            min-width: 220px;
            flex: 1;
        }
        .allergy-alert {
            background: #fef2f2;
            color: #991b1b;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-top: 5px;
            font-weight: 600;
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
                        <h1 class="page-title"><i class="bi bi-people-fill"></i> Master Pelanggan & Anabul</h1>
                        <div class="breadcrumb-nav">Data Kontak Pemilik, Riwayat Vaksin & Catatan Khusus Alergi Hewan</div>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="openAddCustomerModal()"><i class="bi bi-person-plus-fill"></i> Registrasi Pelanggan & Anabul</button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Daftar Pelanggan dan Hewan -->
                <div class="customer-list">
                    <?php 
                    while ($c = $customersList->fetch(PDO::FETCH_ASSOC)): 
                        $pets = $customerModel->getPets($c['id_customer']);
                    ?>
                    <div class="customer-card">
                        <div class="customer-header">
                            <div>
                                <h3 style="margin: 0; font-size: 1.15rem; color: #0f172a;">
                                    👤 <?php echo htmlspecialchars($c['nama_customer']); ?>
                                </h3>
                                <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px;">
                                    📞 <?php echo htmlspecialchars($c['telepon']); ?> • 🏠 <?php echo htmlspecialchars($c['alamat'] ?? 'Alamat belum diisi'); ?>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button class="btn btn-sm btn-info" onclick="openAddPetModal(<?php echo $c['id_customer']; ?>, '<?php echo htmlspecialchars(addslashes($c['nama_customer'])); ?>')">
                                    + Tambah Anabul
                                </button>
                                <?php if ($role === 'admin'): ?>
                                <form method="POST" style="margin: 0;" onsubmit="return confirm('Hapus pelanggan beserta profil hewan miliknya?');">
                                    <input type="hidden" name="action" value="delete_customer">
                                    <input type="hidden" name="id_customer" value="<?php echo $c['id_customer']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Anabul List -->
                        <div>
                            <div style="font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 6px;">
                                Anabul Peliharaan (<?php echo count($pets); ?> ekor):
                            </div>
                            <?php if (count($pets) > 0): ?>
                                <div class="pet-badge-list">
                                    <?php foreach ($pets as $p): ?>
                                    <div class="pet-chip">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <strong style="font-size: 1rem; color: #0f172a;">
                                                <?php echo ($p['spesies'] === 'Kucing') ? '🐱' : (($p['spesies'] === 'Anjing') ? '🐶' : '🐾'); ?>
                                                <?php echo htmlspecialchars($p['nama_hewan']); ?>
                                            </strong>
                                            <span class="badge badge-info" style="font-size: 0.75rem;">
                                                <?php echo $p['spesies']; ?>
                                            </span>
                                        </div>
                                        <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px;">
                                            Ras: <?php echo htmlspecialchars($p['ras'] ?? '-'); ?> • Berat: <?php echo $p['berat_badan'] ? $p['berat_badan'].' kg' : '-'; ?>
                                        </div>

                                        <?php if (!empty($p['catatan_alergi'])): ?>
                                        <div class="allergy-alert">
                                            ⚠️ Alergi: <?php echo htmlspecialchars($p['catatan_alergi']); ?>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($p['catatan_kebiasaan'])): ?>
                                        <div style="font-size: 0.8rem; font-style: italic; color: #475569; margin-top: 4px;">
                                            Note: <?php echo htmlspecialchars($p['catatan_kebiasaan']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span style="font-size: 0.85rem; color: #94a3b8; font-style: italic;">Belum ada hewan terdaftar.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Registrasi Pelanggan & Hewan -->
    <div id="addCustomerModal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="modal-content" style="background: white; border-radius: 12px; width: 100%; max-width: 550px; padding: 25px; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0;">Registrasi Pelanggan Baru</h3>
                <button type="button" onclick="closeCustomerModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_customer">

                <h4 style="margin: 0 0 10px 0; color: #0284c7; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;">Data Pemilik</h4>
                <div class="form-group" style="margin-bottom: 10px;">
                    <label>Nama Lengkap Pelanggan</label>
                    <input type="text" name="nama_customer" required class="form-control" placeholder="Nama pemilik">
                </div>
                <div class="form-group" style="margin-bottom: 10px;">
                    <label>Nomor Telepon / WhatsApp (Juga login portal)</label>
                    <input type="text" name="telepon" required class="form-control" placeholder="08xxxxxxxxxx">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Alamat Tinggal</label>
                    <textarea name="alamat" rows="2" class="form-control" placeholder="Alamat pelanggan..."></textarea>
                </div>

                <h4 style="margin: 15px 0 10px 0; color: #0284c7; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;">Data Anabul Pertama (Opsional)</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div class="form-group">
                        <label>Nama Hewan</label>
                        <input type="text" name="nama_hewan" class="form-control" placeholder="Nama anabul">
                    </div>
                    <div class="form-group">
                        <label>Spesies</label>
                        <select name="spesies" class="form-control">
                            <option value="Kucing">Kucing</option>
                            <option value="Anjing">Anjing</option>
                            <option value="Kelinci">Kelinci</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div class="form-group">
                        <label>Ras Hewan</label>
                        <input type="text" name="ras" class="form-control" placeholder="Contoh: Persia, Pomeranian">
                    </div>
                    <div class="form-group">
                        <label>Berat Badan (kg)</label>
                        <input type="number" step="0.1" name="berat_badan" class="form-control" placeholder="4.5">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 10px;">
                    <label style="color: #b91c1c;">Catatan Alergi / Sensitivitas</label>
                    <input type="text" name="catatan_alergi" class="form-control" placeholder="Alergi ayam, seafood, shampo tertentu...">
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Catatan Kebiasaan / Karakter</label>
                    <input type="text" name="catatan_kebiasaan" class="form-control" placeholder="Ramah, penakut bunyi keras...">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="closeCustomerModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Registrasi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tambah Pet Khusus -->
    <div id="addPetModal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="modal-content" style="background: white; border-radius: 12px; width: 100%; max-width: 500px; padding: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0;">Tambah Anabul untuk: <span id="petOwnerName"></span></h3>
                <button type="button" onclick="closePetModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_pet">
                <input type="hidden" name="id_customer" id="pet_id_customer">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div class="form-group">
                        <label>Nama Hewan</label>
                        <input type="text" name="nama_hewan" required class="form-control" placeholder="Nama anabul">
                    </div>
                    <div class="form-group">
                        <label>Spesies</label>
                        <select name="spesies" class="form-control">
                            <option value="Kucing">Kucing</option>
                            <option value="Anjing">Anjing</option>
                            <option value="Kelinci">Kelinci</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div class="form-group">
                        <label>Ras</label>
                        <input type="text" name="ras" class="form-control" placeholder="Persia, Beagle...">
                    </div>
                    <div class="form-group">
                        <label>Berat (kg)</label>
                        <input type="number" step="0.1" name="berat_badan" class="form-control" placeholder="4.2">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 10px;">
                    <label style="color: #b91c1c;">Catatan Alergi</label>
                    <input type="text" name="catatan_alergi" class="form-control" placeholder="Alergi khusus...">
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Catatan Karakter</label>
                    <input type="text" name="catatan_kebiasaan" class="form-control" placeholder="Karakter...">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="closePetModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambahkan Anabul</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddCustomerModal() {
            document.getElementById('addCustomerModal').style.display = 'flex';
        }
        function closeCustomerModal() {
            document.getElementById('addCustomerModal').style.display = 'none';
        }
        function openAddPetModal(custId, ownerName) {
            document.getElementById('pet_id_customer').value = custId;
            document.getElementById('petOwnerName').innerText = ownerName;
            document.getElementById('addPetModal').style.display = 'flex';
        }
        function closePetModal() {
            document.getElementById('addPetModal').style.display = 'none';
        }
    </script>
    <script src="assets/js/adminator.js"></script>
</body>
</html>
