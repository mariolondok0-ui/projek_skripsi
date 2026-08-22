<?php $current = basename($_SERVER['PHP_SELF']); ?>
<nav class="pub-navbar" id="pubNavbar">
  <div class="nav-brand">
    <div class="brand-icon" style="background: linear-gradient(135deg, #1e6eb5, #3b82f6) !important; border-radius: 12px; display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; min-width: 38px; box-shadow: 0 4px 12px rgba(30, 110, 181, 0.3); border: 2px solid rgba(255, 255, 255, 0.2);">
      <i class="fas fa-mosque" style="color: #ffffff; font-size: 1rem;"></i>
    </div>
    <div style="min-width: 0; overflow: hidden;">
      <div style="font-size: 0.68rem; opacity: 0.75; font-weight: 400; line-height: 1.1;">Sistem Informasi</div>
      <div style="font-size: 0.85rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.2;">Kas Masjid Baiturrohman</div>
    </div>
  </div>

  <div class="nav-links custom-nav-links">
    <a href="<?= APP_URL ?>/index.php" class="nav-login pub-nav-btn" title="Kembali ke Halaman Utama">
      <span class="btn-text">Kembali</span> <i class="fas fa-arrow-right"></i>
    </a>
  </div>
  
  <span class="nav-toggle" id="navToggle" style="display: none !important;"><i class="fas fa-bars"></i></span>
</nav>

<style>
/* Memaksa navbar utama menempel di atas dan transparan */
.pub-navbar {
  display: flex !important;
  justify-content: space-between !important;
  align-items: center !important;
  padding: 12px 4% !important;
  background: transparent !important;
  position: fixed !important;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 1000;
  border-bottom: none !important;
  box-shadow: none !important;
}

/* Memastikan brand dan logo berjejer rapi dan tidak menabrak kanan */
.pub-navbar .nav-brand {
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
  max-width: 75% !important;
  flex: 1;
  min-width: 0;
}

/* Memaksa area link/tombol kembali tetap di kanan atas dan tidak menyusut */
.custom-nav-links {
  display: flex !important;
  position: static !important;
  background: transparent !important;
  box-shadow: none !important;
  padding: 0 !important;
  width: auto !important;
  flex-shrink: 0;
}

/* Sembunyikan tombol garis tiga secara permanen */
.pub-navbar .nav-toggle {
  display: none !important;
}

/* Styling tombol kembali dengan kotak berwarna dan responsif */
.pub-nav-btn {
  display: inline-flex !important;
  align-items: center;
  gap: 6px;
  background: rgba(255, 255, 255, 0.15) !important;
  color: #fff !important;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;
  border: 1px solid rgba(255, 255, 255, 0.25);
  transition: all 0.25s ease;
  text-decoration: none;
  white-space: nowrap;
}

.pub-nav-btn:hover {
  background: var(--primary, #1e6eb5) !important;
  border-color: rgba(255, 255, 255, 0.5);
  box-shadow: 0 4px 12px rgba(30, 110, 181, 0.4);
}

.pub-nav-btn:active {
  background: #155a96 !important;
}

/* Pengaturan khusus tampilan HP (Layar kecil di bawah 768px) */
@media (max-width: 768px) {
  .pub-nav-btn .btn-text {
    display: none !important; /* Kata "Kembali" disembunyikan di HP, menyisakan ikon panah saja */
  }
  .pub-nav-btn {
    padding: 8px 10px !important; /* Tombol jadi kotak pas untuk ikon panah */
  }
}

@media (min-width: 769px) {
  .pub-navbar {
    padding: 16px 5% !important;
  }
  .pub-nav-btn {
    padding: 10px 20px;
    font-size: 0.9rem;
  }
  .pub-navbar .brand-icon {
    width: 42px !important;
    height: 42px !important;
    min-width: 42px !important;
  }
  .pub-navbar .nav-brand div:last-child div:first-child {
    font-size: 0.75rem !important;
  }
  .pub-navbar .nav-brand div:last-child div:last-child {
    font-size: 1.05rem !important;
  }
}
</style>