<?php
require_once '../includes/config.php';
requireLogin();

$admin_id = (int)$_SESSION['admin_id'];
$admin    = $conn->query("SELECT * FROM users WHERE id=$admin_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = sanitize($_POST['nama']  ?? '');
    $email = sanitize($_POST['email'] ?? '');
    if (empty($nama) || empty($email)) {
        setAlert('danger', 'Nama dan email wajib diisi.');
    } else {
        $cek = $conn->query("SELECT id FROM users WHERE email='$email' AND id!=$admin_id")->fetch_assoc();
        if ($cek) {
            setAlert('danger', 'Email sudah digunakan akun lain.');
        } else {
            $stmt = $conn->prepare("UPDATE users SET nama=?, email=? WHERE id=?");
            $stmt->bind_param('ssi', $nama, $email, $admin_id);
            $stmt->execute();
            $_SESSION['admin_nama']  = $nama;
            $_SESSION['admin_email'] = $email;
            setAlert('success', 'Profil berhasil diperbarui.');
        }
    }
    redirect(APP_URL.'/admin/profil.php');
}

$total_input = (int)$conn->query("SELECT COUNT(*) as c FROM transaksi WHERE user_id=$admin_id")->fetch_assoc()['c'];
$bergabung   = date('d M Y', strtotime($admin['created_at']));
$alert       = getAlert();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Profil – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=2026">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ===== PROFILE PAGE ===== */
.profile-hero {
  background: linear-gradient(135deg, #0f3d26 0%, #1a7a4a 60%, #0d9488 100%);
  border-radius: var(--radius-xl);
  padding: 32px 28px;
  color: #fff;
  position: relative;
  overflow: hidden;
  margin-bottom: 24px;
}
.profile-hero::before {
  content: '';
  position: absolute;
  top: -40px; right: -40px;
  width: 160px; height: 160px;
  background: rgba(255,255,255,.07);
  border-radius: 50%;
}
.profile-hero::after {
  content: '';
  position: absolute;
  bottom: -30px; left: -30px;
  width: 120px; height: 120px;
  background: rgba(201,168,76,.12);
  border-radius: 50%;
}
.profile-avatar {
  width: 80px; height: 80px;
  border-radius: 50%;
  background: rgba(255,255,255,.2);
  border: 3px solid rgba(255,255,255,.4);
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; font-weight: 800; color: #fff;
  flex-shrink: 0;
  backdrop-filter: blur(8px);
}
.profile-stat {
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.2);
  border-radius: var(--radius);
  padding: 12px 16px;
  text-align: center;
  flex: 1;
  min-width: 100px;
  backdrop-filter: blur(4px);
}
.profile-stat .ps-val { font-size: 1.2rem; font-weight: 800; color: #fff; }
.profile-stat .ps-lbl { font-size: .7rem; color: rgba(255,255,255,.7); margin-top: 2px; }

/* Form Card */
.form-card {
  background: var(--bg-card);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow);
  border: 1px solid var(--border-light);
  overflow: hidden;
  margin-bottom: 20px;
}
.form-card-header {
  padding: 18px 22px;
  border-bottom: 1px solid var(--border-light);
  display: flex; align-items: center; gap: 10px;
  background: var(--bg-main);
}
.form-card-header .fch-icon {
  width: 36px; height: 36px;
  border-radius: var(--radius-sm);
  background: var(--primary);
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: .9rem;
}
.form-card-header h3 { font-size: .9rem; font-weight: 700; color: var(--text-primary); }
.form-card-body { padding: 22px; }

@media (max-width: 640px) {
  .profile-hero { padding: 24px 18px; }
  .profile-avatar { width: 64px; height: 64px; font-size: 1.6rem; }
  .profile-stat .ps-val { font-size: 1rem; }
}
</style>
</head>
<body>
<div class="admin-wrapper">
<?php include '../includes/partials/sidebar-admin.php'; ?>
<div class="admin-main">

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-left">
      <div class="topbar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></div>
      <div class="breadcrumb">
        <span class="bc-item"><i class="fas fa-home"></i></span>
        <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
        <span class="bc-item">Pengaturan</span>
        <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
        <span class="bc-item active">Profil</span>
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-date"><i class="fas fa-calendar-alt"></i> <?= date('d M Y') ?></div>
    </div>
  </div>

  <div class="admin-content" style="max-width:680px">

    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>">
      <i class="fas fa-<?= $alert['type']=='success'?'check-circle':'exclamation-circle' ?>"></i>
      <?= htmlspecialchars($alert['message']) ?>
    </div>
    <?php endif; ?>

    <!-- PROFILE HERO -->
    <div class="profile-hero animate-fadeIn">
      <div style="position:relative;z-index:1">
        <div style="display:flex;align-items:center;gap:18px;margin-bottom:24px;flex-wrap:wrap">
          <div class="profile-avatar"><?= strtoupper(substr($admin['nama'],0,1)) ?></div>
          <div>
            <div style="font-size:1.2rem;font-weight:800;line-height:1.3"><?= htmlspecialchars($admin['nama']) ?></div>
            <div style="font-size:.82rem;opacity:.8;margin-top:3px"><i class="fas fa-envelope" style="margin-right:5px"></i><?= htmlspecialchars($admin['email']) ?></div>
            <div style="margin-top:8px">
              <span style="background:rgba(255,255,255,.2);padding:3px 12px;border-radius:99px;font-size:.72rem;font-weight:600;border:1px solid rgba(255,255,255,.3)">
                <i class="fas fa-user-shield" style="margin-right:4px"></i> Administrator
              </span>
            </div>
          </div>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <div class="profile-stat">
            <div class="ps-val"><?= $total_input ?></div>
            <div class="ps-lbl"><i class="fas fa-database" style="margin-right:3px"></i>Total Input</div>
          </div>
          <div class="profile-stat">
            <div class="ps-val" style="font-size:.85rem"><?= $bergabung ?></div>
            <div class="ps-lbl"><i class="fas fa-calendar" style="margin-right:3px"></i>Bergabung Sejak</div>
          </div>
        </div>
      </div>
    </div>

    <!-- FORM EDIT PROFIL -->
    <div class="form-card animate-fadeIn delay-1">
      <div class="form-card-header">
        <div class="fch-icon"><i class="fas fa-user-edit"></i></div>
        <div>
          <h3>Edit Informasi Profil</h3>
          <div style="font-size:.75rem;color:var(--text-muted)">Perubahan langsung aktif setelah disimpan</div>
        </div>
      </div>
      <div class="form-card-body">
        <form method="POST">
          <div class="form-group">
            <label class="form-label">Nama Lengkap <span class="required">*</span></label>
            <div class="input-group">
              <i class="fas fa-user input-icon"></i>
              <input type="text" name="nama" class="form-control"
                     value="<?= htmlspecialchars($admin['nama']) ?>"
                     placeholder="Masukkan nama lengkap" required>
            </div>
          </div>
          <div class="form-group" style="margin-bottom:24px">
            <label class="form-label">Alamat Email <span class="required">*</span></label>
            <div class="input-group">
              <i class="fas fa-envelope input-icon"></i>
              <input type="email" name="email" class="form-control"
                     value="<?= htmlspecialchars($admin['email']) ?>"
                     placeholder="Masukkan email" required>
            </div>
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">
              <i class="fas fa-save"></i> Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- TOMBOL KEMBALI -->
    <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-ghost w-100" style="justify-content:center">
      <i class="fas fa-arrow-left"></i> Kembali
    </a>

  </div>
</div>
</div>

<script>
const sidebar=document.getElementById('adminSidebar');
const overlay=document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle').addEventListener('click',()=>{sidebar.classList.toggle('open');overlay.classList.toggle('active');});
overlay.addEventListener('click',()=>{sidebar.classList.remove('open');overlay.classList.remove('active');});
</script>
</body>
</html>
