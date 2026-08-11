<?php
require_once '../includes/config.php';
requireLogin();

$admin_id = (int)$_SESSION['admin_id'];
$admin    = $conn->query("SELECT * FROM users WHERE id=$admin_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_profil';

    // ---- LOGIKA UBAH PROFIL (NAMA & FOTO) ----
    if ($action === 'update_profil') {
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

    // ---- LOGIKA UBAH EMAIL ----
    if ($action === 'update_email') {
        $email = sanitize($_POST['email'] ?? '');
        
        if (empty($email)) {
            setAlert('danger', 'Email wajib diisi.');
        } else {
            // Cek apakah email dipakai oleh user/admin lain
            $cek = $conn->query("SELECT id FROM users WHERE email='$email' AND id!=$admin_id")->fetch_assoc();
            if ($cek) {
                setAlert('danger', 'Email sudah digunakan akun lain.');
            } else {
                $stmt = $conn->prepare("UPDATE users SET email=? WHERE id=?");
                $stmt->bind_param('si', $email, $admin_id);
                $stmt->execute();
                
                $_SESSION['admin_email'] = $email; // Update session email
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

        // PENGECEKAN PINTAR: Support Hash Modern, MD5, atau Teks Biasa
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
            // Simpan password baru dengan sistem HASH yang sangat aman
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
$bergabung   = date('d M Y', strtotime($admin['created_at']));
$alert       = getAlert();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Profil & Keamanan – <?= APP_NAME ?></title>
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
  flex-shrink: 0;
}
.form-card-header h3 { font-size: .9rem; font-weight: 700; color: var(--text-primary); margin: 0;}
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

/* CSS Tambahan Dari Keamanan */
.toggle-pw {
  position: absolute; 
  right: 6px; 
  top: 50%; 
  transform: translateY(-50%);
  background: none;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  font-size: 1rem;
  padding: 10px 12px;
  z-index: 2;
  border-radius: 8px;
  transition: background 0.2s;
}
.toggle-pw:active { background: rgba(0,0,0,0.05); }

.strength-wrap { margin-top: 8px; }
.strength-bar-bg { height: 6px; background: var(--border-light); border-radius: 99px; overflow: hidden; }
.strength-bar-fill { height: 100%; border-radius: 99px; transition: width .4s ease, background .4s; width: 0%; }
.strength-label { font-size: .75rem; font-weight: 600; margin-top: 5px; }

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.pw-checklist { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
.pw-check-item { display: flex; align-items: center; gap: 8px; font-size: .85rem; color: var(--text-muted); transition: color .2s; }
.pw-check-item.pass { color: var(--success); font-weight: 600; }
.pw-check-item i { width: 16px; text-align: center; flex-shrink: 0; font-size: .8rem; }

.tip-item { display: flex; align-items: flex-start; gap: 8px; font-size: .85rem; color: var(--text-secondary); }
.tip-item i { color: var(--success); margin-top: 3px; flex-shrink: 0; font-size: .8rem; }

@media (max-width: 640px) {
  .profile-hero { padding: 24px 18px; }
  .profile-avatar { width: 64px; height: 64px; font-size: 1.6rem; }
  .btn-edit-avatar { width: 24px; height: 24px; font-size: 0.7rem; }
  .profile-stat .ps-val { font-size: 1rem; }
  
  .info-grid { grid-template-columns: 1fr; gap: 24px; }
  .info-grid > div:first-child { padding-bottom: 20px; border-bottom: 1px dashed var(--border-light); }
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
        <span class="bc-item active">Profil & Keamanan</span>
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
          <!-- Gunakan input hidden untuk action form -->
          <input type="hidden" name="action" value="update_profil">
          
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

    <!-- FORM UBAH EMAIL (Diambil dari halaman Keamanan) -->
    <div class="form-card animate-fadeIn delay-2">
      <div class="form-card-header">
        <div class="fch-icon" style="background:#0284c7"><i class="fas fa-envelope"></i></div>
        <div>
          <h3>Ubah Alamat Email</h3>
          <div style="font-size:.75rem;color:var(--text-muted)">Ganti email yang digunakan untuk login</div>
        </div>
      </div>
      <div class="form-card-body">
        <form method="POST">
          <input type="hidden" name="action" value="update_email">
          
          <div class="form-group" style="margin-bottom:20px">
            <label class="form-label">Alamat Email Baru <span class="required">*</span></label>
            <div class="input-group">
              <i class="fas fa-envelope input-icon"></i>
              <input type="email" name="email" class="form-control" 
                     placeholder="Masukkan email baru" required>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100" style="justify-content:center;height:48px;background:#0284c7;border-color:#0284c7">
            <i class="fas fa-save"></i> Simpan Email Baru
          </button>
        </form>
      </div>
    </div>

    <!-- FORM UBAH PASSWORD (Diambil dari halaman Keamanan) -->
    <div class="form-card animate-fadeIn delay-3">
      <div class="form-card-header">
        <div class="fch-icon"><i class="fas fa-key"></i></div>
        <div>
          <h3>Ubah Password</h3>
          <div style="font-size:.75rem;color:var(--text-muted)">Masukkan password lama dan password baru</div>
        </div>
      </div>
      <div class="form-card-body">
        <form method="POST" autocomplete="off">
          <input type="hidden" name="action" value="update_password">

          <!-- Password Lama -->
          <div class="form-group">
            <label class="form-label">Password Lama <span class="required">*</span></label>
            <div class="input-group" style="position:relative">
              <i class="fas fa-lock input-icon"></i>
              <input type="password" name="password_lama" id="pwLama"
                     class="form-control" placeholder="Masukkan password saat ini" required
                     style="padding-right:50px">
              <button type="button" class="toggle-pw" data-target="pwLama">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>

          <!-- Password Baru -->
          <div class="form-group">
            <label class="form-label">Password Baru <span class="required">*</span></label>
            <div class="input-group" style="position:relative">
              <i class="fas fa-key input-icon"></i>
              <input type="password" name="password_baru" id="pwBaru"
                     class="form-control" placeholder="Minimal 6 karakter" required minlength="6"
                     style="padding-right:50px">
              <button type="button" class="toggle-pw" data-target="pwBaru">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <!-- Strength Meter -->
            <div class="strength-wrap">
              <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                <span style="font-size:.75rem;color:var(--text-muted)">Kekuatan password</span>
                <span class="strength-label" id="strengthLabel" style="color:var(--text-muted)">–</span>
              </div>
              <div class="strength-bar-bg">
                <div class="strength-bar-fill" id="strengthBar"></div>
              </div>
            </div>
          </div>

          <!-- Konfirmasi -->
          <div class="form-group" style="margin-bottom:24px">
            <label class="form-label">Konfirmasi Password Baru <span class="required">*</span></label>
            <div class="input-group" style="position:relative">
              <i class="fas fa-check-circle input-icon"></i>
              <input type="password" name="password_ulang" id="pwUlang"
                     class="form-control" placeholder="Ulangi password baru" required
                     style="padding-right:50px">
              <button type="button" class="toggle-pw" data-target="pwUlang">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <div id="matchMsg" style="font-size:.8rem;margin-top:6px;min-height:18px"></div>
          </div>

          <button type="submit" class="btn btn-primary w-100" style="justify-content:center;height:48px;background:#15803d;border-color:#15803d">
            <i class="fas fa-shield-alt"></i> Simpan Password Baru
          </button>
        </form>
      </div>
    </div>

    <!-- CHECKLIST + TIPS (Diambil dari halaman Keamanan) -->
    <div class="form-card animate-fadeIn delay-4">
      <div class="form-card-header">
        <div class="fch-icon" style="background:var(--success)"><i class="fas fa-tasks"></i></div>
        <div>
          <h3>Kriteria & Tips Password</h3>
          <div style="font-size:.75rem;color:var(--text-muted)">Panduan membuat password yang aman</div>
        </div>
      </div>
      <div class="form-card-body">
        <!-- class info-grid mengatur layout menyamping di PC, dan atas-bawah di HP -->
        <div class="info-grid">
          
          <!-- Checklist real-time -->
          <div>
            <div style="font-size:.85rem;font-weight:700;color:var(--text-primary);">
              <i class="fas fa-clipboard-check" style="color:var(--primary);margin-right:6px"></i>Syarat Minimal
            </div>
            <div class="pw-checklist">
              <div class="pw-check-item" id="chk-len"><i class="fas fa-circle"></i> Minimal 6 karakter</div>
              <div class="pw-check-item" id="chk-upper"><i class="fas fa-circle"></i> Mengandung huruf besar (A-Z)</div>
              <div class="pw-check-item" id="chk-num"><i class="fas fa-circle"></i> Mengandung angka (0-9)</div>
              <div class="pw-check-item" id="chk-sym"><i class="fas fa-circle"></i> Mengandung simbol (!@#$...)</div>
            </div>
          </div>
          
          <!-- Tips -->
          <div>
            <div style="font-size:.85rem;font-weight:700;color:var(--text-primary);margin-bottom:10px">
              <i class="fas fa-lightbulb" style="color:var(--secondary);margin-right:6px"></i>Tips Ekstra Aman
            </div>
            <div style="display:flex;flex-direction:column;gap:10px">
              <div class="tip-item"><i class="fas fa-check"></i>Jangan pakai nama atau tanggal lahir Anda</div>
              <div class="tip-item"><i class="fas fa-check"></i>Ganti password secara rutin minimal 3 bulan sekali</div>
              <div class="tip-item"><i class="fas fa-check"></i>Gunakan minimal 8 karakter agar lebih kebal</div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- TOMBOL KEMBALI -->
    <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn" style="background:var(--primary);color:#fff;border:none;">
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

// Toggle show/hide password
document.querySelectorAll('.toggle-pw').forEach(btn => {
  btn.addEventListener('click', () => {
    const inp = document.getElementById(btn.dataset.target);
    const ico = btn.querySelector('i');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    ico.classList.toggle('fa-eye');
    ico.classList.toggle('fa-eye-slash');
  });
});

// Password strength + checklist
document.getElementById('pwBaru').addEventListener('input', function () {
  const v = this.value;
  const chkLen   = v.length >= 6;
  const chkUpper = /[A-Z]/.test(v);
  const chkNum   = /[0-9]/.test(v);
  const chkSym   = /[^A-Za-z0-9]/.test(v);
  let score = [chkLen, v.length>=8, chkUpper, chkNum, chkSym].filter(Boolean).length;

  setCheck('chk-len',   chkLen);
  setCheck('chk-upper', chkUpper);
  setCheck('chk-num',   chkNum);
  setCheck('chk-sym',   chkSym);

  const bar   = document.getElementById('strengthBar');
  const label = document.getElementById('strengthLabel');
  const pct   = (score / 5) * 100;
  bar.style.width = pct + '%';

  const levels = [
    { max:1, bg:'#ef4444', txt:'Sangat Lemah' },
    { max:2, bg:'#f97316', txt:'Lemah' },
    { max:3, bg:'#f59e0b', txt:'Sedang' },
    { max:4, bg:'#3b82f6', txt:'Kuat' },
    { max:5, bg:'#3b82f6', txt:'Sangat Kuat' },
  ];
  const lvl = levels.find(l => score <= l.max) || levels[4];
  if (v.length === 0) { bar.style.background='var(--border-light)'; label.textContent='–'; label.style.color='var(--text-muted)'; }
  else { bar.style.background = lvl.bg; label.textContent = lvl.txt; label.style.color = lvl.bg; }

  checkMatch();
});

function setCheck(id, pass) {
  const el = document.getElementById(id);
  el.classList.toggle('pass', pass);
  el.querySelector('i').className = pass ? 'fas fa-check-circle' : 'fas fa-circle';
}

document.getElementById('pwUlang').addEventListener('input', checkMatch);
function checkMatch() {
  const baru  = document.getElementById('pwBaru').value;
  const ulang = document.getElementById('pwUlang').value;
  const msg   = document.getElementById('matchMsg');
  if (!ulang) { msg.innerHTML = ''; return; }
  if (baru === ulang) {
    msg.innerHTML = '<i class="fas fa-check-circle" style="color:var(--info)"></i> <span style="color:var(--info)">Password cocok</span>';
  } else {
    msg.innerHTML = '<i class="fas fa-times-circle" style="color:var(--danger)"></i> <span style="color:var(--danger)">Password tidak cocok</span>';
  }
}

// Sidebar logic
const sidebar=document.getElementById('adminSidebar');
const overlay=document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle').addEventListener('click',()=>{sidebar.classList.toggle('open');overlay.classList.toggle('active');});
overlay.addEventListener('click',()=>{sidebar.classList.remove('open');overlay.classList.remove('active');});
</script>
</body>
</html>