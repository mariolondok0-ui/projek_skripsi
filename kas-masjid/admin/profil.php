<?php
require_once '../includes/config.php';
requireLogin();

$admin_id = (int)$_SESSION['admin_id'];
$admin    = $conn->query("SELECT * FROM users WHERE id=$admin_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = sanitize($_POST['nama']  ?? '');
    
    if (empty($nama)) {
        setAlert('danger', 'Nama wajib diisi.');
    } else {
        $foto_nama = $admin['foto_profil']; // Ambil nama foto lama
        
        // Cek apakah ada file foto yang di-upload
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['foto']['tmp_name'];
            $file_name = $_FILES['foto']['name'];
            $file_size = $_FILES['foto']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png'];

            // Validasi format dan ukuran (Max 2MB)
            if (!in_array($file_ext, $allowed_ext)) {
                setAlert('danger', 'Format foto tidak valid (hanya JPG/PNG).');
                redirect(APP_URL.'/admin/profil.php');
                exit;
            } elseif ($file_size > 2097152) { 
                setAlert('danger', 'Ukuran foto maksimal 2MB.');
                redirect(APP_URL.'/admin/profil.php');
                exit;
            } else {
                // Pastikan folder uploads tersedia
                $upload_dir = '../assets/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                // Hapus foto lama jika ada (agar server tidak penuh)
                if (!empty($admin['foto_profil']) && file_exists($upload_dir . $admin['foto_profil'])) {
                    unlink($upload_dir . $admin['foto_profil']);
                }

                // Generate nama unik dan pindahkan file
                $new_file_name = 'profil_' . $admin_id . '_' . time() . '.' . $file_ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $new_file_name)) {
                    $foto_nama = $new_file_name;
                }
            }
        }

        // Simpan perubahan ke database
        $stmt = $conn->prepare("UPDATE users SET nama=?, foto_profil=? WHERE id=?");
        $stmt->bind_param('ssi', $nama, $foto_nama, $admin_id);
        $stmt->execute();
        
        $_SESSION['admin_nama'] = $nama; // Update session nama
        setAlert('success', 'Profil berhasil diperbarui.');
    }
    redirect(APP_URL.'/admin/profil.php');
    exit;
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
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=1786264272">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ===== PROFILE PAGE ===== */
.profile-hero {
  background: linear-gradient(135deg, #0f2d4a 0%, #1e6eb5 60%, #5ba3d9 100%);
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

/* Penyesuaian Wrapper Avatar agar icon kamera bisa diposisikan pas */
.avatar-wrapper {
  position: relative;
  display: inline-block;
  flex-shrink: 0;
}
.profile-avatar {
  width: 80px; height: 80px;
  border-radius: 50%;
  background: rgba(255,255,255,.2);
  border: 3px solid rgba(255,255,255,.4);
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; font-weight: 800; color: #fff;
  backdrop-filter: blur(8px);
  overflow: hidden;
  cursor: pointer; /* Kursor pointer agar tahu bisa diklik */
  transition: transform 0.2s;
}
.profile-avatar:hover {
  transform: scale(1.03);
}
.profile-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Tombol Edit / Kamera menempel di foto */
.btn-edit-avatar {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 28px;
  height: 28px;
  background: #ffffff;
  color: #1e6eb5;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.8rem;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  border: 2px solid #1e6eb5;
  transition: transform 0.2s ease, background 0.2s;
  z-index: 10;
}
.btn-edit-avatar:hover {
  transform: scale(1.1);
  background: #f0fdf4;
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

/* Modal Lihat Foto */
.foto-modal-overlay {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.85); z-index: 9999;
  display: flex; align-items: center; justify-content: center;
  opacity: 0; visibility: hidden; transition: all 0.3s ease;
  backdrop-filter: blur(5px);
}
.foto-modal-overlay.active {
  opacity: 1; visibility: visible;
}
.foto-modal-img {
  max-width: 90%; max-height: 90%; border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.5);
  transform: scale(0.8); transition: transform 0.3s ease;
  border: 4px solid #fff;
}
.foto-modal-overlay.active .foto-modal-img {
  transform: scale(1);
}
.foto-modal-close {
  position: absolute; top: 20px; right: 20px;
  color: #fff; font-size: 2rem; cursor: pointer;
  background: none; border: none; padding: 10px;
  transition: color 0.2s;
}
.foto-modal-close:hover { color: #f87171; }

@media (max-width: 640px) {
  .profile-hero { padding: 24px 18px; }
  .profile-avatar { width: 64px; height: 64px; font-size: 1.6rem; }
  .btn-edit-avatar { width: 24px; height: 24px; font-size: 0.7rem; }
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
          
          <!-- FOTO PROFIL DENGAN ICON KAMERA -->
          <div class="avatar-wrapper">
            <div class="profile-avatar" id="avatarContainer" onclick="lihatFotoProfil()">
              <?php if (!empty($admin['foto_profil']) && file_exists('../assets/uploads/' . $admin['foto_profil'])): ?>
                  <img src="<?= APP_URL ?>/assets/uploads/<?= htmlspecialchars($admin['foto_profil']) ?>" alt="Avatar">
              <?php else: ?>
                  <?= strtoupper(substr($admin['nama'],0,1)) ?>
              <?php endif; ?>
            </div>
            <!-- Tombol Kamera (memicu input file hidden) -->
            <div class="btn-edit-avatar" onclick="document.getElementById('uploadFoto').click()" title="Ubah Foto Profil">
              <i class="fas fa-camera"></i>
            </div>
          </div>

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
        <form method="POST" enctype="multipart/form-data">
          
          <!-- INPUT FILE DISEMBUNYIKAN DI SINI -->
          <input type="file" name="foto" id="uploadFoto" accept="image/png, image/jpeg, image/jpg" style="display:none;">

          <div class="form-group" style="margin-bottom:24px">
            <label class="form-label">Nama Lengkap <span class="required">*</span></label>
            <div class="input-group">
              <i class="fas fa-user input-icon"></i>
              <input type="text" name="nama" class="form-control"
                     value="<?= htmlspecialchars($admin['nama']) ?>"
                     placeholder="Masukkan nama lengkap" required>
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

<!-- Modal Lihat Foto Full -->
<div class="foto-modal-overlay" id="modalLihatFoto">
  <button class="foto-modal-close" onclick="closeFotoModal()"><i class="fas fa-times"></i></button>
  <img src="" class="foto-modal-img" id="imgModalPreview" alt="Foto Profil Full">
</div>

<script>
// Fungsi Lihat Foto Besar
function lihatFotoProfil() {
    const avatarContainer = document.getElementById('avatarContainer');
    const imgElement = avatarContainer.querySelector('img');
    
    // Hanya buka modal jika ada tag gambar
    if (imgElement) {
        document.getElementById('imgModalPreview').src = imgElement.src;
        document.getElementById('modalLihatFoto').classList.add('active');
    }
}
function closeFotoModal() {
    document.getElementById('modalLihatFoto').classList.remove('active');
}
// Tutup modal jika klik di luar gambar
document.getElementById('modalLihatFoto').addEventListener('click', function(e) {
    if (e.target === this) closeFotoModal();
});
// Tutup modal dengan tombol Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeFotoModal(); });

// Live Preview Gambar saat dipilih dari file manager
document.getElementById('uploadFoto').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarContainer').innerHTML = '<img src="' + e.target.result + '" alt="Avatar">';
        }
        reader.readAsDataURL(this.files[0]);
    }
});

// Sidebar logic
const sidebar=document.getElementById('adminSidebar');
const overlay=document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle').addEventListener('click',()=>{sidebar.classList.toggle('open');overlay.classList.toggle('active');});
overlay.addEventListener('click',()=>{sidebar.classList.remove('open');overlay.classList.remove('active');});
</script>
</body>
</html>