<?php
require_once '../includes/config.php';
requireLogin();

$admin_id = (int)$_SESSION['admin_id'];
$admin    = $conn->query("SELECT * FROM users WHERE id=$admin_id")->fetch_assoc();

// Fungsi Bulan Indonesia
function bulanIndo($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00') return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $ts = strtotime($tanggal);
    $tgl = date('d', $ts);
    $bln = $bulan[(int)date('m', $ts)];
    $thn = date('Y', $ts);
    return "$tgl $bln $thn";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_profil';

    // ---- LOGIKA UBAH PROFIL (NAMA & FOTO) ----
    if ($action === 'update_profil' || isset($_FILES['foto'])) {
        $nama  = sanitize($_POST['nama']  ?? $admin['nama']);
        $foto_nama = $admin['foto_profil']; 
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['foto']['tmp_name'];
            $file_name = $_FILES['foto']['name'];
            $file_size = $_FILES['foto']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($file_ext, $allowed_ext)) {
                setAlert('danger', 'Format foto tidak valid (hanya JPG/PNG/WEBP).');
                redirect(APP_URL.'/admin/profil.php');
                exit;
            } elseif ($file_size > 2097152) { 
                setAlert('danger', 'Ukuran foto maksimal 2MB.');
                redirect(APP_URL.'/admin/profil.php');
                exit;
            } else {
                $upload_dir = '../assets/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                if (!empty($admin['foto_profil']) && file_exists($upload_dir . $admin['foto_profil'])) {
                    unlink($upload_dir . $admin['foto_profil']);
                }

                $new_file_name = 'profil_' . $admin_id . '_' . time() . '.' . $file_ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $new_file_name)) {
                    $foto_nama = $new_file_name;
                }
            }
        }

        $stmt = $conn->prepare("UPDATE users SET nama=?, foto_profil=? WHERE id=?");
        $stmt->bind_param('ssi', $nama, $foto_nama, $admin_id);
        $stmt->execute();
        
        $_SESSION['admin_nama'] = $nama; 
        setAlert('success', 'Profil berhasil diperbarui.');
        redirect(APP_URL.'/admin/profil.php');
        exit;
    }

    // ---- LOGIKA UBAH EMAIL (DENGAN VALIDASI EMAIL LAMA) ----
    if ($action === 'update_email') {
        $email_lama = sanitize($_POST['email_lama'] ?? '');
        $email_baru = sanitize($_POST['email_baru'] ?? '');
        
        if (empty($email_lama) || empty($email_baru)) {
            setAlert('danger', 'Email lama dan email baru wajib diisi.');
        } elseif ($email_lama !== $admin['email']) {
            setAlert('danger', 'Email lama yang Anda masukkan tidak sesuai.');
        } elseif ($email_lama === $email_baru) {
            setAlert('danger', 'Email baru tidak boleh sama dengan email lama.');
        } else {
            $cek = $conn->query("SELECT id FROM users WHERE email='$email_baru' AND id!=$admin_id")->fetch_assoc();
            if ($cek) {
                setAlert('danger', 'Email sudah digunakan akun lain.');
            } else {
                $stmt = $conn->prepare("UPDATE users SET email=? WHERE id=?");
                $stmt->bind_param('si', $email_baru, $admin_id);
                $stmt->execute();
                
                $_SESSION['admin_email'] = $email_baru; 
                setAlert('success', 'Alamat email berhasil diperbarui.');
            }
        }
        redirect(APP_URL.'/admin/profil.php');
        exit;
    }

    // ---- LOGIKA UBAH PASSWORD ----
    if ($action === 'update_password') {
        $pw_lama  = $_POST['password_lama']  ?? '';
        $pw_baru  = $_POST['password_baru']  ?? '';
        $pw_ulang = $_POST['password_ulang'] ?? '';

        $db_pass = $admin['password'];
        $is_valid = false;

        if (password_verify($pw_lama, $db_pass)) {
            $is_valid = true;
        } elseif (md5($pw_lama) === $db_pass) {
            $is_valid = true;
        } elseif ($pw_lama === $db_pass) {
            $is_valid = true;
        }

        if (!$is_valid) {
            setAlert('danger', 'Password lama yang Anda masukkan salah.');
        } elseif (strlen($pw_baru) < 6) {
            setAlert('danger', 'Password baru minimal 6 karakter.');
        } elseif ($pw_baru !== $pw_ulang) {
            setAlert('danger', 'Konfirmasi password tidak cocok.');
        } elseif ($pw_baru === $pw_lama) {
            setAlert('danger', 'Password baru tidak boleh sama dengan password lama.');
        } else {
            $hash = password_hash($pw_baru, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param('si', $hash, $admin_id);
            
            if ($stmt->execute()) {
                setAlert('success', 'Password berhasil diubah dengan aman.');
            } else {
                setAlert('danger', 'Gagal mengubah password. Terjadi kesalahan sistem.');
            }
        }
        redirect(APP_URL.'/admin/profil.php');
        exit;
    }
}

$total_input = (int)$conn->query("SELECT COUNT(*) as c FROM transaksi WHERE user_id=$admin_id")->fetch_assoc()['c'];
$bergabung   = bulanIndo($admin['created_at']);
$alert       = getAlert();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>Profil & Keamanan – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background-color: var(--bg-body, #f8fafc); color: var(--text-main, #0f172a); margin: 0; }

/* TOPBAR */
.topbar {
  background: var(--bg-card, #fff); height: 65px; display: flex; align-items: center; justify-content: space-between;
  padding: 0 30px; border-bottom: 1px solid var(--border-color, #e2e8f0); z-index: 10; position: sticky; top: 0;
}
.breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 500; color: var(--text-muted, #64748b); }
.breadcrumb .active { color: var(--primary, #1e6eb5); font-weight: 600; }
.topbar-right { display: flex; align-items: center; gap: 15px; }
.topbar-date { font-size: 0.85rem; font-weight: 500; color: var(--text-muted); background: var(--bg-body); padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border-color); }

.topbar-user-static { 
    padding: 4px 10px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0;
    display: flex; align-items: center; gap: 8px; max-width: 160px;
}
.t-avatar { width: 32px; height: 32px; background: var(--primary, #1e6eb5); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: bold; overflow: hidden; flex-shrink: 0; }
.t-avatar img { width: 100%; height: 100%; object-fit: cover; }
.t-name { font-weight: 600; font-size: 0.82rem; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* PROFILE HERO */
.profile-hero {
  background: linear-gradient(135deg, #0f2d4a 0%, #1e6eb5 60%, #5ba3d9 100%);
  border-radius: 16px; padding: 28px 22px; color: #fff; position: relative; overflow: hidden; margin-bottom: 20px;
  box-shadow: 0 10px 25px rgba(30, 110, 181, 0.2);
}
.profile-hero::before { content: ''; position: absolute; top: -40px; right: -40px; width: 160px; height: 160px; background: rgba(255,255,255,.07); border-radius: 50%; }
.profile-hero::after { content: ''; position: absolute; bottom: -30px; left: -30px; width: 120px; height: 120px; background: rgba(201,168,76,.12); border-radius: 50%; }

.avatar-wrapper { position: relative; display: inline-block; flex-shrink: 0; }
.profile-avatar {
  width: 75px; height: 75px; border-radius: 50%; background: rgba(255,255,255,.2); border: 3px solid rgba(255,255,255,.4);
  display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 800; color: #fff;
  backdrop-filter: blur(8px); overflow: hidden; cursor: pointer; transition: transform 0.2s;
}
.profile-avatar:hover { transform: scale(1.03); }
.profile-avatar img { width: 100%; height: 100%; object-fit: cover; }

.btn-edit-avatar {
  position: absolute; bottom: 0; right: 0; width: 26px; height: 26px; background: #ffffff; color: #1e6eb5;
  border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;
  cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.2); border: 2px solid #1e6eb5; transition: transform 0.2s ease, background 0.2s; z-index: 10;
}
.btn-edit-avatar:hover { transform: scale(1.1); background: #f0fdf4; }

.profile-stat-row {
  display: flex; gap: 10px; margin-top: 18px;
}
.profile-stat {
  background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2); border-radius: 10px;
  padding: 10px 14px; text-align: center; flex: 1; min-width: 0; backdrop-filter: blur(4px);
}
.profile-stat .ps-val { font-size: 1.1rem; font-weight: 800; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.profile-stat .ps-lbl { font-size: 0.65rem; color: rgba(255,255,255,.8); margin-top: 2px; white-space: nowrap; }

/* TOMBOL AKSI MENU (RESPONSIF DAN RAPI DI HP) */
.settings-action-grid {
  display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;
}
.action-card-btn {
  background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px;
  cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.02); transition: all 0.2s ease; 
  display: flex; align-items: center; gap: 12px; flex: 1; min-width: 150px;
}
.action-card-btn:hover { border-color: var(--primary, #1e6eb5); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(30,110,181,0.08); background: #f8fafc; }
.action-card-btn .ac-icon { 
  width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0;
}
.action-card-btn .ac-title { font-size: 0.82rem; font-weight: 700; color: #0f172a; white-space: nowrap; }

.btn-profil .ac-icon { background: rgba(30,110,181,0.1); color: #1e6eb5; }
.btn-email .ac-icon { background: rgba(2,132,199,0.1); color: #0284c7; }
.btn-password .ac-icon { background: rgba(21,128,61,0.1); color: #15803d; }

/* RESPONSIVE POPUP MODAL */
.custom-modal-overlay {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
  z-index: 9998; display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; transition: all 0.3s ease; padding: 15px;
}
.custom-modal-overlay.active { opacity: 1; visibility: visible; }
.custom-modal-box {
  background: #ffffff; width: 100%; max-width: 480px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.15);
  overflow: hidden; transform: translateY(20px); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); max-height: 90vh; display: flex; flex-direction: column;
}
.custom-modal-overlay.active .custom-modal-box { transform: translateY(0); }
.custom-modal-header { padding: 18px 22px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: #f8fafc; }
.custom-modal-header h3 { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px; }
.custom-modal-close { background: none; border: none; font-size: 1.1rem; color: #64748b; cursor: pointer; padding: 5px; border-radius: 50%; transition: 0.2s; }
.custom-modal-close:hover { background: #e2e8f0; color: #ef4444; }
.custom-modal-body { padding: 22px; overflow-y: auto; }

.form-group { margin-bottom: 16px; }
.form-label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #0f172a; }
.form-control { width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; outline: none; transition: border-color 0.2s; }
.form-control:focus { border-color: var(--primary, #1e6eb5); }

/* Modal Lihat Foto */
.foto-modal-overlay {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 9999;
  display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; transition: all 0.3s ease; backdrop-filter: blur(5px);
}
.foto-modal-overlay.active { opacity: 1; visibility: visible; }
.foto-modal-img { max-width: 90%; max-height: 90%; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); transform: scale(0.8); transition: transform 0.3s ease; border: 4px solid #fff; }
.foto-modal-overlay.active .foto-modal-img { transform: scale(1); }
.foto-modal-close { position: absolute; top: 20px; right: 20px; color: #fff; font-size: 2rem; cursor: pointer; background: none; border: none; padding: 10px; transition: color 0.2s; }
.foto-modal-close:hover { color: #f87171; }

.toggle-pw {
  position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; font-size: 1rem; padding: 10px 12px; z-index: 2; border-radius: 8px; transition: background 0.2s;
}
.toggle-pw:active { background: rgba(0,0,0,0.05); }

.strength-wrap { margin-top: 8px; }
.strength-bar-bg { height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
.strength-bar-fill { height: 100%; border-radius: 99px; transition: width .4s ease, background .4s; width: 0%; }
.strength-label { font-size: .75rem; font-weight: 600; margin-top: 5px; }

/* PERBAIKAN RESPONSIF LAYAR HP AGAR TAMPILAN ELEGAN & PAS */
@media (max-width: 768px) {
  html, body { overflow-x: hidden !important; max-width: 100vw !important; }
  .admin-wrapper { display: block !important; width: 100% !important; overflow-x: hidden !important; }
  .admin-main { width: 100% !important; margin-left: 0 !important; padding: 0 !important; box-sizing: border-box !important; }
  
  .topbar { padding: 0 12px !important; }
  .topbar-date { display: none !important; }
  .admin-content { padding: 15px 12px !important; width: 100% !important; box-sizing: border-box !important; }
  
  /* Kartu hero profil tersusun rapi secara terpusat di HP */
  .profile-hero { padding: 22px 16px !important; }
  .profile-hero .profile-top-content {
    flex-direction: column !important;
    align-items: center !important;
    text-align: center !important;
  }
  .profile-hero .profile-info-text {
    width: 100% !important;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .profile-hero .profile-info-text div {
    white-space: normal !important;
    overflow: visible !important;
    text-overflow: clip !important;
  }
  
  /* Menu aksi tombol di bawah agar tersusun vertikal penuh di HP dan nyaman disentuh */
  .settings-action-grid { flex-direction: column !important; gap: 8px !important; }
  .action-card-btn { width: 100% !important; justify-content: flex-start !important; }
}
</style>
</head>
<body>
<div class="admin-wrapper">
<?php include '../includes/partials/sidebar-admin.php'; ?>
<div class="admin-main">

  <!-- TOPBAR -->
  <div class="topbar">
    <div style="display:flex; align-items:center; gap:8px; min-width:0;">
      <div id="sidebarToggle" style="cursor:pointer; font-size:1.1rem; color:var(--text-muted); flex-shrink:0;"><i class="fas fa-bars"></i></div>
      <div class="breadcrumb" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
        <i class="fas fa-home"></i> <i class="fas fa-chevron-right" style="font-size:0.55rem; opacity:0.5;"></i> <span class="active">Profil</span>
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-date"><i class="fas fa-calendar-alt" style="margin-right: 4px;"></i> <?= bulanIndo(date('Y-m-d')) ?></div>
      
      <div class="topbar-user-static">
        <div class="t-avatar">
          <?php if (!empty($admin['foto_profil']) && file_exists('../assets/uploads/' . $admin['foto_profil'])): ?>
              <img src="<?= APP_URL ?>/assets/uploads/<?= htmlspecialchars($admin['foto_profil']) ?>" alt="Avatar">
          <?php else: ?>
              <?= strtoupper(substr($admin['nama'], 0, 1)) ?>
          <?php endif; ?>
        </div>
        <div class="t-name"><?= htmlspecialchars($admin['nama']) ?></div>
      </div>

    </div>
  </div>

  <div class="admin-content" style="max-width:750px; margin: 0 auto;">

    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>" style="padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 500; display: flex; align-items: center; gap: 10px; background: <?= $alert['type']=='success'?'#ecfdf5':'#fef2f2' ?>; color: <?= $alert['type']=='success'?'#065f46':'#991b1b' ?>; border: 1px solid <?= $alert['type']=='success'?'#a7f3d0':'#fecaca' ?>;">
      <i class="fas fa-<?= $alert['type']=='success'?'check-circle':'exclamation-circle' ?>"></i>
      <?= htmlspecialchars($alert['message']) ?>
    </div>
    <?php endif; ?>

    <!-- PROFILE HERO DENGAN FORM UPLOAD GALERI OTOMATIS -->
    <div class="profile-hero animate-fadeIn">
      <form method="POST" enctype="multipart/form-data" id="formAutoUpload">
        <input type="hidden" name="action" value="update_profil">
        <input type="file" name="foto" id="quickUploadFoto" accept="image/*" style="display:none;" onchange="document.getElementById('formAutoUpload').submit();">
      </form>

      <div style="position:relative;z-index:1">
        <div class="profile-top-content" style="display:flex;align-items:center;gap:16px;margin-bottom:18px;flex-wrap:wrap">
          <div class="avatar-wrapper">
            <div class="profile-avatar" id="avatarContainer" onclick="lihatFotoProfil()" title="Klik untuk lihat foto">
              <?php if (!empty($admin['foto_profil']) && file_exists('../assets/uploads/' . $admin['foto_profil'])): ?>
                  <img src="<?= APP_URL ?>/assets/uploads/<?= htmlspecialchars($admin['foto_profil']) ?>" alt="Avatar">
              <?php else: ?>
                  <?= strtoupper(substr($admin['nama'],0,1)) ?>
              <?php endif; ?>
            </div>
            <div class="btn-edit-avatar" onclick="document.getElementById('quickUploadFoto').click()" title="Ambil dari Galeri">
              <i class="fas fa-camera"></i>
            </div>
          </div>
          <div class="profile-info-text" style="min-width:0; flex:1;">
            <div style="font-size:1.15rem; font-weight:800; line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($admin['nama']) ?></div>
            <div style="font-size:.8rem; opacity:.85; margin-top:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><i class="fas fa-envelope" style="margin-right:5px"></i><?= htmlspecialchars($admin['email']) ?></div>
            <div style="margin-top:6px">
              <span style="background:rgba(255,255,255,.2);padding:3px 10px;border-radius:99px;font-size:.7rem;font-weight:600;border:1px solid rgba(255,255,255,.3)">
                <i class="fas fa-user-shield" style="margin-right:4px"></i> Administrator
              </span>
            </div>
          </div>
        </div>
        <div class="profile-stat-row">
          <div class="profile-stat">
            <div class="ps-val"><?= $total_input ?></div>
            <div class="ps-lbl"><i class="fas fa-database" style="margin-right:3px"></i>Total Input</div>
          </div>
          <div class="profile-stat">
            <div class="ps-val" style="font-size:.95rem"><?= $bergabung ?></div>
            <div class="ps-lbl"><i class="fas fa-calendar" style="margin-right:3px"></i>Bergabung Sejak</div>
          </div>
        </div>
      </div>
    </div>

    <!-- TOMBOL AKSI MENU -->
    <div class="settings-action-grid">
      <div class="action-card-btn btn-profil" onclick="openModal('modalProfil')">
        <div class="ac-icon"><i class="fas fa-user-edit"></i></div>
        <div class="ac-title">Edit Profil</div>
      </div>
      <div class="action-card-btn btn-email" onclick="openModal('modalEmail')">
        <div class="ac-icon"><i class="fas fa-envelope"></i></div>
        <div class="ac-title">Ubah Email</div>
      </div>
      <div class="action-card-btn btn-password" onclick="openModal('modalPassword')">
        <div class="ac-icon"><i class="fas fa-key"></i></div>
        <div class="ac-title">Ubah Password</div>
      </div>
    </div>

  </div>
</div>
</div>

<!-- ================= POPUP MODAL 1: EDIT PROFIL ================= -->
<div class="custom-modal-overlay" id="modalProfil">
  <div class="custom-modal-box">
    <div class="custom-modal-header">
      <h3><i class="fas fa-user-edit" style="color:var(--primary);"></i> Edit Informasi Profil</h3>
      <button class="custom-modal-close" onclick="closeModal('modalProfil')"><i class="fas fa-times"></i></button>
    </div>
    <div class="custom-modal-body">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_profil">

        <div class="form-group">
          <label class="form-label">Ganti Foto Profil (Galeri)</label>
          <input type="file" name="foto" accept="image/*" class="form-control" style="padding: 8px;">
        </div>

        <div class="form-group">
          <label class="form-label">Nama Lengkap <span style="color:red">*</span></label>
          <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($admin['nama']) ?>" placeholder="Masukkan nama lengkap" required>
        </div>
        
        <button type="submit" class="btn btn-primary w-100" style="width:100%; justify-content:center; height:45px; margin-top:10px; background:var(--primary); color:#fff; border:none; border-radius:10px; font-weight:600; cursor:pointer;">
          <i class="fas fa-save"></i> Simpan Perubahan
        </button>
      </form>
    </div>
  </div>
</div>

<!-- ================= POPUP MODAL 2: UBAH EMAIL (DENGAN EMAIL LAMA) ================= -->
<div class="custom-modal-overlay" id="modalEmail">
  <div class="custom-modal-box">
    <div class="custom-modal-header">
      <h3><i class="fas fa-envelope" style="color:#0284c7;"></i> Ubah Alamat Email</h3>
      <button class="custom-modal-close" onclick="closeModal('modalEmail')"><i class="fas fa-times"></i></button>
    </div>
    <div class="custom-modal-body">
      <form method="POST">
        <input type="hidden" name="action" value="update_email">
        
        <div class="form-group">
          <label class="form-label">Alamat Email Lama <span style="color:red">*</span></label>
          <input type="email" name="email_lama" class="form-control" placeholder="Masukkan email lama" required>
        </div>

        <div class="form-group">
          <label class="form-label">Alamat Email Baru <span style="color:red">*</span></label>
          <input type="email" name="email_baru" class="form-control" placeholder="Masukkan email baru" required>
        </div>

        <button type="submit" class="btn btn-primary w-100" style="width:100%; justify-content:center; height:45px; margin-top:10px; background:#0284c7; color:#fff; border:none; border-radius:10px; font-weight:600; cursor:pointer;">
          <i class="fas fa-save"></i> Simpan Email Baru
        </button>
      </form>
    </div>
  </div>
</div>

<!-- ================= POPUP MODAL 3: UBAH PASSWORD ================= -->
<div class="custom-modal-overlay" id="modalPassword">
  <div class="custom-modal-box">
    <div class="custom-modal-header">
      <h3><i class="fas fa-key" style="color:#15803d;"></i> Ubah Password</h3>
      <button class="custom-modal-close" onclick="closeModal('modalPassword')"><i class="fas fa-times"></i></button>
    </div>
    <div class="custom-modal-body">
      <form method="POST" autocomplete="off">
        <input type="hidden" name="action" value="update_password">

        <div class="form-group">
          <label class="form-label">Password Lama <span style="color:red">*</span></label>
          <div style="position:relative">
            <input type="password" name="password_lama" id="pwLama" class="form-control" placeholder="Masukkan password saat ini" required style="padding-right:45px">
            <button type="button" class="toggle-pw" data-target="pwLama"><i class="fas fa-eye"></i></button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Password Baru <span style="color:red">*</span></label>
          <div style="position:relative">
            <input type="password" name="password_baru" id="pwBaru" class="form-control" placeholder="Minimal 6 karakter" required minlength="6" style="padding-right:45px">
            <button type="button" class="toggle-pw" data-target="pwBaru"><i class="fas fa-eye"></i></button>
          </div>
          <div class="strength-wrap">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px">
              <span style="font-size:0.75rem;color:#64748b">Kekuatan password</span>
              <span class="strength-label" id="strengthLabel" style="color:#64748b">–</span>
            </div>
            <div class="strength-bar-bg"><div class="strength-bar-fill" id="strengthBar"></div></div>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:20px">
          <label class="form-label">Konfirmasi Password Baru <span style="color:red">*</span></label>
          <div style="position:relative">
            <input type="password" name="password_ulang" id="pwUlang" class="form-control" placeholder="Ulangi password baru" required style="padding-right:45px">
            <button type="button" class="toggle-pw" data-target="pwUlang"><i class="fas fa-eye"></i></button>
          </div>
          <div id="matchMsg" style="font-size:0.8rem;margin-top:4px;min-height:16px"></div>
        </div>

        <button type="submit" class="btn btn-primary w-100" style="width:100%; justify-content:center; height:45px; background:#15803d; color:#fff; border:none; border-radius:10px; font-weight:600; cursor:pointer;">
          <i class="fas fa-shield-alt"></i> Simpan Password Baru
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Modal Lihat Foto Full -->
<div class="foto-modal-overlay" id="modalLihatFoto">
  <button class="foto-modal-close" onclick="closeFotoModal()"><i class="fas fa-times"></i></button>
  <img src="" class="foto-modal-img" id="imgModalPreview" alt="Foto Profil Full">
</div>

<script>
function openModal(modalId) { document.getElementById(modalId).classList.add('active'); }
function closeModal(modalId) { document.getElementById(modalId).classList.remove('active'); }

document.querySelectorAll('.custom-modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
});

function lihatFotoProfil() {
    const avatarContainer = document.getElementById('avatarContainer');
    const imgElement = avatarContainer.querySelector('img');
    if (imgElement) {
        document.getElementById('imgModalPreview').src = imgElement.src;
        document.getElementById('modalLihatFoto').classList.add('active');
    }
}
function closeFotoModal() { document.getElementById('modalLihatFoto').classList.remove('active'); }
document.getElementById('modalLihatFoto').addEventListener('click', function(e) { if (e.target === this) closeFotoModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeFotoModal(); });

document.querySelectorAll('.toggle-pw').forEach(btn => {
  btn.addEventListener('click', () => {
    const inp = document.getElementById(btn.dataset.target);
    const ico = btn.querySelector('i');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    ico.classList.toggle('fa-eye');
    ico.classList.toggle('fa-eye-slash');
  });
});

const pwBaruInput = document.getElementById('pwBaru');
if (pwBaruInput) {
  pwBaruInput.addEventListener('input', function () {
    const v = this.value;
    const bar   = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');
    let score = [v.length >= 6, v.length >= 8, /[A-Z]/.test(v), /[0-9]/.test(v), /[^A-Za-z0-9]/.test(v)].filter(Boolean).length;
    const pct   = (score / 5) * 100;
    bar.style.width = pct + '%';

    const levels = [
      { max:1, bg:'#ef4444', txt:'Sangat Lemah' },
      { max:2, bg:'#f97316', txt:'Lemah' },
      { max:3, bg:'#f59e0b', txt:'Sedang' },
      { max:4, bg:'#3b82f6', txt:'Kuat' },
      { max:5, bg:'#10b981', txt:'Sangat Kuat' },
    ];
    const lvl = levels.find(l => score <= l.max) || levels[4];
    if (v.length === 0) { bar.style.background='#e2e8f0'; label.textContent='–'; label.style.color='#64748b'; }
    else { bar.style.background = lvl.bg; label.textContent = lvl.txt; label.style.color = lvl.bg; }

    checkMatch();
  });
}

const pwUlangInput = document.getElementById('pwUlang');
if (pwUlangInput) {
  pwUlangInput.addEventListener('input', checkMatch);
}

function checkMatch() {
  const baru  = document.getElementById('pwBaru').value;
  const ulang = document.getElementById('pwUlang').value;
  const msg   = document.getElementById('matchMsg');
  if (!ulang) { msg.innerHTML = ''; return; }
  if (baru === ulang) {
    msg.innerHTML = '<i class="fas fa-check-circle" style="color:#10b981;"></i> <span style="color:#10b981;">Password cocok</span>';
  } else {
    msg.innerHTML = '<i class="fas fa-times-circle" style="color:#ef4444;"></i> <span style="color:#ef4444;">Password tidak cocok</span>';
  }
}

const sidebar=document.getElementById('adminSidebar');
const overlay=document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle').addEventListener('click',()=>{sidebar.classList.toggle('open');overlay.classList.toggle('active');});
overlay.addEventListener('click',()=>{sidebar.classList.remove('open');overlay.classList.remove('active');});
</script>
</body>
</html>