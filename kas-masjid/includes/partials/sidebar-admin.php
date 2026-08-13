<?php
$current    = basename($_SERVER['PHP_SELF']);
$admin_name = $_SESSION['admin_nama'] ?? 'Admin';
$admin_init = strtoupper(substr($admin_name, 0, 1));
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="adminSidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo" style="background: linear-gradient(135deg, #1e6eb5, #3b82f6) !important; width: 45px; height: 45px; border-radius: 12px; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 12px rgba(30, 110, 181, 0.4) !important; border: 1px solid rgba(255, 255, 255, 0.2);">
      <i class="fas fa-mosque" style="color: #ffffff; font-size: 22px;"></i>
    </div>
    <div class="sidebar-brand">
      <h3>Kas Masjid</h3>
      <p>Baeturrohman</p>
    </div>
  </div>

  <nav class="sidebar-nav">

    <a href="<?= APP_URL ?>/admin/dashboard.php"
       class="nav-item <?= $current=='dashboard.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard
    </a>

    <a href="<?= APP_URL ?>/admin/kas-masuk.php"
       class="nav-item <?= $current=='kas-masuk.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-arrow-circle-up"></i></span> Kas Masuk
    </a>
    <a href="<?= APP_URL ?>/admin/kas-keluar.php"
       class="nav-item <?= $current=='kas-keluar.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-arrow-circle-down"></i></span> Kas Keluar
    </a>

    <a href="<?= APP_URL ?>/admin/laporan.php"
       class="nav-item <?= $current=='laporan.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-file-invoice-dollar"></i></span> Laporan
    </a>

    <a href="<?= APP_URL ?>/admin/kategori.php"
       class="nav-item <?= $current=='kategori.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-tags"></i></span> Kategori
    </a>
    <?php
    $jml_sampah = 0;
    try {
        if (isset($conn) && $conn) {
            $cek_kolom = $conn->query("SHOW COLUMNS FROM transaksi LIKE 'deleted_at'");
            if ($cek_kolom && $cek_kolom->num_rows > 0) {
                $res = $conn->query("SELECT COUNT(*) as c FROM transaksi WHERE deleted_at IS NOT NULL");
                if ($res) $jml_sampah = (int)$res->fetch_assoc()['c'];
            }
        }
    } catch (Exception $e) {
        $jml_sampah = 0;
    }
    ?>

    <a href="<?= APP_URL ?>/admin/tempat-sampah.php"
       class="nav-item <?= $current=='tempat-sampah.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-history"></i></span> Riwayat
      <?php if ($jml_sampah > 0): ?>
      <span class="nav-badge"><?= $jml_sampah ?></span>
      <?php endif; ?>
    </a>
    
    <div class="nav-section-label">Publik</div>
    <a href="<?= APP_URL ?>/index.php" target="_blank" class="nav-item">
      <span class="nav-icon"><i class="fas fa-external-link-alt"></i></span> Lihat Halaman Publik
    </a>

  </nav>
</aside>

<!-- Modal Konfirmasi Logout -->
<div class="modal-overlay" id="logoutModal" style="z-index:9999">
  <div class="modal" style="max-width:380px">
    <div class="modal-header">
      <div class="modal-title">
        <i class="fas fa-sign-out-alt" style="color:var(--danger)"></i>
        Konfirmasi Logout
      </div>
      <button class="modal-close" onclick="closeLogoutModal()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="modal-body" style="text-align:center;padding:28px">
      <div style="width:68px;height:68px;background:rgba(239,68,68,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:var(--danger);margin:0 auto 16px">
        <i class="fas fa-sign-out-alt"></i>
      </div>
      <h3 style="font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:8px">
        Yakin ingin keluar?
      </h3>
      <p style="font-size:.85rem;color:#64748b;line-height:1.6">
        Anda akan keluar dari sesi admin.<br>Pastikan semua data sudah tersimpan.
      </p>
    </div>
    <div class="modal-footer" style="justify-content:center;gap:12px;padding-bottom:24px;border:none;">
      <button class="btn btn-ghost" onclick="closeLogoutModal()" style="min-width:110px;justify-content:center;background:#f1f5f9;color:#64748b;border:none;">
        <i class="fas fa-times"></i> Batal
      </button>
      <a href="<?= APP_URL ?>/logout.php" class="btn btn-danger" style="min-width:110px;justify-content:center;background:#ef4444;color:#fff;border:none;padding:10px 15px;border-radius:8px;">
        <i class="fas fa-sign-out-alt"></i> Ya, Logout
      </a>
    </div>
  </div>
</div>

<script>
function openLogoutModal()  { document.getElementById('logoutModal').classList.add('active'); }
function closeLogoutModal() { document.getElementById('logoutModal').classList.remove('active'); }
document.getElementById('logoutModal').addEventListener('click', function(e) {
  if (e.target === this) closeLogoutModal();
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeLogoutModal();
});
</script>