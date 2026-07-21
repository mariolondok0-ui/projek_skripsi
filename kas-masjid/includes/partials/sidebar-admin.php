<?php
$current     = basename($_SERVER['PHP_SELF']);
$admin_name  = $_SESSION['admin_nama'] ?? 'Admin';
$admin_init  = strtoupper(substr($admin_name, 0, 1));
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="adminSidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo"><i class="fas fa-mosque"></i></div>
    <div class="sidebar-brand"><h3>Kas Masjid</h3><p>Baiturrohman</p></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Menu Utama</div>
    <a href="<?= APP_URL ?>/admin/dashboard.php" class="nav-item <?= $current=='dashboard.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>Dashboard
    </a>
    <div class="nav-section-label">Keuangan</div>
    <a href="<?= APP_URL ?>/admin/kas-masuk.php" class="nav-item <?= $current=='kas-masuk.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-arrow-circle-down"></i></span>Kas Masuk
    </a>
    <a href="<?= APP_URL ?>/admin/kas-keluar.php" class="nav-item <?= $current=='kas-keluar.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-arrow-circle-up"></i></span>Kas Keluar
    </a>
    <a href="<?= APP_URL ?>/admin/laporan.php" class="nav-item <?= $current=='laporan.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-file-invoice-dollar"></i></span>Laporan
    </a>
    <div class="nav-section-label">Pengaturan</div>
    <a href="<?= APP_URL ?>/admin/kategori.php" class="nav-item <?= $current=='kategori.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-tags"></i></span>Kategori
    </a>
    <a href="<?= APP_URL ?>/admin/profil.php" class="nav-item <?= $current=='profil.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-user-cog"></i></span>Profil
    </a>
    <div class="nav-section-label">Publik</div>
    <a href="<?= APP_URL ?>/index.php" target="_blank" class="nav-item">
      <span class="nav-icon"><i class="fas fa-external-link-alt"></i></span>Lihat Halaman Publik
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= APP_URL ?>/logout.php" class="sidebar-user">
      <div class="user-avatar"><?= $admin_init ?></div>
      <div style="flex:1;overflow:hidden">
        <div style="font-size:.85rem;font-weight:700;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($admin_name) ?></div>
        <div style="font-size:.72rem;color:rgba(255,255,255,.5)">Logout <i class="fas fa-sign-out-alt"></i></div>
      </div>
    </a>
  </div>
</aside>
