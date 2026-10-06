<?php
// Deteksi halaman saat ini
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['user_role'] ?? 'guest';
?>
<!-- Sidebar Navigation (Adminator Design System) -->
<nav class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <span class="brand-icon">
                <i class="bi bi-heart-pulse-fill"></i>
            </span>
            <div class="brand-text-wrap">
                <span class="brand-title">PetCare <span class="brand-tag">POS</span></span>
                <span class="brand-sub">Management Suite</span>
            </div>
        </a>
        <button type="button" class="sidebar-toggler" style="display: none;" id="sidebarCloseBtn" aria-label="Tutup Menu">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    
    <ul class="sidebar-nav">
        <!-- SEKSI UTAMA -->
        <li class="sidebar-section-title">Menu Utama</li>
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>" title="Dashboard Analitik">
                <div class="nav-link-content">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span class="nav-text">Dashboard Analitik</span>
                </div>
            </a>
        </li>
        
        <!-- SEKSI TRANSAKSI & LAYANAN -->
        <?php if ($user_role === 'admin' || $user_role === 'kasir'): ?>
        <li class="sidebar-section-title">Transaksi & Layanan</li>
        <li class="nav-item">
            <a href="penjualan.php" class="nav-link <?php echo ($current_page === 'penjualan.php') ? 'active' : ''; ?>" title="Kasir POS Hibrida">
                <div class="nav-link-content">
                    <i class="bi bi-cart3"></i>
                    <span class="nav-text">Kasir POS Hibrida</span>
                </div>
                <span class="sidebar-badge badge-info">POS</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="booking_inap.php" class="nav-link <?php echo ($current_page === 'booking_inap.php') ? 'active' : ''; ?>" title="Reservasi Pet Hotel">
                <div class="nav-link-content">
                    <i class="bi bi-building"></i>
                    <span class="nav-text">Pet Hotel & Inap</span>
                </div>
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-item">
            <a href="antrean_grooming.php" class="nav-link <?php echo ($current_page === 'antrean_grooming.php') ? 'active' : ''; ?>" title="Antrean Grooming">
                <div class="nav-link-content">
                    <i class="bi bi-scissors"></i>
                    <span class="nav-text">Antrean Grooming</span>
                </div>
            </a>
        </li>

        <?php if ($user_role === 'admin' || $user_role === 'kasir'): ?>
        <li class="nav-item">
            <a href="customer.php" class="nav-link <?php echo ($current_page === 'customer.php') ? 'active' : ''; ?>" title="Pelanggan & Hewan">
                <div class="nav-link-content">
                    <i class="bi bi-people-fill"></i>
                    <span class="nav-text">Pelanggan & Anabul</span>
                </div>
            </a>
        </li>
        <?php endif; ?>

        <!-- SEKSI LOGISTIK & PRODUK -->
        <?php if ($user_role === 'admin'): ?>
        <li class="sidebar-section-title">Logistik & Inventori</li>
        <li class="nav-item">
            <a href="repack.php" class="nav-link <?php echo ($current_page === 'repack.php') ? 'active' : ''; ?>" title="Konversi Repack Pakan">
                <div class="nav-link-content">
                    <i class="bi bi-box-seam-fill"></i>
                    <span class="nav-text">Konversi Repack</span>
                </div>
            </a>
        </li>
        <li class="nav-item">
            <a href="stok.php" class="nav-link <?php echo ($current_page === 'stok.php') ? 'active' : ''; ?>" title="Monitoring Stok">
                <div class="nav-link-content">
                    <i class="bi bi-boxes"></i>
                    <span class="nav-text">Monitoring Stok</span>
                </div>
            </a>
        </li>
        <?php endif; ?>

        <!-- SEKSI MASTER DATA -->
        <?php if ($user_role === 'admin' || $user_role === 'kasir'): ?>
        <li class="sidebar-section-title">Master Data</li>
        <li class="nav-item">
            <a href="barang.php" class="nav-link <?php echo ($current_page === 'barang.php') ? 'active' : ''; ?>" title="Master Produk & Jasa">
                <div class="nav-link-content">
                    <i class="bi bi-tags-fill"></i>
                    <span class="nav-text">Produk & Layanan</span>
                </div>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($user_role === 'admin'): ?>
        <li class="nav-item">
            <a href="kandang.php" class="nav-link <?php echo ($current_page === 'kandang.php') ? 'active' : ''; ?>" title="Fasilitas Kandang Hotel">
                <div class="nav-link-content">
                    <i class="bi bi-door-open-fill"></i>
                    <span class="nav-text">Kamar Pet Hotel</span>
                </div>
            </a>
        </li>
        <li class="nav-item">
            <a href="kategori.php" class="nav-link <?php echo ($current_page === 'kategori.php') ? 'active' : ''; ?>" title="Kategori Produk">
                <div class="nav-link-content">
                    <i class="bi bi-folder2-open"></i>
                    <span class="nav-text">Kategori Produk</span>
                </div>
            </a>
        </li>
        <li class="nav-item">
            <a href="pembelian.php" class="nav-link <?php echo ($current_page === 'pembelian.php') ? 'active' : ''; ?>" title="Pengadaan / Pembelian">
                <div class="nav-link-content">
                    <i class="bi bi-truck"></i>
                    <span class="nav-text">Pengadaan Pasokan</span>
                </div>
            </a>
        </li>
        <li class="nav-item">
            <a href="vendor.php" class="nav-link <?php echo ($current_page === 'vendor.php') ? 'active' : ''; ?>" title="Data Vendor Supplier">
                <div class="nav-link-content">
                    <i class="bi bi-shop"></i>
                    <span class="nav-text">Data Vendor</span>
                </div>
            </a>
        </li>
        <?php endif; ?>

        <!-- SEKSI LAPORAN & SISTEM -->
        <li class="sidebar-section-title">Laporan & Pengaturan</li>
        <?php if ($user_role === 'admin' || $user_role === 'kasir'): ?>
        <li class="nav-item">
            <a href="laporan_penjualan.php" class="nav-link <?php echo ($current_page === 'laporan_penjualan.php') ? 'active' : ''; ?>" title="Laporan Penjualan">
                <div class="nav-link-content">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span class="nav-text">Laporan Penjualan</span>
                </div>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($user_role === 'admin'): ?>
        <li class="nav-item">
            <a href="laporan_pembelian.php" class="nav-link <?php echo ($current_page === 'laporan_pembelian.php') ? 'active' : ''; ?>" title="Laporan Pengadaan">
                <div class="nav-link-content">
                    <i class="bi bi-graph-down-arrow"></i>
                    <span class="nav-text">Laporan Pengadaan</span>
                </div>
            </a>
        </li>
        <li class="nav-item">
            <a href="users.php" class="nav-link <?php echo ($current_page === 'users.php') ? 'active' : ''; ?>" title="Manajemen Pengguna">
                <div class="nav-link-content">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span class="nav-text">Staf & Pengguna</span>
                </div>
            </a>
        </li>
        <li class="nav-item">
            <a href="pengaturan.php" class="nav-link <?php echo ($current_page === 'pengaturan.php') ? 'active' : ''; ?>" title="Pengaturan Sistem">
                <div class="nav-link-content">
                    <i class="bi bi-sliders2"></i>
                    <span class="nav-text">Pengaturan Sistem</span>
                </div>
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-item" style="margin-top: 18px; border-top: 1px dashed var(--border-subtle); padding-top: 12px;">
            <a href="portal/index.php" target="_blank" class="nav-link" title="Buka Portal Pelanggan" style="color: var(--color-primary); background: var(--color-primary-soft);">
                <div class="nav-link-content">
                    <i class="bi bi-globe2" style="color: var(--color-primary);"></i>
                    <span class="nav-text" style="font-weight: 600;">Portal Pelanggan</span>
                </div>
                <i class="bi bi-arrow-up-right" style="font-size: 12px; width: auto;"></i>
            </a>
        </li>

        <li class="nav-item">
            <a href="logout.php" class="nav-link" title="Logout Keluar" style="color: var(--color-danger);">
                <div class="nav-link-content">
                    <i class="bi bi-box-arrow-right" style="color: var(--color-danger);"></i>
                    <span class="nav-text">Logout Keluar</span>
                </div>
            </a>
        </li>
    </ul>
</nav>