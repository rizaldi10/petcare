CREATE DATABASE IF NOT EXISTS petcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE petcare_db;

-- 1. Pengguna Internal (RBAC)
CREATE TABLE IF NOT EXISTS users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kasir', 'groomer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Entitas Pelanggan (Pemilik)
CREATE TABLE IF NOT EXISTS customer (
    id_customer INT AUTO_INCREMENT PRIMARY KEY,
    nama_customer VARCHAR(100) NOT NULL,
    telepon VARCHAR(20) NOT NULL,
    alamat TEXT NULL,
    password VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Entitas Hewan Peliharaan (Child of Customer)
CREATE TABLE IF NOT EXISTS hewan_peliharaan (
    id_hewan INT AUTO_INCREMENT PRIMARY KEY,
    id_customer INT NOT NULL,
    nama_hewan VARCHAR(100) NOT NULL,
    spesies ENUM('Kucing', 'Anjing', 'Kelinci', 'Lainnya') NOT NULL,
    ras VARCHAR(100) NULL,
    tanggal_lahir DATE NULL,
    berat_badan DECIMAL(5,2) NULL,
    catatan_alergi TEXT NULL,
    catatan_kebiasaan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_customer) REFERENCES customer(id_customer) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Entitas Fasilitas Kandang Pet Hotel
CREATE TABLE IF NOT EXISTS kandang (
    id_kandang INT AUTO_INCREMENT PRIMARY KEY,
    nomor_kandang VARCHAR(20) NOT NULL UNIQUE,
    ukuran ENUM('Small', 'Medium', 'Large', 'VIP') NOT NULL,
    tarif_per_malam DECIMAL(12,2) NOT NULL,
    status_tersedia TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- 5. Kategori Barang & Jasa
CREATE TABLE IF NOT EXISTS kategori_barang (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- 6. Master Produk, Jasa, & Fasilitas
CREATE TABLE IF NOT EXISTS barang (
    id_barang INT AUTO_INCREMENT PRIMARY KEY,
    id_kategori INT NOT NULL,
    kode_barang VARCHAR(50) NOT NULL UNIQUE,
    nama_barang VARCHAR(150) NOT NULL,
    tipe_item ENUM('retail', 'repack', 'jasa', 'kandang') NOT NULL DEFAULT 'retail',
    stok INT DEFAULT 0,
    berat_gram INT NULL DEFAULT 0,
    harga_beli DECIMAL(12,2) DEFAULT 0.00,
    harga_jual DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori_barang(id_kategori) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 7. Transaksi Penjualan Kasir POS
CREATE TABLE IF NOT EXISTS penjualan (
    id_penjualan INT AUTO_INCREMENT PRIMARY KEY,
    id_customer INT NOT NULL,
    id_user INT NOT NULL,
    no_faktur VARCHAR(50) NOT NULL UNIQUE,
    tgl_penjualan DATE NOT NULL,
    total_bayar DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_customer) REFERENCES customer(id_customer) ON DELETE RESTRICT,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 8. Detail Item Penjualan
CREATE TABLE IF NOT EXISTS detail_penjualan (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_penjualan INT NOT NULL,
    id_barang INT NOT NULL,
    jumlah INT NOT NULL,
    harga_satuan DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (id_penjualan) REFERENCES penjualan(id_penjualan) ON DELETE CASCADE,
    FOREIGN KEY (id_barang) REFERENCES barang(id_barang) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 9. Transaksi Reservasi & Check-in Pet Hotel
CREATE TABLE IF NOT EXISTS reservasi_inap (
    id_inap INT AUTO_INCREMENT PRIMARY KEY,
    id_hewan INT NOT NULL,
    id_kandang INT NOT NULL,
    tgl_masuk DATETIME NOT NULL,
    tgl_estimasi_keluar DATETIME NOT NULL,
    tgl_aktual_keluar DATETIME NULL,
    opsi_pakan ENUM('Bawa_Mandiri', 'Disediakan_Toko') NOT NULL,
    uang_muka_dp DECIMAL(12,2) DEFAULT 0.00,
    biaya_denda_overstay DECIMAL(12,2) DEFAULT 0.00,
    total_biaya_akhir DECIMAL(12,2) NULL,
    status_inap ENUM('Booking', 'Check-In', 'Selesai', 'Batal') NOT NULL DEFAULT 'Check-In',
    FOREIGN KEY (id_hewan) REFERENCES hewan_peliharaan(id_hewan) ON DELETE RESTRICT,
    FOREIGN KEY (id_kandang) REFERENCES kandang(id_kandang) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 10. Antrean Layanan Grooming
CREATE TABLE IF NOT EXISTS antrean_grooming (
    id_grooming INT AUTO_INCREMENT PRIMARY KEY,
    id_hewan INT NOT NULL,
    id_groomer INT NOT NULL,
    id_barang_layanan INT NOT NULL,
    status_pengerjaan ENUM('Antre', 'Mandi', 'Pengeringan', 'Siap_Ambil', 'Selesai') NOT NULL DEFAULT 'Antre',
    waktu_masuk DATETIME NOT NULL,
    waktu_selesai DATETIME NULL,
    catatan_kondisi TEXT NULL,
    FOREIGN KEY (id_hewan) REFERENCES hewan_peliharaan(id_hewan) ON DELETE RESTRICT,
    FOREIGN KEY (id_groomer) REFERENCES users(id_user) ON DELETE RESTRICT,
    FOREIGN KEY (id_barang_layanan) REFERENCES barang(id_barang) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 11. Log Konversi Repack Pakan & Susut
CREATE TABLE IF NOT EXISTS log_repack (
    id_repack INT AUTO_INCREMENT PRIMARY KEY,
    id_barang_karung_asal INT NOT NULL,
    id_barang_eceran_target INT NOT NULL,
    jumlah_karung_asal INT NOT NULL,
    total_hasil_eceran_pack INT NOT NULL,
    selisih_susut_gram DECIMAL(10,2) NOT NULL,
    tgl_eksekusi DATETIME NOT NULL,
    id_admin INT NOT NULL,
    FOREIGN KEY (id_barang_karung_asal) REFERENCES barang(id_barang) ON DELETE RESTRICT,
    FOREIGN KEY (id_barang_eceran_target) REFERENCES barang(id_barang) ON DELETE RESTRICT,
    FOREIGN KEY (id_admin) REFERENCES users(id_user) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 12. Rekapitulasi Hak Komisi Staf
CREATE TABLE IF NOT EXISTS komisi_groomer (
    id_komisi INT AUTO_INCREMENT PRIMARY KEY,
    id_user_groomer INT NOT NULL,
    id_penjualan INT NOT NULL,
    id_barang_layanan INT NOT NULL,
    persentase_komisi DECIMAL(5,2) NOT NULL,
    nominal_komisi DECIMAL(12,2) NOT NULL,
    tgl_transaksi DATETIME NOT NULL,
    FOREIGN KEY (id_user_groomer) REFERENCES users(id_user) ON DELETE RESTRICT,
    FOREIGN KEY (id_penjualan) REFERENCES penjualan(id_penjualan) ON DELETE CASCADE,
    FOREIGN KEY (id_barang_layanan) REFERENCES barang(id_barang) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 13. Vendor & Pasokan Masuk
CREATE TABLE IF NOT EXISTS vendor (
    id_vendor INT AUTO_INCREMENT PRIMARY KEY,
    nama_vendor VARCHAR(100) NOT NULL,
    kontak VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pembelian (
    id_pembelian INT AUTO_INCREMENT PRIMARY KEY,
    id_vendor INT NOT NULL,
    no_faktur_pembelian VARCHAR(50) NOT NULL UNIQUE,
    tgl_pembelian DATE NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_vendor) REFERENCES vendor(id_vendor) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS detail_pembelian (
    id_detail_pembelian INT AUTO_INCREMENT PRIMARY KEY,
    id_pembelian INT NOT NULL,
    id_barang INT NOT NULL,
    jumlah INT NOT NULL,
    harga_beli DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (id_pembelian) REFERENCES pembelian(id_pembelian) ON DELETE CASCADE,
    FOREIGN KEY (id_barang) REFERENCES barang(id_barang) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 14. Tabel Pengaturan Sistem & UI Theme
CREATE TABLE IF NOT EXISTS pengaturan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kunci VARCHAR(100) UNIQUE NOT NULL,
    nilai TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- SEED DATA DEFAULT

-- Default Internal Users (Password: password)
INSERT INTO users (id_user, nama, username, password, role) VALUES
(1, 'Administrator PetCare', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'Sarah Kasir', 'kasir1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kasir'),
(3, 'Dedi Groomer', 'groomer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'groomer')
ON DUPLICATE KEY UPDATE nama=VALUES(nama);

-- Default Settings
INSERT INTO pengaturan (kunci, nilai) VALUES
('nama_toko', 'PetCare POS & Hotel Management'),
('alamat_toko', 'Jl. Pet Kingdom No. 45, Jakarta'),
('telepon_toko', '0812-3456-7890'),
('email_toko', 'contact@petcare.id'),
('persentase_komisi_groomer', '20'),
('tarif_denda_overstay_per_jam', '15000'),
('footer_struk', 'Terima kasih atas kepercayaan Anda pada PetCare! Sehat selalu untuk anabul.'),
('warna_primary', '#0284c7'),
('warna_secondary', '#0369a1'),
('warna_sidebar', '#0f172a'),
('warna_sidebar_header', '#1e293b'),
('warna_success', '#10b981'),
('warna_danger', '#ef4444'),
('warna_warning', '#f59e0b'),
('warna_info', '#06b6d4')
ON DUPLICATE KEY UPDATE nilai=VALUES(nilai);

-- Default Categories
INSERT INTO kategori_barang (id_kategori, nama_kategori) VALUES
(1, 'Pakan Kucing (Dry Food)'),
(2, 'Pakan Anjing (Dry Food)'),
(3, 'Wet Food & Treat'),
(4, 'Layanan Grooming'),
(5, 'Pet Hotel & Fasilitas'),
(6, 'Obat, Vitamin & Perawatan')
ON DUPLICATE KEY UPDATE nama_kategori=VALUES(nama_kategori);

-- Default Cages (Kandang)
INSERT INTO kandang (id_kandang, nomor_kandang, ukuran, tarif_per_malam, status_tersedia) VALUES
(1, 'KD-S01', 'Small', 50000.00, 1),
(2, 'KD-S02', 'Small', 50000.00, 1),
(3, 'KD-M01', 'Medium', 75000.00, 1),
(4, 'KD-M02', 'Medium', 75000.00, 1),
(5, 'KD-L01', 'Large', 110000.00, 1),
(6, 'VIP-01', 'VIP', 175000.00, 1)
ON DUPLICATE KEY UPDATE tarif_per_malam=VALUES(tarif_per_malam);

-- Default Vendors
INSERT INTO vendor (id_vendor, nama_vendor, kontak) VALUES
(1, 'PT. Royal Satwa Sejahtera', '081122334455'),
(2, 'CV. Pet Supply Indonesia', '081299887766')
ON DUPLICATE KEY UPDATE nama_vendor=VALUES(nama_vendor);

-- Default Products & Services
INSERT INTO barang (id_barang, id_kategori, kode_barang, nama_barang, tipe_item, stok, berat_gram, harga_beli, harga_jual) VALUES
-- Karung Asal Repack
(1, 1, 'RC-BABY-10KG', 'Royal Canin Mother & Babycat Sack 10kg', 'retail', 8, 10000, 850000.00, 1050000.00),
(2, 2, 'RC-MAXI-15KG', 'Royal Canin Maxi Adult Dog Sack 15kg', 'retail', 5, 15000, 1100000.00, 1350000.00),
-- Target Eceran Repack
(3, 1, 'RC-BABY-500G', 'Royal Canin Mother & Babycat Repack 500g', 'repack', 25, 500, 42500.00, 65000.00),
(4, 1, 'RC-BABY-1KG', 'Royal Canin Mother & Babycat Repack 1kg', 'repack', 18, 1000, 85000.00, 120000.00),
(5, 2, 'RC-MAXI-1KG', 'Royal Canin Maxi Adult Dog Repack 1kg', 'repack', 12, 1000, 73300.00, 105000.00),
-- Ritel Reguler
(6, 3, 'WHISK-POUCH-TUNA', 'Whiskas Pouch Tuna 85g', 'retail', 80, 85, 6000.00, 8500.00),
(7, 3, 'PEDIGREE-CAN-BEEF', 'Pedigree Can Beef 400g', 'retail', 35, 400, 22000.00, 29000.00),
(8, 6, 'SHP-ANTI-KUTU-250', 'Shampoo Anti Kutu Kucing & Anjing 250ml', 'retail', 20, 250, 35000.00, 52000.00),
-- Layanan Grooming (Jasa)
(9, 4, 'GRM-BASIC', 'Grooming Mandi Dasar (Shampoo, Potong Kuku, Bersih Telinga)', 'jasa', 999, 0, 0.00, 60000.00),
(10, 4, 'GRM-KUTU-JAMUR', 'Grooming Khusus Kutu & Jamur (Medicated Shampoo)', 'jasa', 999, 0, 0.00, 85000.00),
(11, 4, 'GRM-VIP', 'Grooming Lengkap VIP (Styling Haircut, Spa & Aroma)', 'jasa', 999, 0, 0.00, 135000.00),
-- Item Fasilitas Kamar (Kandang)
(12, 5, 'KND-SMALL-DAY', 'Inap Hotel Kamar Small (Per Malam)', 'kandang', 999, 0, 0.00, 50000.00),
(13, 5, 'KND-MED-DAY', 'Inap Hotel Kamar Medium (Per Malam)', 'kandang', 999, 0, 0.00, 75000.00),
(14, 5, 'KND-LRG-DAY', 'Inap Hotel Kamar Large (Per Malam)', 'kandang', 999, 0, 0.00, 110000.00),
(15, 5, 'KND-VIP-DAY', 'Inap Hotel Kamar VIP AC (Per Malam)', 'kandang', 999, 0, 0.00, 175000.00)
ON DUPLICATE KEY UPDATE nama_barang=VALUES(nama_barang), harga_jual=VALUES(harga_jual);

-- Demo Customer (Password: password)
INSERT INTO customer (id_customer, nama_customer, telepon, alamat, password, is_active) VALUES
(1, 'Ahmad Fauzi', '081234567890', 'Jl. Kenanga No. 12, Kebayoran Baru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
(2, 'Jessica Tan', '085678901234', 'Apartemen Sudirman Tower A No. 1402', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1)
ON DUPLICATE KEY UPDATE nama_customer=VALUES(nama_customer);

-- Demo Pets
INSERT INTO hewan_peliharaan (id_hewan, id_customer, nama_hewan, spesies, ras, tanggal_lahir, berat_badan, catatan_alergi, catatan_kebiasaan) VALUES
(1, 1, 'Milo', 'Kucing', 'Persia Peaknose', '2023-02-10', 4.10, 'Alergi ayam & seafood, gunakan pakan hypoallergenic', 'Suka dielus bawah dagu, agak takut bunyi pengering rambut terlalu keras'),
(2, 1, 'Bobby', 'Anjing', 'Poodle Mini', '2022-08-15', 5.30, 'Tidak ada alergi', 'Sangat lincah, senang bermain bola'),
(3, 2, 'Cleo', 'Kucing', 'British Shorthair', '2023-05-20', 3.85, 'Sensitif sampo wangi kuat, pakai sampo oat', 'Tenang dan suka tidur di tempat tinggi')
ON DUPLICATE KEY UPDATE nama_hewan=VALUES(nama_hewan);