<?php
$current    = basename($_SERVER['PHP_SELF']);
$admin_name = $_SESSION['admin_nama'] ?? 'Admin';
$admin_init = strtoupper(substr($admin_name, 0, 1));
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="adminSidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <i class="fas fa-mosque"></i>
    </div>
    <div class="sidebar-brand">
      <h3>Kas Masjid</h3>
      <p>Baiturrohman</p>
    </div>
  </div>

  <nav class="sidebar-nav">

    <div class="nav-section-label">Menu Utama</div>
    <a href="<?= APP_URL ?>/admin/dashboard.php"
       class="nav-item <?= $current=='dashboard.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard
    </a>

    <div class="nav-section-label">Keuangan</div>
    <a href="<?= APP_URL ?>/admin/kas-masuk.php"
       class="nav-item <?= $current=='kas-masuk.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-arrow-circle-down"></i></span> Kas Masuk
    </a>
    <a href="<?= APP_URL ?>/admin/kas-keluar.php"
       class="nav-item <?= $current=='kas-keluar.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-arrow-circle-up"></i></span> Kas Keluar
    </a>

    <div class="nav-section-label">Laporan</div>
    <a href="<?= APP_URL ?>/admin/laporan.php"
       class="nav-item <?= $current=='laporan.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-file-invoice-dollar"></i></span> Laporan
    </a>

    <div class="nav-section-label">Kategori</div>
    <a href="<?= APP_URL ?>/admin/kategori.php"
       class="nav-item <?= $current=='kategori.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-tags"></i></span> Kategori
    </a>
    <?php
    $jml_sampah = 0;
    try {
        if (isset($conn) && $conn) {
            // Cek dulu apakah kolom deleted_at sudah ada
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

    <div class="nav-section-label">Pengaturan</div>
    <a href="<?= APP_URL ?>/admin/profil.php"
       class="nav-item <?= $current=='profil.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-user-circle"></i></span> Profil
    </a>
    <a href="<?= APP_URL ?>/admin/ubah-password.php"
       class="nav-item <?= $current=='ubah-password.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-shield-alt"></i></span> Keamanan
    </a>
<a href="<?= APP_URL ?>/admin/tempat-sampah.php"
       class="nav-item <?= $current=='tempat-sampah.php'?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-trash-alt"></i></span>
      Riwayat
      <?php if ($jml_sampah > 0): ?>
      <span class="nav-badge"><?= $jml_sampah ?></span>
      <?php endif; ?>
    </a>
    <div class="nav-section-label">Publik</div>
    <a href="<?= APP_URL ?>/index.php" target="_blank" class="nav-item">
      <span class="nav-icon"><i class="fas fa-external-link-alt"></i></span> Lihat Halaman Publik
    </a>

  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user" onclick="openLogoutModal()" style="cursor:pointer">
      <div class="user-avatar"><?= $admin_init ?></div>
      <div style="flex:1;overflow:hidden">
        <div style="font-size:.85rem;font-weight:700;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
          <?= htmlspecialchars($admin_name) ?>
        </div>
        <div style="font-size:.72rem;color:rgba(255,255,255,.5)">
          <i class="fas fa-sign-out-alt"></i> Logout
        </div>
      </div>
    </div>
  </div>
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
      <h3 style="font-size:1rem;font-weight:700;color:var(--text-primary);margin-bottom:8px">
        Yakin ingin keluar?
      </h3>
      <p style="font-size:.85rem;color:var(--text-muted);line-height:1.6">
        Anda akan keluar dari sesi admin.<br>Pastikan semua data sudah tersimpan.
      </p>
    </div>
    <div class="modal-footer" style="justify-content:center;gap:12px;padding-bottom:24px">
      <button class="btn btn-ghost" onclick="closeLogoutModal()" style="min-width:110px;justify-content:center">
        <i class="fas fa-times"></i> Batal
      </button>
      <a href="<?= APP_URL ?>/logout.php" class="btn btn-danger" style="min-width:110px;justify-content:center">
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
