# PetCare POS, Grooming & Hotel Management

Aplikasi manajemen pet shop berbasis PHP native dan MySQL/MariaDB. Proyek ini mencakup POS, pengelolaan stok dan pembelian, antrean grooming, reservasi inap, serta portal pelanggan.

## Fitur

- **POS dan transaksi:** penjualan produk dan layanan, pencetakan struk, laporan penjualan.
- **Persediaan:** katalog barang dan jasa, kategori, vendor, pembelian, pemantauan stok, serta pencatatan konversi pakan repack.
- **Grooming:** antrean kerja bertahap, catatan kondisi dan alergi hewan, serta pencatatan komisi saat layanan grooming dijual di POS.
- **Pet hotel:** reservasi, check-in/check-out, pilihan pakan, dan pencatatan biaya inap serta denda overstay.
- **Portal pelanggan:** pendaftaran/login, profil hewan, booking grooming dan inap, pelacakan grooming, serta riwayat transaksi.
- **Administrasi:** pengguna dan role staf, pengaturan toko, tema, dan dashboard.

## Teknologi dan struktur

- PHP 8+ dengan PDO; tidak ada framework PHP atau Composer dependency yang dikelola proyek.
- MySQL atau MariaDB dengan tabel InnoDB.
- Antarmuka HTML, CSS, dan JavaScript tanpa bundler.
- Controller berupa halaman PHP di root dan di `portal/`; logika query dikelompokkan dalam `models/`.

Direktori penting:

```text
assets/       CSS, JavaScript, dan CSS dinamis
config/       konfigurasi aplikasi dan koneksi database
database/     skema PetCare dan berkas SQL dari proyek POS lama
models/       model data dan operasi bisnis
portal/       halaman pelanggan
*.php         halaman admin, kasir, dan operasional
```

`database/petcare_schema.sql` adalah skema dan seed utama untuk aplikasi PetCare. `database/schema.sql` dan `database/pos_minimart.sql` merupakan berkas dari proyek POS asal; jangan gunakan keduanya untuk memasang PetCare.

## Menjalankan secara lokal

1. Pasang PHP 8+, ekstensi PDO MySQL, dan MySQL/MariaDB. Laragon atau XAMPP dapat digunakan.
2. Salin proyek ke document root web server, misalnya `C:\laragon\www\petcare`.
3. Untuk lokal, koneksi default memakai `localhost`, database `petcare_db`, user `root`, tanpa password. Nilai dapat diubah dengan environment variables `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`.
4. Jalankan MySQL dan Apache, lalu buka `http://localhost/petcare/install.php` untuk membuat database dan memuat skema serta seed awal.
5. Buka aplikasi:
   - Beranda: `http://localhost/petcare/`
   - Login staf: `http://localhost/petcare/login.php`
   - Portal pelanggan: `http://localhost/petcare/portal/index.php`

`BASE_URL` ditetapkan di `config/config.php` ke `http://localhost/petcare/`. Sesuaikan bila nama host atau jalur pemasangan berbeda.

## Deploy di Railway

Railway mendeteksi `Dockerfile` di root proyek. Image memasang ekstensi `pdo_mysql` dan `mysqli`, lalu Apache mendengarkan port dari variabel `PORT`. Tambahkan service MySQL Railway dan hubungkan service aplikasi ke service database. Koneksi otomatis membaca `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, dan `MYSQLPASSWORD` yang disediakan Railway; variabel `DB_*` juga didukung.

Setelah deploy pertama, buka `/install.php` satu kali untuk membuat tabel dan data awal di database yang terhubung. User Railway harus memiliki izin membuat tabel. Installer tidak mencoba membuat database baru di Railway. Setelah instalasi selesai, hapus atau batasi akses ke `/install.php` sebelum aplikasi dipakai publik.

Booking grooming dari portal otomatis membuat antrean layanan. Di POS, kasir/admin memilih booking yang menunggu pembayaran; pelanggan, anabul, groomer, layanan, dan tarif terisi otomatis. Setelah checkout, antrean ditautkan ke transaksi agar booking tersebut tidak ditagih atau dibuat antrean baru untuk kedua kalinya. Jika database sudah terpasang sebelum alur ini ditambahkan, jalankan `/install.php` sekali untuk menambahkan kolom relasi transaksi.

Installer bukan sistem migrasi versi. Skema memakai `CREATE TABLE IF NOT EXISTS` dan seed awal; menjalankannya kembali tidak menerapkan perubahan skema untuk tabel yang sudah ada. Cadangkan database sebelum perubahan manual atau pemasangan ulang.

## Akun demo

Seed bawaan menggunakan password `password` untuk akun berikut:

| Jenis akun | Username / nomor telepon | Catatan |
| --- | --- | --- |
| Admin | `admin` | Administrasi aplikasi |
| Kasir | `kasir1` | POS dan operasional yang diizinkan untuk kasir |
| Groomer | `groomer1` | Papan antrean grooming |
| Pelanggan | `081234567890` | Ahmad Fauzi; hewan Milo dan Bobby |
| Pelanggan | `085678901234` | Jessica Tan; hewan Cleo |

Akun tersebut hanya untuk pengembangan lokal. Ganti password demo sebelum aplikasi dipakai dengan data nyata. Form login demo saat ini menampilkan kredensial contoh.

## Catatan kondisi saat ini

README ini menjelaskan perilaku yang tersedia di kode, bukan jaminan kesiapan produksi. Review statis menemukan beberapa hal yang perlu diperbaiki sebelum aplikasi digunakan dengan data atau transaksi nyata:

- Booking portal menerima ID hewan dari form tanpa validasi kepemilikan pada server.
- POS menerima harga dan jumlah item dari permintaan browser; validasi stok dan harga perlu dipindahkan/diterapkan di sisi server.
- Pemeriksaan bentrok reservasi kamar belum mencakup rentang tanggal, dan alokasi kamar belum dibuat atomik.
- Check-out menerima total dan denda dari form serta perhitungan inap memakai tanggal estimasi.
- Form perubahan data belum menggunakan token CSRF; sesi belum diregenerasi setelah login.
- Installer dapat diakses lewat web dan menampilkan pesan error. Batasi penggunaannya dan matikan setelah instalasi.

## Dokumentasi terkait

- `PANDUAN_ARSITEKTUR.md` — panduan arsitektur dan pola pengembangan.
- `petcare.md` — spesifikasi sistem PetCare.
- `Design.md` — catatan desain antarmuka.
