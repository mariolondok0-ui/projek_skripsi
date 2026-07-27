<?php
require_once '../includes/config.php';
requireLogin();

$admin_id = (int)$_SESSION['admin_id'];
$admin    = $conn->query("SELECT * FROM users WHERE id=$admin_id")->fetch_assoc();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw_lama  = $_POST['password_lama']  ?? '';
    $pw_baru  = $_POST['password_baru']  ?? '';
    $pw_ulang = $_POST['password_ulang'] ?? '';

    if (!password_verify($pw_lama, $admin['password'])) {
        setAlert('danger', 'Password lama yang Anda masukkan salah.');
    } elseif (strlen($pw_baru) < 6) {
        setAlert('danger', 'Password baru minimal 6 karakter.');
    } elseif ($pw_baru !== $pw_ulang) {
        setAlert('danger', 'Konfirmasi password tidak cocok dengan password baru.');
    } elseif ($pw_baru === $pw_lama) {
        setAlert('danger', 'Password baru tidak boleh sama dengan password lama.');
    } else {
        $hash = password_hash($pw_baru, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hash' WHERE id=$admin_id");
        setAlert('success', 'Password berhasil diubah! Silakan gunakan password baru Anda.');
        redirect(APP_URL . '/admin/ubah-password.php');
    }
    redirect(APP_URL . '/admin/ubah-password.php');
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Ubah Password – <?= APP_NAME ?></title>
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
        <span class="bc-item active">Ubah Password</span>
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
          <h1 class="page-title"><i class="fas fa-lock"></i> Ubah Password</h1>
          <p class="page-subtitle">Perbarui password akun administrator Anda</p>
        </div>
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-ghost">
          <i class="fas fa-arrow-left"></i> Kembali
        </a>
      </div>
    </div>

    <div style="max-width:860px;display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">

      <!-- Form Ubah Password -->
      <div class="card animate-fadeIn">
        <div class="card-header">
          <div class="card-title"><i class="fas fa-key"></i> Form Ubah Password</div>
        </div>
        <div class="card-body">
          <form method="POST" id="formPassword" autocomplete="off">

            <!-- Password Lama -->
            <div class="form-group">
              <label class="form-label">Password Lama <span class="required">*</span></label>
              <div class="input-group" style="position:relative">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password_lama" id="pwLama"
                       class="form-control" placeholder="Masukkan password saat ini" required>
                <button type="button" class="toggle-pwd" data-target="pwLama"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:.9rem;padding:4px">
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
                       class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                <button type="button" class="toggle-pwd" data-target="pwBaru"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:.9rem;padding:4px">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <!-- Strength Bar -->
              <div style="margin-top:10px">
                <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                  <span style="font-size:.72rem;color:var(--text-muted)">Kekuatan password</span>
                  <span id="strengthLabel" style="font-size:.72rem;font-weight:600;color:var(--text-muted)">–</span>
                </div>
                <div class="progress-bar-wrap">
                  <div class="progress-bar" id="strengthBar" style="width:0%;background:var(--border)"></div>
                </div>
              </div>
              <div class="form-text">Gunakan kombinasi huruf besar, kecil, angka, dan simbol</div>
            </div>

            <!-- Konfirmasi Password -->
            <div class="form-group">
              <label class="form-label">Konfirmasi Password Baru <span class="required">*</span></label>
              <div class="input-group" style="position:relative">
                <i class="fas fa-check-circle input-icon"></i>
                <input type="password" name="password_ulang" id="pwUlang"
                       class="form-control" placeholder="Ulangi password baru" required>
                <button type="button" class="toggle-pwd" data-target="pwUlang"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:.9rem;padding:4px">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <div id="matchMsg" style="font-size:.75rem;margin-top:6px;min-height:18px"></div>
            </div>

            <div style="display:flex;gap:12px;margin-top:8px">
              <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="fas fa-shield-alt"></i> Ubah Password
              </button>
              <button type="reset" class="btn btn-ghost" onclick="resetForm()">
                <i class="fas fa-undo"></i> Reset
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Sisi Kanan: Info Akun + Tips -->
      <div style="display:flex;flex-direction:column;gap:20px">

        <!-- Info Akun -->
        <div class="card animate-fadeIn delay-1">
          <div class="card-body" style="padding:24px;text-align:center">
            <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800;color:#fff;margin:0 auto 16px;box-shadow:0 6px 18px rgba(26,122,74,.3)">
              <?= strtoupper(substr($admin['nama'],0,1)) ?>
            </div>
            <h3 style="font-size:1rem;font-weight:800;color:var(--text-primary);margin-bottom:4px"><?= htmlspecialchars($admin['nama']) ?></h3>
            <p style="font-size:.82rem;color:var(--text-muted)"><?= htmlspecialchars($admin['email']) ?></p>
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-light)">
              <div style="font-size:.78rem;color:var(--text-muted)">Bergabung sejak</div>
              <div style="font-size:.9rem;font-weight:600;color:var(--primary);margin-top:3px"><?= date('d F Y', strtotime($admin['created_at'])) ?></div>
            </div>
          </div>
        </div>

        <!-- Tips Keamanan -->
        <div class="card animate-fadeIn delay-2" style="background:linear-gradient(135deg,rgba(26,122,74,.05),rgba(13,148,136,.05));border:1px solid rgba(26,122,74,.15)">
          <div class="card-body" style="padding:22px">
            <h4 style="font-size:.9rem;font-weight:700;color:var(--primary);margin-bottom:14px">
              <i class="fas fa-lightbulb" style="color:var(--secondary)"></i> Tips Password Aman
            </h4>
            <ul style="display:flex;flex-direction:column;gap:10px">
              <?php $tips = [
                'Gunakan minimal 8 karakter untuk keamanan optimal',
                'Kombinasikan huruf besar, kecil, angka, dan simbol',
                'Jangan gunakan informasi pribadi (nama, tanggal lahir)',
                'Ganti password secara berkala setiap 3 bulan',
                'Jangan gunakan password yang sama di platform lain',
              ]; foreach ($tips as $tip): ?>
              <li style="display:flex;align-items:flex-start;gap:8px;font-size:.82rem;color:var(--text-secondary)">
                <i class="fas fa-check-circle" style="color:var(--success);margin-top:2px;flex-shrink:0"></i>
                <?= $tip ?>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>

        <!-- Checklist Password Baru -->
        <div class="card animate-fadeIn delay-3">
          <div class="card-body" style="padding:22px">
            <h4 style="font-size:.9rem;font-weight:700;color:var(--text-primary);margin-bottom:14px">
              <i class="fas fa-tasks" style="color:var(--primary)"></i> Kriteria Password
            </h4>
            <ul id="passwordChecklist" style="display:flex;flex-direction:column;gap:8px">
              <li class="check-item" id="chk-len"   style="display:flex;align-items:center;gap:8px;font-size:.82rem;color:var(--text-muted)"><i class="fas fa-circle" style="font-size:.5rem"></i> Minimal 6 karakter</li>
              <li class="check-item" id="chk-upper" style="display:flex;align-items:center;gap:8px;font-size:.82rem;color:var(--text-muted)"><i class="fas fa-circle" style="font-size:.5rem"></i> Mengandung huruf besar</li>
              <li class="check-item" id="chk-num"   style="display:flex;align-items:center;gap:8px;font-size:.82rem;color:var(--text-muted)"><i class="fas fa-circle" style="font-size:.5rem"></i> Mengandung angka</li>
              <li class="check-item" id="chk-sym"   style="display:flex;align-items:center;gap:8px;font-size:.82rem;color:var(--text-muted)"><i class="fas fa-circle" style="font-size:.5rem"></i> Mengandung simbol (!@#$...)</li>
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
    inp.type = inp.type === 'password' ? 'text' : 'password';
    ico.classList.toggle('fa-eye');
    ico.classList.toggle('fa-eye-slash');
  });
});

// Password strength + checklist
document.getElementById('pwBaru').addEventListener('input', function () {
  const v = this.value;
  let score = 0;

  const chkLen   = v.length >= 6;
  const chkUpper = /[A-Z]/.test(v);
  const chkNum   = /[0-9]/.test(v);
  const chkSym   = /[^A-Za-z0-9]/.test(v);

  if (chkLen)   score++;
  if (v.length >= 8) score++;
  if (chkUpper) score++;
  if (chkNum)   score++;
  if (chkSym)   score++;

  // Update checklist
  updateCheck('chk-len',   chkLen);
  updateCheck('chk-upper', chkUpper);
  updateCheck('chk-num',   chkNum);
  updateCheck('chk-sym',   chkSym);

  // Strength bar
  const bar   = document.getElementById('strengthBar');
  const label = document.getElementById('strengthLabel');
  const pct   = (score / 5) * 100;
  bar.style.width = pct + '%';
  bar.style.transition = 'width .4s ease, background .4s';

  if (!v.length) {
    bar.style.background = 'var(--border)';
    label.textContent = '–'; label.style.color = 'var(--text-muted)';
  } else if (score <= 1) {
    bar.style.background = 'var(--danger)';
    label.textContent = 'Sangat Lemah'; label.style.color = 'var(--danger)';
  } else if (score <= 2) {
    bar.style.background = 'var(--warning)';
    label.textContent = 'Lemah'; label.style.color = 'var(--warning)';
  } else if (score <= 3) {
    bar.style.background = '#f59e0b';
    label.textContent = 'Sedang'; label.style.color = '#f59e0b';
  } else if (score <= 4) {
    bar.style.background = 'var(--info)';
    label.textContent = 'Kuat'; label.style.color = 'var(--info)';
  } else {
    bar.style.background = 'var(--success)';
    label.textContent = 'Sangat Kuat'; label.style.color = 'var(--success)';
  }

  // Re-check match
  checkMatch();
});

function updateCheck(id, pass) {
  const el  = document.getElementById(id);
  const ico = el.querySelector('i');
  if (pass) {
    el.style.color = 'var(--success)';
    ico.className  = 'fas fa-check-circle';
    ico.style.fontSize = '.85rem';
  } else {
    el.style.color = 'var(--text-muted)';
    ico.className  = 'fas fa-circle';
    ico.style.fontSize = '.5rem';
  }
}

// Password match
document.getElementById('pwUlang').addEventListener('input', checkMatch);
function checkMatch() {
  const baru  = document.getElementById('pwBaru').value;
  const ulang = document.getElementById('pwUlang').value;
  const msg   = document.getElementById('matchMsg');
  if (!ulang) { msg.innerHTML = ''; return; }
  if (baru === ulang) {
    msg.innerHTML = '<i class="fas fa-check-circle" style="color:var(--success)"></i> <span style="color:var(--success)">Password cocok</span>';
  } else {
    msg.innerHTML = '<i class="fas fa-times-circle" style="color:var(--danger)"></i> <span style="color:var(--danger)">Password tidak cocok</span>';
  }
}

// Reset form
function resetForm() {
  document.getElementById('strengthBar').style.width = '0%';
  document.getElementById('strengthLabel').textContent = '–';
  document.getElementById('matchMsg').innerHTML = '';
  ['chk-len','chk-upper','chk-num','chk-sym'].forEach(id => updateCheck(id, false));
}

// Sidebar toggle
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
