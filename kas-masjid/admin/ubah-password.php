<?php
require_once '../includes/config.php';
requireLogin();

$admin_id = (int)$_SESSION['admin_id'];
$admin    = $conn->query("SELECT * FROM users WHERE id=$admin_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw_lama  = $_POST['password_lama']  ?? '';
    $pw_baru  = $_POST['password_baru']  ?? '';
    $pw_ulang = $_POST['password_ulang'] ?? '';

    if (!password_verify($pw_lama, $admin['password'])) {
        setAlert('danger', 'Password lama yang Anda masukkan salah.');
    } elseif (strlen($pw_baru) < 6) {
        setAlert('danger', 'Password baru minimal 6 karakter.');
    } elseif ($pw_baru !== $pw_ulang) {
        setAlert('danger', 'Konfirmasi password tidak cocok.');
    } elseif ($pw_baru === $pw_lama) {
        setAlert('danger', 'Password baru tidak boleh sama dengan password lama.');
    } else {
        $hash = password_hash($pw_baru, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hash' WHERE id=$admin_id");
        setAlert('success', 'Password berhasil diubah.');
        redirect(APP_URL.'/admin/ubah-password.php');
    }
    redirect(APP_URL.'/admin/ubah-password.php');
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Keamanan – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ===== SECURITY PAGE ===== */
.security-hero {
  background: linear-gradient(135deg, #1e1b4b 0%, #3730a3 60%, #1d4ed8 100%);
  border-radius: var(--radius-xl);
  padding: 28px 24px;
  color: #fff;
  position: relative;
  overflow: hidden;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  gap: 18px;
}
.security-hero::before {
  content: '';
  position: absolute;
  top: -30px; right: -30px;
  width: 120px; height: 120px;
  background: rgba(255,255,255,.07);
  border-radius: 50%;
}
.security-icon-wrap {
  width: 64px; height: 64px; flex-shrink: 0;
  background: rgba(255,255,255,.15);
  border: 2px solid rgba(255,255,255,.3);
  border-radius: 18px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.6rem;
  backdrop-filter: blur(6px);
}

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
  background: #3730a3;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: .9rem;
}
.form-card-header h3 { font-size: .9rem; font-weight: 700; color: var(--text-primary); }
.form-card-body { padding: 22px; }

/* Strength */
.strength-wrap { margin-top: 8px; }
.strength-bar-bg { height: 6px; background: var(--border-light); border-radius: 99px; overflow: hidden; }
.strength-bar-fill { height: 100%; border-radius: 99px; transition: width .4s ease, background .4s; width: 0%; }
.strength-label { font-size: .72rem; font-weight: 600; margin-top: 5px; }

/* Checklist */
.pw-checklist { display: flex; flex-direction: column; gap: 7px; }
.pw-check-item { display: flex; align-items: center; gap: 8px; font-size: .8rem; color: var(--text-muted); transition: color .2s; }
.pw-check-item.pass { color: var(--success); }
.pw-check-item i { width: 16px; text-align: center; flex-shrink: 0; font-size: .75rem; }

/* Tips */
.tip-item { display: flex; align-items: flex-start; gap: 8px; font-size: .8rem; color: var(--text-secondary); }
.tip-item i { color: var(--success); margin-top: 2px; flex-shrink: 0; }

@media (max-width: 640px) {
  .security-hero { flex-direction: column; text-align: center; padding: 22px 18px; }
  .security-icon-wrap { margin: 0 auto; }
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
        <span class="bc-item active">Keamanan</span>
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

    <!-- SECURITY HERO -->
    <div class="security-hero animate-fadeIn">
      <div class="security-icon-wrap"><i class="fas fa-shield-alt"></i></div>
      <div style="position:relative;z-index:1;flex:1">
        <div style="font-size:1.1rem;font-weight:800;margin-bottom:4px">Keamanan Akun</div>
        <div style="font-size:.82rem;opacity:.8;line-height:1.6">
          Ubah password secara berkala untuk menjaga keamanan akun administrator Anda.
        </div>
        <div style="margin-top:10px;display:flex;align-items:center;gap:8px">
          <div style="width:8px;height:8px;border-radius:50%;background:#4ade80;animation:pulse 2s infinite"></div>
          <span style="font-size:.75rem;opacity:.8">Akun aktif: <?= htmlspecialchars($admin['nama']) ?></span>
        </div>
      </div>
    </div>

    <!-- FORM UBAH PASSWORD -->
    <div class="form-card animate-fadeIn delay-1">
      <div class="form-card-header">
        <div class="fch-icon"><i class="fas fa-key"></i></div>
        <div>
          <h3>Ubah Password</h3>
          <div style="font-size:.75rem;color:var(--text-muted)">Masukkan password lama dan password baru</div>
        </div>
      </div>
      <div class="form-card-body">
        <form method="POST" autocomplete="off">

          <!-- Password Lama -->
          <div class="form-group">
            <label class="form-label">Password Lama <span class="required">*</span></label>
            <div class="input-group" style="position:relative">
              <i class="fas fa-lock input-icon"></i>
              <input type="password" name="password_lama" id="pwLama"
                     class="form-control" placeholder="Masukkan password saat ini" required
                     style="padding-right:44px">
              <button type="button" class="toggle-pw" data-target="pwLama"
                      style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:.9rem;padding:4px;z-index:2">
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
                     style="padding-right:44px">
              <button type="button" class="toggle-pw" data-target="pwBaru"
                      style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:.9rem;padding:4px;z-index:2">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <!-- Strength Meter -->
            <div class="strength-wrap">
              <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                <span style="font-size:.72rem;color:var(--text-muted)">Kekuatan password</span>
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
                     style="padding-right:44px">
              <button type="button" class="toggle-pw" data-target="pwUlang"
                      style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:.9rem;padding:4px;z-index:2">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <div id="matchMsg" style="font-size:.75rem;margin-top:6px;min-height:18px"></div>
          </div>

          <button type="submit" class="btn btn-primary w-100" style="justify-content:center;height:46px">
            <i class="fas fa-shield-alt"></i> Simpan Password Baru
          </button>
        </form>
      </div>
    </div>

    <!-- CHECKLIST + TIPS -->
    <div class="form-card animate-fadeIn delay-2">
      <div class="form-card-header">
        <div class="fch-icon" style="background:var(--success)"><i class="fas fa-tasks"></i></div>
        <div>
          <h3>Kriteria & Tips Password</h3>
          <div style="font-size:.75rem;color:var(--text-muted)">Password yang kuat melindungi akun Anda</div>
        </div>
      </div>
      <div class="form-card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
          <!-- Checklist real-time -->
          <div>
            <div style="font-size:.8rem;font-weight:700;color:var(--text-primary);margin-bottom:10px">
              <i class="fas fa-clipboard-check" style="color:var(--primary);margin-right:5px"></i>Syarat Password
            </div>
            <div class="pw-checklist">
              <div class="pw-check-item" id="chk-len"><i class="fas fa-circle"></i> Minimal 6 karakter</div>
              <div class="pw-check-item" id="chk-upper"><i class="fas fa-circle"></i> Huruf besar (A-Z)</div>
              <div class="pw-check-item" id="chk-num"><i class="fas fa-circle"></i> Angka (0-9)</div>
              <div class="pw-check-item" id="chk-sym"><i class="fas fa-circle"></i> Simbol (!@#$...)</div>
            </div>
          </div>
          <!-- Tips -->
          <div>
            <div style="font-size:.8rem;font-weight:700;color:var(--text-primary);margin-bottom:10px">
              <i class="fas fa-lightbulb" style="color:var(--secondary);margin-right:5px"></i>Tips Aman
            </div>
            <div style="display:flex;flex-direction:column;gap:8px">
              <div class="tip-item"><i class="fas fa-check"></i>Jangan pakai nama/tanggal lahir</div>
              <div class="tip-item"><i class="fas fa-check"></i>Ganti setiap 3 bulan sekali</div>
              <div class="tip-item"><i class="fas fa-check"></i>Minimal 8 karakter lebih aman</div>
              <div class="tip-item"><i class="fas fa-check"></i>Jangan gunakan password sama di tempat lain</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- KEMBALI -->
    <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-ghost w-100" style="justify-content:center">
      <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
    </a>

  </div>
</div>
</div>

<script>
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
    { max:5, bg:'#10b981', txt:'Sangat Kuat' },
  ];
  const lvl = levels.find(l => score <= l.max) || levels[4];
  if (v.length === 0) { bar.style.background='var(--border)'; label.textContent='–'; label.style.color='var(--text-muted)'; }
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
    msg.innerHTML = '<i class="fas fa-check-circle" style="color:var(--success)"></i> <span style="color:var(--success)">Password cocok</span>';
  } else {
    msg.innerHTML = '<i class="fas fa-times-circle" style="color:var(--danger)"></i> <span style="color:var(--danger)">Password tidak cocok</span>';
  }
}

// Sidebar toggle
const sidebar=document.getElementById('adminSidebar');
const overlay=document.getElementById('sidebarOverlay');
document.getElementById('sidebarToggle').addEventListener('click',()=>{sidebar.classList.toggle('open');overlay.classList.toggle('active');});
overlay.addEventListener('click',()=>{sidebar.classList.remove('open');overlay.classList.remove('active');});
</script>
</body>
</html>
