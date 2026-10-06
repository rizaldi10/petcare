# SPESIFIKASI PROYEK & PANDUAN PENGEMBANGAN: PETCARE POS & BOARDING SYSTEM

Dokumen ini merupakan panduan implementasi teknis (*System Specification & Implementation Prompt*) yang dirancang khusus untuk memandu agen AI (*Antigravity Agent* / *Autonomous Coding Agent*) dalam melakukan *refactoring* dan pengembangan sistem dari *base project* `pos_minimart` menjadi **PetCare POS, Grooming, & Boarding System**[cite: 1, 4].

---

## 1. IKHTISAR SISTEM & TUJUAN ARSITEKTUR

* **Nama Proyek:** PetCare POS & Hotel Management System[cite: 4]
* **Base Proyek:** `pos_minimart` (PHP Native Modular, MySQL/MariaDB, Vanilla JS, CSS)
* **Tipe Arsitektur:** Monolitik Terpadu (*Single Shared Database*, *Decoupled Interface Role*)
* **Tujuan Utama:** Memodifikasi sistem POS kasir standar menjadi sistem hibrida yang menangani:
  1. Penjualan produk ritel & modul konversi kemasan pakan karungan (*repack*)[cite: 1, 4].
  2. Papan kerja antrean layanan *grooming* dengan kalkulasi komisi staf[cite: 4].
  3. Reservasi fasilitas kamar inap (*pet hotel*) dengan kalkulasi denda penjemputan (*overstay*) otomatis[cite: 4].
  4. Portal mandiri pelanggan untuk pendaftaran profil hewan dan pelacakan status perawatan *real-time*[cite: 4].

---

## 2. STACK TEKNOLOGI & STRUKTUR DIREKTORI

### 2.1 Konfigurasi Lingkungan
* **Bahasa Pemrograman:** PHP >= 8.x (Gaya OOP terstruktur pada folder `models/`)
* **Basis Data:** MySQL 8.x / MariaDB (Engine: InnoDB, Ekstensi: PDO dengan `PDO::ERRMODE_EXCEPTION`)
* **Frontend:** Semantic HTML5, CSS kustom responsif, Vanilla JavaScript / Fetch API
* **Output Cetak:** Print CSS Media Query untuk printer termal POS 58mm/80mm

### 2.2 Peta Perubahan Struktur Berkas
```text
pos_minimart/ (Direktori Kerja Utama)
├── assets/
│   ├── css/
│   │   ├── style.css           # Styling antarmuka kasir & admin
│   │   ├── portal.css          # Styling portal publik pelanggan
│   │   └── print.css           # Layout cetak nota & tiket penjemputan
│   └── js/
│       ├── kasir.js            # Interaksi keranjang belanja hibrida
│       └── tracker.js          # Polling status pengerjaan grooming
├── config/
│   ├── config.php              # Base URL & konstanta sistem
│   └── database.php            # Handler koneksi PDO & transaksi ACID
├── database/
│   └── petcare_schema.sql      # DDL database relasional lengkap
├── models/
│   ├── User.php                # Entitas staf internal (admin, kasir, groomer)
│   ├── Customer.php            # Entitas pemilik hewan & kredensial portal
│   ├── Hewan.php               # Entitas hewan peliharaan & rekam alergi
│   ├── Barang.php              # Entitas produk ritel, jasa, & fasilitas
│   ├── Kandang.php             # Entitas fisik kamar pet hotel
│   ├── Penjualan.php           # Entitas transaksi kasir & pelunasan
│   ├── Inap.php                # Entitas booking, check-in, & check-out
│   ├── Grooming.php            # Entitas antrean & pembagian komisi
│   ├── Repack.php              # Logika konversi karung ke eceran
│   ├── Pembelian.php           # Pengadaan pasokan supplier
│   └── Vendor.php              # Entitas pemasok barang
├── portal/                     # Sub-direktori Portal Mandiri Pelanggan
│   ├── index.php               # Beranda akun pelanggan
│   ├── my_pets.php             # Form kelola profil hewan peliharaan
│   ├── booking.php             # Form reservasi kamar & grooming daring
│   ├── track.php               # Layar pemantau progres perawatan live
│   └── riwayat.php             # Unduh arsip struk digital
├── antrean_grooming.php        # Papan pantau tugas teknis groomer
├── booking_inap.php            # Meja front-desk reservasi pet hotel
├── repack.php                  # Antarmuka eksekusi pemecahan pakan
├── penjualan.php               # Kasir hibrida (ritel + jasa)
├── customer.php                # Master data pelanggan & hewan walk-in
├── barang.php                  # Master data produk, paket jasa, & kamar
├── stok.php                    # Monitoring mutasi stok gudang
├── struk.php                   # Generator tiket thermal & faktur
├── dashboard.php               # Analitik omzet, kamar, & bagi hasil
├── login.php                   # Form login multi-aktor
└── logout.php                  # Destruksi sesi