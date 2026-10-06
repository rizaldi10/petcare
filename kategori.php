<?php
require_once 'config/config.php';
requireRole(['admin']);

require_once 'models/KategoriBarang.php';

$database = new Database();
$db = $database->getConnection();

$kategori = new KategoriBarang($db);

$message = '';
$message_type = '';

// Handle form submission
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $kategori->nama_kategori = sanitizeInput($_POST['nama_kategori']);
                $kategori->deskripsi = sanitizeInput($_POST['deskripsi']);

                if ($kategori->create()) {
                    $message = 'Kategori barang berhasil ditambahkan!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menambahkan kategori barang!';
                    $message_type = 'error';
                }
                break;

            case 'update':
                $kategori->id = sanitizeInput($_POST['id']);
                $kategori->nama_kategori = sanitizeInput($_POST['nama_kategori']);
                $kategori->deskripsi = sanitizeInput($_POST['deskripsi']);

                if ($kategori->update()) {
                    $message = 'Kategori barang berhasil diperbarui!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal memperbarui kategori barang!';
                    $message_type = 'error';
                }
                break;

            case 'delete':
                $kategori->id = sanitizeInput($_POST['id']);
                if ($kategori->delete()) {
                    $message = 'Kategori barang berhasil dihapus!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menghapus kategori barang!';
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Get all kategori
$stmt = $kategori->readAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Barang - <?php echo APP_NAME; ?></title>
    <?php require_once 'head_inc.php'; ?>
</head>
<body>
    <div class="main-container">
        <!-- Sidebar -->
        <?php 
        $role = $_SESSION['user_role'];
        require_once 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php require_once 'topbar.php'; ?>

            <!-- Content -->
            <div class="content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title"><i class="bi bi-folder2-open"></i> Kategori Produk & Layanan</h1>
                        <div class="breadcrumb-nav">Klasifikasi Pakan, Aksesoris, Jasa Grooming & Kamar</div>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="data-card" style="margin-bottom: 25px;">
                    <div class="data-card-body">
                        <h2 style="font-size: 1.15rem; margin-top: 0;">Tambah Kategori Baru</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nama_kategori">Nama Kategori</label>
                                <input type="text" id="nama_kategori" name="nama_kategori" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="deskripsi">Deskripsi</label>
                            <textarea id="deskripsi" name="deskripsi" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Tambah Kategori</button>
                    </form>
                    </div>
                </div>

                <!-- Data Kategori Table -->
                <div class="data-card">
                    <div class="data-card-body" style="padding: 0;">
                    <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Kategori</th>
                                <th>Deskripsi</th>
                                <th>Tanggal Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['nama_kategori']; ?></td>
                                <td><?php echo $row['deskripsi'] ?: '-'; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <button onclick="editKategori(<?php echo htmlspecialchars(json_encode($row)); ?>)" 
                                            class="btn btn-warning btn-sm">Edit</button>
                                    <button onclick="deleteKategori(<?php echo $row['id']; ?>)" 
                                            class="btn btn-danger btn-sm">Hapus</button>
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

    <!-- Edit Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 500px;">
            <h2>Edit Kategori</h2>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label for="edit_nama_kategori">Nama Kategori</label>
                    <input type="text" id="edit_nama_kategori" name="nama_kategori" required>
                </div>

                <div class="form-group">
                    <label for="edit_deskripsi">Deskripsi</label>
                    <textarea id="edit_deskripsi" name="deskripsi" rows="3"></textarea>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editKategori(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_nama_kategori').value = data.nama_kategori;
            document.getElementById('edit_deskripsi').value = data.deskripsi;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function deleteKategori(id) {
            if (confirm('Apakah Anda yakin ingin menghapus kategori ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        document.getElementById('editModal').onclick = function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        }
    </script>
    <script src="assets/js/adminator.js"></script>
</body>
</html>
