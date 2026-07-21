<footer class="pub-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-brand">
          <div class="fb-icon"><i class="fas fa-mosque" style="color:#fff;font-size:1.2rem"></i></div>
          <div><h3>Masjid Baiturrohman</h3><p style="font-size:.75rem;opacity:.6">Sistem Informasi Keuangan Kas</p></div>
        </div>
        <p class="footer-about"><?= MASJID_ALAMAT ?>. Sistem ini dibangun untuk mendukung transparansi pengelolaan keuangan masjid kepada seluruh jamaah.</p>
      </div>
      <div>
        <h4 class="footer-heading">Navigasi</h4>
        <ul class="footer-links">
          <li><a href="<?= APP_URL ?>/index.php"><i class="fas fa-chevron-right" style="font-size:.7rem;margin-right:4px"></i>Beranda</a></li>
          <li><a href="<?= APP_URL ?>/laporan-publik.php"><i class="fas fa-chevron-right" style="font-size:.7rem;margin-right:4px"></i>Laporan Keuangan</a></li>
          <li><a href="<?= APP_URL ?>/grafik-publik.php"><i class="fas fa-chevron-right" style="font-size:.7rem;margin-right:4px"></i>Visualisasi Grafik</a></li>
          <li><a href="<?= APP_URL ?>/login.php"><i class="fas fa-chevron-right" style="font-size:.7rem;margin-right:4px"></i>Login Admin</a></li>
        </ul>
      </div>
      <div>
        <h4 class="footer-heading">Informasi</h4>
        <ul class="footer-links">
          <li><a href="#"><i class="fas fa-map-marker-alt" style="margin-right:6px;color:var(--secondary-light)"></i>Kec. Samarang, Garut</a></li>
          <li><a href="#"><i class="fas fa-clock" style="margin-right:6px;color:var(--secondary-light)"></i>Update Real-time</a></li>
          <li><a href="#"><i class="fas fa-lock" style="margin-right:6px;color:var(--secondary-light)"></i>Data Terverifikasi</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> <?= MASJID_NAME ?> &mdash; Sistem Informasi Pengelolaan Keuangan Kas Masjid</p>
      <p style="margin-top:4px;font-size:.75rem;opacity:.5">Dikembangkan sebagai penelitian skripsi &bull; Institut Teknologi Garut</p>
    </div>
  </div>
</footer>
