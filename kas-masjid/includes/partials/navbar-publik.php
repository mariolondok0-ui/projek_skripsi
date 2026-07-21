<?php $current = basename($_SERVER['PHP_SELF']); ?>
<nav class="pub-navbar" id="pubNavbar">
  <div class="nav-brand">
    <div class="brand-icon"><i class="fas fa-mosque"></i></div>
    <div>
      <div style="font-size:.75rem;opacity:.7;font-weight:400">Sistem Informasi</div>
      <div>Kas Masjid Baiturrohman</div>
    </div>
  </div>
  <div class="nav-links" id="navLinks">
    <a href="<?= APP_URL ?>/index.php" class="<?= $current=='index.php'?'active':'' ?>"><i class="fas fa-home"></i> Beranda</a>
    <a href="<?= APP_URL ?>/login.php" class="nav-login" style="display:inline-flex;align-items:center;gap:6px"><i class="fas fa-sign-in-alt"></i> Login Admin</a>
  </div>
  <span class="nav-toggle" id="navToggle"><i class="fas fa-bars"></i></span>
</nav>
<div class="pub-content"></div>
