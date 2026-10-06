<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/models/Customer.php';
require_once dirname(__DIR__) . '/models/Hewan.php';

requireCustomerLogin();

$database = new Database();
$db = $database->getConnection();
$customerModel = new Customer($db);
$hewanModel = new Hewan($db);

$customer_id = $_SESSION['customer_id'];
$message = '';
$message_type = '';

// Handle Add / Edit Pet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $data = [
            'id_customer' => $customer_id,
            'nama_hewan' => sanitizeInput($_POST['nama_hewan']),
            'spesies' => $_POST['spesies'],
            'ras' => sanitizeInput($_POST['ras'] ?? ''),
            'tanggal_lahir' => !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : null,
            'berat_badan' => !empty($_POST['berat_badan']) ? (float)$_POST['berat_badan'] : null,
            'catatan_alergi' => sanitizeInput($_POST['catatan_alergi'] ?? ''),
            'catatan_kebiasaan' => sanitizeInput($_POST['catatan_kebiasaan'] ?? '')
        ];

        if ($hewanModel->create($data)) {
            $message = 'Profil anabul berhasil ditambahkan ke akun Anda!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menyimpan profil anabul.';
            $message_type = 'error';
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id_hewan'];
        // Verifikasi pemilik hewan
        $pet = $hewanModel->readOne($id);
        if ($pet && (int)$pet['id_customer'] === (int)$customer_id) {
            $data = [
                'nama_hewan' => sanitizeInput($_POST['nama_hewan']),
                'spesies' => $_POST['spesies'],
                'ras' => sanitizeInput($_POST['ras'] ?? ''),
                'tanggal_lahir' => !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : null,
                'berat_badan' => !empty($_POST['berat_badan']) ? (float)$_POST['berat_badan'] : null,
                'catatan_alergi' => sanitizeInput($_POST['catatan_alergi'] ?? ''),
                'catatan_kebiasaan' => sanitizeInput($_POST['catatan_kebiasaan'] ?? '')
            ];
            $hewanModel->update($id, $data);
            $message = 'Data anabul berhasil diperbarui!';
            $message_type = 'success';
        }
    }
}

$my_pets = $customerModel->getPets($customer_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Anabul Saya - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/portal.css">
</head>
<body class="portal-body">
    <nav class="portal-nav">
        <a href="index.php" class="portal-logo">
            <span>🐾</span> <?php echo APP_NAME; ?>
        </a>
        <div class="portal-menu">
            <a href="index.php" class="portal-link">Beranda</a>
            <a href="my_pets.php" class="portal-link active">Anabul Saya</a>
            <a href="booking.php" class="portal-link">Booking Online</a>
            <a href="track.php" class="portal-link">Pantau Perawatan (Live)</a>
            <a href="riwayat.php" class="portal-link">Riwayat Struk</a>
            <a href="index.php?action=logout" class="portal-link" style="color: #ef4444;">Keluar</a>
        </div>
    </nav>

    <div class="portal-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0; color: #0f172a;">Profil Anabul Peliharaan</h2>
                <p style="margin: 4px 0 0 0; color: #64748b;">Kelola data alergi, kebiasaan, dan fisik anabul agar perawatan optimal.</p>
            </div>
            <button class="portal-btn" onclick="openAddModal()">+ Tambah Anabul</button>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo ($message_type === 'success') ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo ($message_type === 'success') ? '#166534' : '#991b1b'; ?>; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
            <?php foreach ($my_pets as $pet): ?>
            <div class="portal-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div>
                        <h3 style="margin: 0; font-size: 1.3rem; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                            <?php echo ($pet['spesies'] === 'Kucing') ? '🐱' : '🐶'; ?>
                            <?php echo htmlspecialchars($pet['nama_hewan']); ?>
                        </h3>
                        <small style="color: #64748b;">
                            <?php echo htmlspecialchars($pet['spesies']); ?> • <?php echo htmlspecialchars($pet['ras'] ?? 'Mix Breed'); ?>
                        </small>
                    </div>
                    <button class="portal-btn portal-btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;" onclick='openEditModal(<?php echo json_encode($pet); ?>)'>
                        Edit
                    </button>
                </div>

                <div style="font-size: 0.9rem; color: #334155; line-height: 1.6;">
                    <div>⚖️ <strong>Berat Badan:</strong> <?php echo $pet['berat_badan'] ? $pet['berat_badan'] . ' kg' : 'Belum diisi'; ?></div>
                    <div>🎂 <strong>Tanggal Lahir:</strong> <?php echo $pet['tanggal_lahir'] ? formatTanggal($pet['tanggal_lahir']) : 'Belum diisi'; ?></div>
                    
                    <?php if (!empty($pet['catatan_alergi'])): ?>
                    <div style="background: #fef2f2; color: #991b1b; padding: 8px 12px; border-radius: 8px; font-size: 0.85rem; margin-top: 10px; font-weight: 600;">
                        ⚠️ Rekam Alergi: <?php echo htmlspecialchars($pet['catatan_alergi']); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($pet['catatan_kebiasaan'])): ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 12px; border-radius: 8px; font-size: 0.85rem; margin-top: 8px;">
                        📝 Karakter: <?php echo htmlspecialchars($pet['catatan_kebiasaan']); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 15px; border-top: 1px solid #f1f5f9; padding-top: 12px; display: flex; gap: 10px;">
                    <a href="booking.php?pet_id=<?php echo $pet['id_hewan']; ?>" class="portal-btn" style="flex: 1; text-align: center; font-size: 0.85rem; padding: 8px;">
                        Booking Kamar
                    </a>
                    <a href="track.php?pet_id=<?php echo $pet['id_hewan']; ?>" class="portal-btn portal-btn-secondary" style="flex: 1; text-align: center; font-size: 0.85rem; padding: 8px;">
                        Status Perawatan
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal Form Tambah / Edit Pet -->
    <div id="petModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; width: 100%; max-width: 500px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 id="modalTitle" style="margin: 0;">Tambah Anabul Baru</h3>
                <button type="button" onclick="closeModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id_hewan" id="id_hewan" value="">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Nama Hewan</label>
                        <input type="text" id="nama_hewan" name="nama_hewan" required style="width: 100%; box-sizing: border-box; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1;" placeholder="Nama anabul">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Spesies</label>
                        <select id="spesies" name="spesies" style="width: 100%; box-sizing: border-box; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                            <option value="Kucing">Kucing</option>
                            <option value="Anjing">Anjing</option>
                            <option value="Kelinci">Kelinci</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Ras</label>
                        <input type="text" id="ras" name="ras" style="width: 100%; box-sizing: border-box; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1;" placeholder="Persia, Poodle...">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Berat Badan (kg)</label>
                        <input type="number" step="0.1" id="berat_badan" name="berat_badan" style="width: 100%; box-sizing: border-box; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1;" placeholder="4.2">
                    </div>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Tanggal Lahir / Umur Perkiraan</label>
                    <input type="date" id="tanggal_lahir" name="tanggal_lahir" style="width: 100%; box-sizing: border-box; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px; color: #b91c1c;">Catatan Alergi / Makanan Pantangan</label>
                    <input type="text" id="catatan_alergi" name="catatan_alergi" style="width: 100%; box-sizing: border-box; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1;" placeholder="Contoh: Alergi ayam, sensitif shampo wangi">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Kebiasaan Khusus / Karakter</label>
                    <textarea id="catatan_kebiasaan" name="catatan_kebiasaan" rows="2" style="width: 100%; box-sizing: border-box; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1;" placeholder="Suka dielus dagu, agak penakut..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="portal-btn portal-btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="portal-btn">Simpan Anabul</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').innerText = 'Tambah Anabul Baru';
            document.getElementById('formAction').value = 'create';
            document.getElementById('id_hewan').value = '';
            document.getElementById('nama_hewan').value = '';
            document.getElementById('spesies').value = 'Kucing';
            document.getElementById('ras').value = '';
            document.getElementById('berat_badan').value = '';
            document.getElementById('tanggal_lahir').value = '';
            document.getElementById('catatan_alergi').value = '';
            document.getElementById('catatan_kebiasaan').value = '';
            document.getElementById('petModal').style.display = 'flex';
        }

        function openEditModal(pet) {
            document.getElementById('modalTitle').innerText = 'Edit Profil ' + pet.nama_hewan;
            document.getElementById('formAction').value = 'update';
            document.getElementById('id_hewan').value = pet.id_hewan;
            document.getElementById('nama_hewan').value = pet.nama_hewan;
            document.getElementById('spesies').value = pet.spesies;
            document.getElementById('ras').value = pet.ras || '';
            document.getElementById('berat_badan').value = pet.berat_badan || '';
            document.getElementById('tanggal_lahir').value = pet.tanggal_lahir || '';
            document.getElementById('catatan_alergi').value = pet.catatan_alergi || '';
            document.getElementById('catatan_kebiasaan').value = pet.catatan_kebiasaan || '';
            document.getElementById('petModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('petModal').style.display = 'none';
        }
    </script>
</body>
</html>
