<?php
/**
 * PetCare POS - Adminator Topbar Header Component
 * Standard 65px height, sticky top, with sidebar toggle and quick search
 */
$currentUserRole = $_SESSION['user_role'] ?? 'guest';
$currentUserName = $_SESSION['nama_lengkap'] ?? ($_SESSION['username'] ?? 'User');
$userAvatarInitial = strtoupper(substr($currentUserName, 0, 1));
?>
<header class="top-nav">
    <div class="topbar-left">
        <button type="button" class="sidebar-toggler" id="sidebarToggle" aria-label="Toggle Navigation" title="Sembunyikan/Tampilkan Navigasi">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-search">
            <i class="bi bi-search"></i>
            <input type="text" id="globalQuickSearch" placeholder="Cari data, anabul, transaksi..." aria-label="Search">
            <kbd>Ctrl+K</kbd>
        </div>
    </div>

    <div class="topbar-right">
        <div class="topbar-chip">
            <i class="bi bi-calendar3"></i>
            <span><?php echo date('d M Y'); ?></span>
        </div>

        <a href="portal/index.php" target="_blank" class="btn btn-sm btn-secondary" style="font-weight: 600;" title="Buka Portal Reservasi Pelanggan">
            <i class="bi bi-globe2 text-primary"></i> Portal Pelanggan
        </a>

        <div class="user-profile-widget">
            <div class="user-avatar-wrapper">
                <div class="user-avatar">
                    <?php echo $userAvatarInitial; ?>
                </div>
                <span class="status-indicator-dot" title="Online"></span>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($currentUserName); ?></div>
                <div class="user-role-badge"><?php echo ucfirst($currentUserRole); ?></div>
            </div>
        </div>
    </div>
</header>
