# PANDUAN ARSITEKTUR & BLUEPRINT UNIVERSAL (SKELETON GUIDE)
> **Dokumen Pengunci Struktur Proyek**  
> Digunakan sebagai acuan baku agar basis proyek ini dapat dikembangkan menjadi berbagai macam sistem informasi (ERP, CRM, SRM, CMS, HRIS, dll.) tanpa merombak arsitektur dasar.

---

## 1. Tiga Pilar Fondasi Abadi (Universal Core)

Fondasi sistem ini menggunakan arsitektur **Native PHP Modern (OOP Model + Page Controller Pattern)** dengan PDO MySQL dan RBAC (*Role-Based Access Control*). Bagian ini **DIKUNCI** dan tidak perlu dirombak:

```
                  ┌─────────────────────────────────────┐
                  │      UNIVERSAL APP SKELETON         │
                  └──────────────────┬──────────────────┘
                                     │
      ┌──────────────────────────────┼──────────────────────────────┐
      ▼                              ▼                              ▼
[ 1. SECURITY & ACCESS ]      [ 2. DATA ENGINE ]           [ 3. UI SHELL & THEME ]
 • login.php / logout.php      • config/database.php        • sidebar.php (Nav dinamis)
 • config/config.php           • models/BaseModel (Pola)    • assets/css/style.css
 • requireLogin()              • PDO Prepared Statements    • assets/css/dynamic.php
 • requireRole(['...'])        • ACID Transactions          • pengaturan.php (Settings)
```

1. **Security & Session Gatekeeper (`config/config.php`)**:
   - `requireLogin()`: Memastikan sesi aktif sebelum mengakses halaman.
   - `requireRole(['role1', 'role2'])`: Membatasi hak akses per halaman sesuai peran pengguna.
   - `sanitizeInput($data)`: Mencegah celana keamanan XSS (*Cross-Site Scripting*).
2. **Database Engine & Model Pattern (`config/database.php` & `models/`)**:
   - Koneksi aman via PDO dengan penanganan `PDO::ERRMODE_EXCEPTION`.
   - Transaksi database ACID (`$db->beginTransaction()`, `$db->commit()`, `$db->rollBack()`).
3. **UI Shell & Dynamic Layout (`sidebar.php`, `dynamic.php`, `style.css`)**:
   - Generator variabel CSS dinamis (`dynamic.php`) yang membaca warna langsung dari tabel `pengaturan`.
   - Layout responsif terintegrasi dengan header profil dan sidebar dinamis berbasis role.

---

## 2. Aturan Baku Pengembangan: "1 Modul = 2 File"

Untuk menjaga struktur direktori tetap rapi dan tidak melebar ke mana-mana:
> **Setiap fitur / entitas baru HANYA membutuhkan 2 file:**
> 1. Satu file Model di dalam `models/{NamaEntitas}.php` (Logika Database).
> 2. Satu file Page Controller di root `{nama_entitas}.php` (Logika Tampilan & Form).
> 3. Ditambah 1 baris item link di `sidebar.php`.

### Template Standar Model (`models/ContohModel.php`)
```php
<?php
class ContohModel {
    private $conn;
    private $table_name = "nama_tabel";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " SET nama = :nama, deskripsi = :deskripsi";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nama', $data['nama']);
        $stmt->bindParam(':deskripsi', $data['deskripsi']);
        return $stmt->execute();
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " SET nama = :nama, deskripsi = :deskripsi WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nama', $data['nama']);
        $stmt->bindParam(':deskripsi', $data['deskripsi']);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>
```

### Template Standar Page Controller (`contoh.php`)
```php
<?php
require_once 'config/config.php';
requireRole(['admin']); // Tentukan role yang diizinkan

require_once 'models/ContohModel.php';

$database = new Database();
$db = $database->getConnection();
$model = new ContohModel($db);

$message = '';
$message_type = '';

// Handle POST request (Create / Update / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        if ($model->create($_POST)) {
            $message = 'Data berhasil disimpan!';
            $message_type = 'success';
        }
    }
}

// Ambil data untuk tabel
$items = $model->readAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Data - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dynamic.php">
</head>
<body>
    <div class="main-container">
        <?php 
        $role = $_SESSION['user_role'];
        require_once 'sidebar.php'; 
        ?>
        <main class="main-content">
            <header class="top-nav">
                <h1>Manajemen Data</h1>
                <div class="user-info">
                    <div class="user-details">
                        <div class="user-name"><?php echo $_SESSION['nama_lengkap']; ?></div>
                        <div class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></div>
                    </div>
                </div>
            </header>
            <div class="content">
                <!-- Konten halaman di sini -->
            </div>
        </main>
    </div>
</body>
</html>
```

---

## 3. Pola Database Universal (The 3 Table Archetypes)

Apapun topik sistem yang diminta dosen, seluruh rancangan database selalu bertumpu pada 3 pola tabel ini:

| Tipe Tabel | Karakteristik & Fungsi | Contoh di Proyek Saat Ini | Contoh di Sistem Lain |
| :--- | :--- | :--- | :--- |
| **1. Tabel Master (Entity Tunggal)** | Menyimpan data entitas independen (CRUD standar). | `users`, `customer`, `vendor`, `kategori_barang`, `barang`. | `leads` (CRM), `artikel` (CMS), `karyawan` (HRIS), `aset` (ERP). |
| **2. Tabel Transaksi Header (Parent)** | Menyimpan induk dokumen bisnis dengan kode unik dan status alur kerja. | `penjualan`, `pembelian`. | `deals` (CRM), `purchase_order` (SRM), `invoices` (ERP), `tickets` (Helpdesk). |
| **3. Tabel Transaksi Detail (Child / Multi-item)** | Menyimpan rincian item dalam 1 transaksi induk (`ON DELETE CASCADE`). | `detail_penjualan`, `detail_pembelian`. | `detail_invoice` (ERP), `po_items` (SRM), `deal_activities` (CRM). |

---

## 4. Matriks Pemetaan Sistem Lain Menggunakan Basis Ini

Gunakan tabel ini sebagai contekan cepat saat berpindah topik sistem:

| Komponen di Proyek Ini | Jika Diubah Jadi **CRM** | Jika Diubah Jadi **SRM** | Jika Diubah Jadi **CMS** | Jika Diubah Jadi **ERP Sederhana** |
| :--- | :--- | :--- | :--- | :--- |
| **Role Pengguna (`users`)** | `manager`, `sales`, `support` | `purchasing`, `auditor`, `vendor` | `admin`, `editor`, `author` | `direktur`, `finance`, `warehouse` |
| **Item Utama (`barang`)** | `leads` / Prospek Klien | Katalog Pengadaan Bahan | Berita / Artikel | Produk Jadi / Barang Aset |
| **Kategori (`kategori_barang`)** | Pipeline Stages (New, Won, Lost) | Kategori Pengadaan | Kategori Artikel | Kategori Departemen / Akun |
| **Entitas Pihak Ke-3 (`customer`/`vendor`)** | Kontak Klien / Partner | Vendor Terdaftar / Rekanan | Pembaca / Pengiklan | Klien / Supplier / Karyawan |
| **Transaksi (`penjualan` / `pembelian`)** | Follow-up Deals & Kontrak | Pengajuan Barang (PR/PO) | Penerbitan Halaman & Seri | Faktur Piutang & Pengeluaran Kas |
| **Laporan (`laporan_*.php`)** | Laporan Konversi Pipeline | Laporan Nilai Kontrak Vendor | Statistik Trafik Artikel | Laporan Arus Kas / Laba Rugi |

---

## 5. Checklist Adaptasi Cepat (15-30 Menit)

Saat dosen meminta berganti tema atau jenis sistem:

1. **Ubah Konfigurasi Dasar ([config/config.php](file:///c:/laragon/www/pos_minimart/config/config.php))**:
   - Sesuaikan `BASE_URL` dan `APP_NAME`.
2. **Ubah Profil & Warna Tema ([pengaturan.php](file:///c:/laragon/www/pos_minimart/pengaturan.php))**:
   - Ganti nama instansi/organisasi dan palet warna (CSS dynamic otomatis memperbarui tampilan).
3. **Sesuaikan Navigasi ([sidebar.php](file:///c:/laragon/www/pos_minimart/sidebar.php))**:
   - Ganti label menu dan ikon emoji sesuai topik baru.
4. **Terapkan Rumus 1 Modul = 2 File**:
   - Tambah/sesuaikan model di `models/` dan view di root.
   - Gunakan tabel `pengaturan` untuk konfigurasi kunci-nilai fleksibel.

---

## 6. Poin Argumen Konseptual untuk Dosen

Jika dosen menanyakan dasar rancangan arsitektur:
1. **Separation of Concerns (SoC)**: Logika database terisolasi di `models/`, routing dan penanganan form berada di controller, sedangkan visual terpusat di `sidebar.php` dan `dynamic.php`.
2. **ACID Transactions**: Seluruh transaksi yang melibatkan multi-tabel (header dan detail) dilindungi oleh PDO Transaction (`beginTransaction`, `commit`, `rollBack`) untuk mencegah data korup.
3. **Extensible RBAC**: Hak akses halaman dikawal secara konsisten dengan fungsi modular `requireRole()`, sehingga memudahkan pergantian peran sesuai kebutuhan bisnis tanpa mengubah sistem otentikasi.
