# Panduan Desain & Arsitektur UI: Adminator Dashboard

Dokumen ini mendefinisikan sistem desain, tata letak antarmuka, spesifikasi visual (design tokens), dan komponen inti yang diadaptasi dari **Adminator Admin Dashboard**. Dokumen ini dapat digunakan sebagai referensi pengembang (frontend/backend) dalam mengimplementasikan dashboard yang konsisten, responsif, dan mudah dipelihara.

---

## 1. Filosofi & Karakteristik Desain

Adminator mengusung gaya **Clean Modular Flat Design** dengan fokus pada:
- **Hierarki Informasi yang Tegas:** Pemanfaatan kontras warna yang lembut antara latar belakang kanvas abu-abu terang dengan kartu konten (*white cards*).
- **Kepadatan Informasi Optimal:** Cocok untuk aplikasi analitik data, metrik performa, dan manajemen sistem (CRUD) tanpa terlihat padat atau berantakan.
- **Micro-Interactions yang Halus:** Transisi minimalis pada navigasi sidebar (*collapse/expand*), hover states tombol, serta chart interaktif.

---

## 2. Design Tokens (Spesifikasi Visual)

### 2.1. Skema Warna (Color Palette)

| Kategori | Nama Token | Nilai Hex | Penggunaan Utama |
| :--- | :--- | :--- | :--- |
| **Canvas** | `bg-main` | `#f4f6f9` / `#f9fafb` | Latar belakang halaman aplikasi utama |
| **Surface** | `bg-surface` | `#ffffff` | Kartu konten, topbar, modal, popover |
| **Sidebar** | `bg-sidebar` | `#ffffff` (atau `#1f2d3d` versi gelap) | Latar belakang sidebar navigasi |
| **Primary** | `color-primary` | `#0f5499` / `#2196f3` | Tombol utama, link aktif, status fokus |
| **Secondary** | `color-secondary` | `#6c757d` | Teks sekunder, border, elemen non-aktif |
| **Success** | `color-success` | `#4caf50` / `#00c292` | Metrik naik, notifikasi sukses, badge aktif |
| **Warning** | `color-warning` | `#ff9800` / `#fec107` | Peringatan, status pending |
| **Danger** | `color-danger` | `#f44336` / `#e91e63` | Error, status gagal, tombol destruktif |
| **Text Primary** | `text-primary` | `#2b343b` / `#31353e` | Judul, heading, teks utama |
| **Text Muted** | `text-muted` | `#72777a` | Label form, caption, timestamp, breadcrumb |
| **Border** | `border-subtle` | `#e9ecef` / `#dee2e6` | Garis pemisah tabel, kartu, divider |

### 2.2. Tipografi (Typography)

- **Font Family Utama:** `'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif`
- **Monospace:** `'Fira Code', 'Courier New', monospace` (untuk tabel data numerik/kode)

| Level | Ukuran (Size) | Weight | Line Height | Penggunaan |
| :--- | :--- | :--- | :--- | :--- |
| **Display / H1** | `24px` (`1.5rem`) | `700` (Bold) | `1.25` | Judul halaman utama |
| **H2 / Card Title** | `18px` (`1.125rem`) | `600` (Semi-Bold) | `1.3` | Header kartu widget/tabel |
| **H3 / Subheading** | `15px` (`0.9375rem`) | `600` (Semi-Bold) | `1.4` | Sub-seksi dalam halaman |
| **Body (Default)** | `14px` (`0.875rem`) | `400` (Regular) | `1.5` | Konten tabel, teks paragraf, input form |
| **Small / Caption**| `12px` (`0.75rem`) | `400` / `500` | `1.4` | Badge status, timestamp, petunjuk input |

### 2.3. Bayangan (Elevation & Shadows)

- **Card Subtle:** `0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03)`
- **Hover/Dropdown:** `0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)`
- **Modal / Floating:** `0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05)`

### 2.4. Radius Sudut (Border Radius)

- **Button & Input:** `4px` hingga `6px`
- **Card / Widget:** `6px` hingga `8px`
- **Badge / Pill:** `9999px`

---

## 3. Struktur Layout Aplikasi

Struktur layout Adminator tersusun atas 4 komponen utama yang membungkus konten:

```
+--------------------------------------------------------------------+
|  [Sidebar]    |  [Header / Topbar]                                 |
|  - Logo       |  - Hamburger Toggler  - Search Bar  - User Profile |
|  - Nav Links  +----------------------------------------------------+
|  - Dropdowns  |  [Main Content Container]                          |
|  - Footer Nav |  - Page Header & Breadcrumbs                       |
|               |  - Row/Col Grid (Cards, Tables, Widgets)           |
|               |                                                    |
|               +----------------------------------------------------+
|               |  [Footer]  - Copyright & App Version               |
+---------------+----------------------------------------------------+
```

### 3.1. Dimensi Layout

- **Lebar Sidebar Normal:** `250px`
- **Lebar Sidebar Mini (Collapsed):** `70px` (hanya menampilkan ikon)
- **Tinggi Header (Topbar):** `65px`
- **Breakpoint Responsif:**
  - Desktop: `≥ 992px` (Sidebar selalu terbuka atau semi-collapsible)
  - Tablet/Mobile: `< 992px` (Sidebar tersembunyi sebagai off-canvas drawer)

---

## 4. Komponen Antarmuka Inti

### 4.1. Kartu Metrik / Stat Widget (KPI Card)

Komponen ini menampilkan ringkasan data penting pada bagian paling atas dashboard.

```html
<div class="stat-card">
  <div class="stat-icon bg-primary-soft text-primary">
    <!-- Icon SVG / FontIcon -->
    <i class="ti-bar-chart"></i>
  </div>
  <div class="stat-details">
    <span class="stat-label">Total Pendapatan</span>
    <h3 class="stat-value">Rp 124.500.000</h3>
    <span class="stat-trend trend-positive">
      <i class="ti-arrow-up"></i> +12.5% vs bulan lalu
    </span>
  </div>
</div>
```

### 4.2. Header Halaman (Page Header & Actions)

Setiap halaman modul harus memiliki header yang konsisten:

```html
<div class="page-header d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="page-title">Daftar Pengguna</h2>
    <nav class="breadcrumb-nav">
      <span>Dashboard</span> / <span class="active">Pengguna</span>
    </nav>
  </div>
  <div class="page-actions">
    <button class="btn btn-outline-secondary me-2">Ekspor CSV</button>
    <button class="btn btn-primary">+ Tambah Baru</button>
  </div>
</div>
```

### 4.3. Data Table Card

Membungkus tabel data dengan toolbar pencarian dan pagination:

```html
<div class="card data-card shadow-sm border-0">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Transaksi Terbaru</h5>
    <div class="card-tools">
      <input type="text" class="form-control form-control-sm" placeholder="Cari data...">
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Nama Pelanggan</th>
            <th>Tanggal</th>
            <th>Nominal</th>
            <th>Status</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>#TRX-0981</td>
            <td>Ahmad Fadilah</td>
            <td>04 Okt 2026</td>
            <td>Rp 450.000</td>
            <td><span class="badge bg-success-subtle text-success">Selesai</span></td>
            <td class="text-end">
              <button class="btn btn-sm btn-light"><i class="ti-more-alt"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer bg-white d-flex justify-content-between align-items-center">
    <small class="text-muted">Menampilkan 1 - 10 dari 120 data</small>
    <!-- Pagination Container -->
  </div>
</div>
```

---

## 5. Struktur Folder yang Disarankan

Untuk mengintegrasikan konsep Adminator ke dalam proyek Anda, pisahkan styling dan layout dengan struktur modular berikut:

```text
src/ (atau resources/)
├── assets/
│   ├── scss/
│   │   ├── _variables.scss       # Token warna, typography, spacing
│   │   ├── _sidebar.scss         # Styling navigasi sidebar & toggler
│   │   ├── _topbar.scss          # Styling header & user menu
│   │   ├── _cards.scss           # Widget metrik, data cards
│   │   ├── _tables.scss          # Penyesuaian styling DataTables
│   │   ├── _forms.scss           # Input controls, datepicker, select
│   │   └── main.scss             # Entry point SCSS
│   └── js/
│       ├── sidebar-toggle.js     # Logika buka/tutup sidebar
│       └── charts-init.js        # Konfigurasi default Chart.js / ApexCharts
├── components/
│   ├── Sidebar.ext
│   ├── Topbar.ext
│   ├── Footer.ext
│   └── StatWidget.ext
└── layouts/
    └── AdminLayout.ext           # Shell utama layout dashboard
```

---

## 6. Checklist Implementasi pada Proyek

Gunakan checklist ini saat mengadopsi template ke dalam basis kode proyek Anda:

1. [ ] **Normalisasi CSS / SCSS Tokens:** Daftarkan variabel warna, font, dan spacing Adminator ke dalam root CSS (`:root`) atau file SCSS global proyek.
2. [ ] **State Collapse Sidebar:** Simpan status sidebar (*collapsed* vs *expanded*) di `localStorage` atau Cookie agar preferensi pengguna tetap tersimpan saat reload halaman.
3. [ ] **Responsivitas Mobile:** Pastikan event klik di luar area sidebar menutup *drawer* di layar `< 992px`.
4. [ ] **Theme Mode Ready:** Buat variabel warna menggunakan CSS Custom Properties (variabel CSS) agar mudah mendukung Dark Mode jika diperlukan di masa mendatang.
5. [ ] **Standardisasi Komponen Tabel:** Pastikan seluruh tabel dibungkus kelas `.table-responsive` untuk menghindari overflow horizontal pada viewport kecil.