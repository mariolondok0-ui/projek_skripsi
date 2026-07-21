<?php
require_once '../includes/config.php';
requireLogin();

$admin_id = (int)$_SESSION['admin_id'];
$admin    = $conn->query("SELECT * FROM users WHERE id=$admin_id")->fetch_assoc();

// Handle POST ubah profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profil') {
        $nama  = sanitize($_POST['nama']  ?? '');
        $email = sanitize($_POST['email'] ?? '');
        if (empty($nama) || empty($email)) {
            setAlert('danger', 'Nama dan email wajib diisi.');
        } else {
            // Cek email duplikat
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
    } elseif ($action === 'ubah_password') {
        $pw_lama = $_POST['password_lama']  ?? '';
        $pw_baru = $_POST['password_baru']  ?? '';
        $pw_ulang= $_POST['password_ulang'] ?? '';
        if (!password_verify($pw_lama, $admin['password'])) {
            setAlert('danger', 'Password lama salah.');
        } elseif (strlen($pw_baru) < 6) {
            setAlert('danger', 'Password baru minimal 6 karakter.');
        } elseif ($pw_baru !== $pw_ulang) {
            setAlert('danger', 'Konfirmasi password tidak cocok.');
        } else {
            $hash = password_hash($pw_baru, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password='$hash' WHERE id=$admin_id");
            setAlert('success', 'Password berhasil diubah.');
        }
    }
    redirect(APP_URL . '/admin/profil.php');
}

// Stats admin
$total_input = $conn->query("SELECT COUNT(*) as c FROM transaksi WHERE user_id=$admin_id")->fetch_assoc()['c'];
$bergabung   = date('d F Y', strtotime($admin['created_at']));
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Profil – <?= APP_NAME ?></title>
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

    <div class="page-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
  <div>
    <h1 class="page-title"><i class="fas fa-user-cog"></i> Profil Admin</h1>
    <p class="page-subtitle">Kelola informasi akun dan keamanan password</p>
  </div>
  <a href="javascript:history.back()" class="btn btn-ghost" style="border: 1.5px solid var(--border); background: var(--bg-card);">
    <i class="fas fa-arrow-left"></i> Kembali
  </a>
</div>
    <div class="grid-2" style="align-items:start;gap:24px">

      <!-- Profil Card -->
      <div style="display:flex;flex-direction:column;gap:20px">

        <!-- Avatar & Info -->
        <div class="card animate-fadeIn" style="text-align:center">
          <div class="card-body" style="padding:36px 24px">
            <div style="width:88px;height:88px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:2.2rem;font-weight:800;color:#fff;margin:0 auto 20px;box-shadow:0 8px 24px rgba(26,122,74,.3)">
              <?= strtoupper(substr($admin['nama'],0,1)) ?>
            </div>
            <h2 style="font-size:1.2rem;font-weight:800;margin-bottom:4px"><?= htmlspecialchars($admin['nama']) ?></h2>
            <p style="color:var(--text-muted);font-size:.875rem"><?= htmlspecialchars($admin['email']) ?></p>
            <div style="display:flex;justify-content:center;gap:24px;margin-top:20px;padding-top:20px;border-top:1px solid var(--border-light)">
              <div style="text-align:center">
                <div style="font-size:1.4rem;font-weight:800;color:var(--primary)"><?= $total_input ?></div>
                <div style="font-size:.75rem;color:var(--text-muted)">Total Input</div>
              </div>
              <div style="width:1px;background:var(--border-light)"></div>
              <div style="text-align:center">
                <div style="font-size:.9rem;font-weight:700;color:var(--primary)"><?= $bergabung ?></div>
                <div style="font-size:.75rem;color:var(--text-muted)">Bergabung Sejak</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Update Profil -->
        <div class="card animate-fadeIn delay-1">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-user-edit"></i> Edit Profil</div>
          </div>
          <div class="card-body">
            <form method="POST">
              <input type="hidden" name="action" value="update_profil">
              <div class="form-group">
                <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                <div class="input-group">
                  <i class="fas fa-user input-icon"></i>
                  <input type="text" name="nama" class="form-control"
                         value="<?= htmlspecialchars($admin['nama']) ?>" required>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Alamat Email <span class="required">*</span></label>
                <div class="input-group">
                  <i class="fas fa-envelope input-icon"></i>
                  <input type="email" name="email" class="form-control"
                         value="<?= htmlspecialchars($admin['email']) ?>" required>
                </div>
              </div>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Perubahan
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Ubah Password -->
      <div style="display:flex;flex-direction:column;gap:20px">
        <div class="card animate-fadeIn delay-2">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-lock"></i> Ubah Password</div>
          </div>
          <div class="card-body">
            <form method="POST" id="formPassword">
              <input type="hidden" name="action" value="ubah_password">
              <div class="form-group">
                <label class="form-label">Password Lama <span class="required">*</span></label>
                <div class="input-group" style="position:relative">
                  <i class="fas fa-lock input-icon"></i>
                  <input type="password" name="password_lama" id="pwLama" class="form-control" placeholder="Password saat ini" required>
                  <button type="button" class="toggle-pwd" data-target="pwLama" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;color:var(--text-muted);font-size:.9rem">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Password Baru <span class="required">*</span></label>
                <div class="input-group" style="position:relative">
                  <i class="fas fa-key input-icon"></i>
                  <input type="password" name="password_baru" id="pwBaru" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                  <button type="button" class="toggle-pwd" data-target="pwBaru" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;color:var(--text-muted);font-size:.9rem">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
                <!-- Strength bar -->
                <div style="margin-top:8px">
                  <div class="progress-bar-wrap"><div class="progress-bar green" id="strengthBar" style="width:0%"></div></div>
                  <div style="font-size:.72rem;color:var(--text-muted);margin-top:4px" id="strengthLabel">Masukkan password baru</div>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Konfirmasi Password Baru <span class="required">*</span></label>
                <div class="input-group" style="position:relative">
                  <i class="fas fa-check-circle input-icon"></i>
                  <input type="password" name="password_ulang" id="pwUlang" class="form-control" placeholder="Ulangi password baru" required>
                  <button type="button" class="toggle-pwd" data-target="pwUlang" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;color:var(--text-muted);font-size:.9rem">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
                <div id="matchMsg" style="font-size:.75rem;margin-top:5px"></div>
              </div>
              <button type="submit" class="btn btn-warning" style="background:var(--warning);color:#fff">
                <i class="fas fa-shield-alt"></i> Ubah Password
              </button>
            </form>
          </div>
        </div>

        <!-- Info Keamanan -->
        <div class="card animate-fadeIn delay-3" style="background:linear-gradient(135deg,rgba(26,122,74,.06),rgba(13,148,136,.06));border:1px solid rgba(26,122,74,.2)">
          <div class="card-body" style="padding:20px">
            <h4 style="font-size:.9rem;font-weight:700;color:var(--primary);margin-bottom:12px"><i class="fas fa-info-circle"></i> Tips Keamanan</h4>
            <ul style="padding-left:4px;display:flex;flex-direction:column;gap:8px">
              <?php foreach([
                ['fas fa-check','Gunakan kombinasi huruf besar, kecil, angka dan simbol'],
                ['fas fa-check','Password minimal 8 karakter untuk keamanan yang baik'],
                ['fas fa-check','Jangan gunakan informasi pribadi sebagai password'],
                ['fas fa-check','Ganti password secara berkala setiap 3 bulan'],
              ] as [$ico,$tip]): ?>
              <li style="display:flex;align-items:flex-start;gap:8px;font-size:.82rem;color:var(--text-secondary)">
                <i class="<?= $ico ?>" style="color:var(--success);margin-top:2px;flex-shrink:0"></i> <?= $tip ?>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</div>

<script>
// Toggle show/hide password
document.querySelectorAll('.toggle-pwd').forEach(btn => {
  btn.addEventListener('click', () => {
    const inp = document.getElementById(btn.dataset.target);
    const ico = btn.querySelector('i');
    inp.type = inp.type==='password' ? 'text' : 'password';
    ico.classList.toggle('fa-eye'); ico.classList.toggle('fa-eye-slash');
  });
});

// Password strength
document.getElementById('pwBaru').addEventListener('input', function() {
  const v = this.value;
  let score = 0;
  if (v.length >= 6)  score++;
  if (v.length >= 8)  score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;
  const bar = document.getElementById('strengthBar');
  const lbl = document.getElementById('strengthLabel');
  const pct = (score / 5) * 100;
  bar.style.width = pct + '%';
  if (score <= 1) { bar.style.background='var(--danger)';  lbl.textContent='Lemah'; lbl.style.color='var(--danger)'; }
  else if (score <= 3) { bar.style.background='var(--warning)'; lbl.textContent='Sedang'; lbl.style.color='var(--warning)'; }
  else { bar.style.background='var(--success)'; lbl.textContent='Kuat'; lbl.style.color='var(--success)'; }
});

// Password match check
document.getElementById('pwUlang').addEventListener('input', function() {
  const msg = document.getElementById('matchMsg');
  if (this.value === document.getElementById('pwBaru').value) {
    msg.innerHTML = '<i class="fas fa-check-circle" style="color:var(--success)"></i> <span style="color:var(--success)">Password cocok</span>';
  } else {
    msg.innerHTML = '<i class="fas fa-times-circle" style="color:var(--danger)"></i> <span style="color:var(--danger)">Password tidak cocok</span>';
  }
});

const sidebar = document.getElementById('adminSidebar');
const overlay = document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle').addEventListener('click',()=>{ sidebar.classList.toggle('open'); overlay.classList.toggle('active'); });
overlay.addEventListener('click',()=>{ sidebar.classList.remove('open'); overlay.classList.remove('active'); });
</script>
</body>
</html>
