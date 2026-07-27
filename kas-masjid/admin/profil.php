<?php
require_once '../includes/config.php';
requireLogin();

$admin_id = (int)$_SESSION['admin_id'];
$admin    = $conn->query("SELECT * FROM users WHERE id=$admin_id")->fetch_assoc();

// Handle POST update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = sanitize($_POST['nama']  ?? '');
    $email = sanitize($_POST['email'] ?? '');
    if (empty($nama) || empty($email)) {
        setAlert('danger', 'Nama dan email wajib diisi.');
    } else {
        $cek = $conn->query("SELECT id FROM users WHERE email='$email' AND id != $admin_id")->fetch_assoc();
        if ($cek) {
            setAlert('danger', 'Email sudah digunakan oleh akun lain.');
        } else {
            $stmt = $conn->prepare("UPDATE users SET nama=?, email=? WHERE id=?");
            $stmt->bind_param('ssi', $nama, $email, $admin_id);
            $stmt->execute();
            $_SESSION['admin_nama']  = $nama;
            $_SESSION['admin_email'] = $email;
            setAlert('success', 'Profil berhasil diperbarui.');
        }
    }
    redirect(APP_URL . '/admin/profil.php');
}

$total_input = (int)$conn->query("SELECT COUNT(*) as c FROM transaksi WHERE user_id=$admin_id")->fetch_assoc()['c'];
$bergabung   = date('d F Y', strtotime($admin['created_at']));
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Profil Admin – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="admin-wrapper">
<?php include '../includes/partials/sidebar-admin.php'; ?>
<div class="admin-main">

  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></div>
      <div class="breadcrumb">
        <span class="bc-item"><i class="fas fa-home"></i></span>
        <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
        <span class="bc-item">Pengaturan</span>
        <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
        <span class="bc-item active">Profil Admin</span>
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-date"><i class="fas fa-calendar-alt"></i> <?= date('d F Y') ?></div>
    </div>
  </div>

  <div class="admin-content">
    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>">
      <i class="fas fa-<?= $alert['type']=='success'?'check-circle':'exclamation-circle' ?>"></i>
      <?= htmlspecialchars($alert['message']) ?>
    </div>
    <?php endif; ?>

    <div class="page-header">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
          <h1 class="page-title"><i class="fas fa-user-cog"></i> Profil Admin</h1>
          <p class="page-subtitle">Kelola informasi akun administrator</p>
        </div>
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-ghost">
          <i class="fas fa-arrow-left"></i> Kembali
        </a>
      </div>
    </div>

    <div style="max-width:860px;display:flex;flex-direction:column;gap:24px">

      <!-- Avatar & Info Card -->
      <div class="card animate-fadeIn">
        <div class="card-body" style="padding:36px 28px">
          <div style="display:flex;align-items:center;gap:28px;flex-wrap:wrap">
            <!-- Avatar -->
            <div style="flex-shrink:0">
              <div style="width:96px;height:96px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:2.4rem;font-weight:800;color:#fff;box-shadow:0 8px 24px rgba(26,122,74,.3)">
                <?= strtoupper(substr($admin['nama'],0,1)) ?>
              </div>
            </div>
            <!-- Info -->
            <div style="flex:1;min-width:200px">
              <h2 style="font-size:1.4rem;font-weight:800;color:var(--text-primary);margin-bottom:4px"><?= htmlspecialchars($admin['nama']) ?></h2>
              <p style="color:var(--text-muted);font-size:.9rem;margin-bottom:16px"><?= htmlspecialchars($admin['email']) ?></p>
              <div style="display:flex;gap:24px;flex-wrap:wrap">
                <div style="text-align:center;padding:12px 20px;background:var(--bg-main);border-radius:var(--radius);min-width:90px">
                  <div style="font-size:1.5rem;font-weight:800;color:var(--primary)"><?= $total_input ?></div>
                  <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px">Total Input</div>
                </div>
                <div style="text-align:center;padding:12px 20px;background:var(--bg-main);border-radius:var(--radius);min-width:90px">
                  <div style="font-size:.95rem;font-weight:700;color:var(--secondary)"><?= $bergabung ?></div>
                  <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px">Bergabung Sejak</div>
                </div>
              </div>
            </div>
           
          </div>
        </div>
      </div>

      <!-- Edit Profil Form -->
      <div class="card animate-fadeIn delay-1">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-user-edit"></i> Edit Informasi Profil</div>
        </div>
        <div class="card-body">
          <form method="POST" id="formProfil">
            <div class="grid-2" style="gap:20px">
              <div class="form-group mb-0">
                <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                <div class="input-group">
                  <i class="fas fa-user input-icon"></i>
                  <input type="text" name="nama" class="form-control"
                         value="<?= htmlspecialchars($admin['nama']) ?>" required
                         placeholder="Masukkan nama lengkap">
                </div>
              </div>
              <div class="form-group mb-0">
                <label class="form-label">Alamat Email <span class="required">*</span></label>
                <div class="input-group">
                  <i class="fas fa-envelope input-icon"></i>
                  <input type="email" name="email" class="form-control"
                         value="<?= htmlspecialchars($admin['email']) ?>" required
                         placeholder="Masukkan email">
                </div>
              </div>
            </div>
            <div style="margin-top:24px;display:flex;gap:12px;align-items:center">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Perubahan
              </button>
              <button type="reset" class="btn btn-ghost">
                <i class="fas fa-undo"></i> Reset
              </button>
              <span style="font-size:.78rem;color:var(--text-muted);margin-left:8px">
                <i class="fas fa-info-circle" style="color:var(--info)"></i>
                Perubahan akan langsung diterapkan pada sesi aktif
              </span>
            </div>
          </form>
        </div>
      </div>

      <!-- Info Keamanan + Link ke Ubah Password -->
      <div class="card animate-fadeIn delay-2" style="background:linear-gradient(135deg,rgba(26,122,74,.05),rgba(13,148,136,.05));border:1px solid rgba(26,122,74,.15)">
        <div class="card-body" style="padding:24px 28px">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px">
            <div>
              <h4 style="font-size:.95rem;font-weight:700;color:var(--primary);margin-bottom:8px">
                <i class="fas fa-shield-alt"></i> Keamanan Akun
              </h4>
              <p style="font-size:.85rem;color:var(--text-secondary);line-height:1.6;max-width:500px">
                Pastikan password Anda kuat dan unik. Ganti password secara berkala untuk menjaga keamanan akun administrator.
              </p>
            </div>
            <a href="ubah-password.php" class="btn btn-primary" style="white-space:nowrap">
              <i class="fas fa-lock"></i> Keamanan
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</div>

<script>
const sidebar = document.getElementById('adminSidebar');
const overlay = document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle').addEventListener('click', () => {
  sidebar.classList.toggle('open'); overlay.classList.toggle('active');
});
overlay.addEventListener('click', () => {
  sidebar.classList.remove('open'); overlay.classList.remove('active');
});
</script>
</body>
</html>
