<?php $current = basename($_SERVER['PHP_SELF']); ?>
<nav class="pub-navbar" id="pubNavbar">
  <div class="nav-brand">
    <div class="brand-icon" style="background:none;padding:0;overflow:hidden">
      <img src="<?= APP_URL ?>/assets/img/logo.jpg" alt="Logo"
           style="width:40px;height:40px;object-fit:cover;border-radius:8px;display:block">
    </div>
    <div>
      <div style="font-size:.75rem;opacity:.7;font-weight:400">Sistem Informasi</div>
      <div>Kas Masjid Baiturrohman</div>
    </div>
  </div>
  <div class="nav-links" id="navLinks">
    <a href="<?= APP_URL ?>/login.php" class="nav-login" style="display:inline-flex;align-items:center;gap:6px"><i class="fas fa-sign-in-alt"></i> Login Admin</a>
  </div>
  <span class="nav-toggle" id="navToggle"><i class="fas fa-bars"></i></span>
</nav>
<div class="pub-content"></div>
