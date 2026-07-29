<?php
require_once 'includes/config.php';
if (isLoggedIn()) redirect(APP_URL.'/admin/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT id,nama,email,password FROM users WHERE email=?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_id']    = $user['id'];
            $_SESSION['admin_nama']  = $user['nama'];
            $_SESSION['admin_email'] = $user['email'];
            setAlert('success', 'Selamat datang, '.$user['nama'].'!');
            redirect(APP_URL.'/admin/dashboard.php');
        } else {
            $error = 'Email atau password salah. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Login Admin – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?= time() ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ===== LOGIN PAGE ===== */
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; }

.login-bg {
  min-height: 100vh;
  background: linear-gradient(135deg, #0b2e1c 0%, #1a7a4a 50%, #0d9488 100%);
  background-size: 200% 200%;
  animation: gradientShift 10s ease infinite;
  display: flex; align-items: center; justify-content: center;
  padding: 20px; position: relative; overflow: hidden;
}
.login-bg::before {
  content: ''; position: absolute;
  top: -100px; left: -100px;
  width: 400px; height: 400px;
  background: rgba(255,255,255,.04); border-radius: 50%;
}
.login-bg::after {
  content: ''; position: absolute;
  bottom: -80px; right: -80px;
  width: 300px; height: 300px;
  background: rgba(201,168,76,.08); border-radius: 50%;
}

/* Card wrapper */
.login-card {
  width: 100%; max-width: 900px;
  background: #fff;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 24px 64px rgba(0,0,0,.3);
  display: grid;
  grid-template-columns: 1fr 1fr;
  position: relative; z-index: 1;
  animation: scaleIn .5s cubic-bezier(.4,0,.2,1);
}

/* Left panel */
.login-left {
  background: linear-gradient(145deg, #0b2e1c 0%, #1a7a4a 100%);
  padding: 48px 36px;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  text-align: center; position: relative; overflow: hidden;
}
.login-left::before {
  content: ''; position: absolute;
  top: -60px; right: -60px;
  width: 200px; height: 200px;
  background: rgba(255,255,255,.05); border-radius: 50%;
}
.login-left::after {
  content: ''; position: absolute;
  bottom: -40px; left: -40px;
  width: 150px; height: 150px;
  background: rgba(201,168,76,.1); border-radius: 50%;
}
.ll-logo {
  width: 88px; height: 88px;
  background: rgba(255,255,255,.15);
  border: 2px solid rgba(255,255,255,.25);
  border-radius: 24px;
  display: flex; align-items: center; justify-content: center;
  font-size: 2.2rem; color: #fff;
  margin: 0 auto 20px;
  animation: float 3s ease-in-out infinite;
  position: relative; z-index: 1;
  backdrop-filter: blur(6px);
}
.ll-name {
  font-size: 1.3rem; font-weight: 800; color: #fff;
  line-height: 1.3; margin-bottom: 8px;
  position: relative; z-index: 1;
}
.ll-sub {
  font-size: .82rem; color: rgba(255,255,255,.65);
  line-height: 1.6; max-width: 220px; margin: 0 auto 28px;
  position: relative; z-index: 1;
}
.ll-features {
  display: flex; flex-direction: column; gap: 10px;
  width: 100%; max-width: 260px;
  position: relative; z-index: 1;
}
.ll-feat {
  display: flex; align-items: center; gap: 10px;
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 10px; padding: 10px 14px;
  text-align: left; animation: fadeInLeft .6s ease both;
}
.ll-feat .lf-icon {
  width: 32px; height: 32px; border-radius: 8px;
  background: var(--secondary); color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: .85rem; flex-shrink: 0;
}
.ll-feat .lf-t { font-size: .8rem; font-weight: 700; color: #fff; }
.ll-feat .lf-s { font-size: .7rem; color: rgba(255,255,255,.55); margin-top: 1px; }

/* Right panel */
.login-right {
  padding: 48px 40px;
  display: flex; flex-direction: column; justify-content: center;
}
.lr-head { margin-bottom: 32px; }
.lr-head h2 {
  font-size: 1.6rem; font-weight: 800;
  color: var(--text-primary); margin-bottom: 6px;
}
.lr-head p { font-size: .875rem; color: var(--text-muted); }

.form-group { margin-bottom: 18px; }
.form-label { display: block; font-size: .82rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 7px; }
.form-label span { color: var(--danger); }
.inp-wrap { position: relative; }
.inp-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: .9rem; z-index: 1; pointer-events: none; }
.inp { width: 100%; padding: 13px 14px 13px 42px; border: 1.5px solid var(--border); border-radius: 12px; font-size: .9rem; color: var(--text-primary); font-family: inherit; background: #fff; transition: all .2s; }
.inp:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(26,122,74,.1); outline: none; }
.inp::placeholder { color: var(--text-muted); }
.inp-pw { padding-right: 46px; }
.pw-toggle { position: absolute; right: 13px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: .9rem; padding: 4px; transition: color .15s; }
.pw-toggle:hover { color: var(--primary); }

.btn-login {
  width: 100%; padding: 14px;
  background: var(--primary); color: #fff;
  border: none; border-radius: 12px;
  font-size: .95rem; font-weight: 700;
  font-family: inherit; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  box-shadow: 0 6px 20px rgba(26,122,74,.35);
  transition: all .25s; margin-top: 8px;
}
.btn-login:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(26,122,74,.45); }
.btn-login:active { transform: scale(.98); }

.login-footer {
  margin-top: 24px; padding-top: 20px;
  border-top: 1px solid var(--border-light);
  text-align: center;
}
.back-link {
  font-size: .82rem; color: var(--text-muted);
  display: inline-flex; align-items: center; gap: 6px;
  text-decoration: none; transition: color .15s;
}
.back-link:hover { color: var(--primary); }
.demo-box {
  margin-top: 14px; padding: 12px 16px;
  background: linear-gradient(135deg, rgba(26,122,74,.06), rgba(13,148,136,.06));
  border: 1px solid rgba(26,122,74,.15);
  border-radius: 10px; font-size: .78rem; color: var(--text-secondary);
}

/* Alert */
.alert-error {
  background: rgba(239,68,68,.08);
  border: 1px solid rgba(239,68,68,.25);
  border-radius: 10px; padding: 12px 16px;
  font-size: .84rem; color: #991b1b;
  display: flex; align-items: center; gap: 8px;
  margin-bottom: 20px; animation: slideDown .3s ease;
}

/* Responsive */
@media (max-width: 720px) {
  .login-card { grid-template-columns: 1fr; max-width: 440px; }
  .login-left { display: none; }
  .login-right { padding: 36px 28px; }
}
@media (max-width: 480px) {
  .login-bg { padding: 16px; align-items: flex-start; padding-top: 40px; }
  .login-right { padding: 28px 22px; }
  .lr-head h2 { font-size: 1.4rem; }
}
</style>
</head>
<body>
<div class="login-bg">
  <div class="login-card">

    <!-- LEFT PANEL -->
    <div class="login-left">
      <div class="ll-logo"><i class="fas fa-mosque"></i></div>
      <div class="ll-name"><?= MASJID_NAME ?></div>
      <p class="ll-sub">Sistem Informasi Pengelolaan Keuangan Kas Masjid Berbasis Web</p>
      <div class="ll-features">
        <div class="ll-feat" style="animation-delay:.1s">
          <div class="lf-icon"><i class="fas fa-shield-alt"></i></div>
          <div><div class="lf-t">Aman & Terproteksi</div><div class="lf-s">Akses khusus admin resmi</div></div>
        </div>
        <div class="ll-feat" style="animation-delay:.2s">
          <div class="lf-icon"><i class="fas fa-chart-bar"></i></div>
          <div><div class="lf-t">Visualisasi Data</div><div class="lf-s">Grafik interaktif real-time</div></div>
        </div>
        <div class="ll-feat" style="animation-delay:.3s">
          <div class="lf-icon"><i class="fas fa-file-pdf"></i></div>
          <div><div class="lf-t">Ekspor PDF</div><div class="lf-s">Cetak laporan keuangan</div></div>
        </div>
        <div class="ll-feat" style="animation-delay:.4s">
          <div class="lf-icon"><i class="fas fa-trash-alt"></i></div>
          <div><div class="lf-t">Tempat Sampah</div><div class="lf-s">Data terhapus bisa dipulihkan</div></div>
        </div>
      </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="login-right">
      <div class="lr-head">
        <h2>Masuk Admin</h2>
        <p>Masukkan kredensial akun Anda untuk mengakses sistem</p>
      </div>

      <?php if ($error): ?>
      <div class="alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" id="loginForm" autocomplete="off">

        <!-- Email -->
        <div class="form-group">
          <label class="form-label">Email <span>*</span></label>
          <div class="inp-wrap">
            <i class="fas fa-envelope inp-icon"></i>
            <input type="email" name="email" class="inp"
                   placeholder="admin@masjid.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   required autocomplete="email">
          </div>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label class="form-label">Password <span>*</span></label>
          <div class="inp-wrap">
            <i class="fas fa-lock inp-icon"></i>
            <input type="password" name="password" id="pwdInput" class="inp inp-pw"
                   placeholder="Masukkan password" required autocomplete="current-password">
            <button type="button" id="pwdToggle" class="pw-toggle">
              <i class="fas fa-eye" id="pwdEye"></i>
            </button>
          </div>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-login" id="loginBtn">
          <span id="btnText"><i class="fas fa-sign-in-alt"></i> Masuk ke Sistem</span>
          <span id="btnLoad" style="display:none"><span class="spinner"></span> Memproses...</span>
        </button>

      </form>

      <div class="login-footer">
        <a href="<?= APP_URL ?>/index.php" class="back-link">
          <i class="fas fa-arrow-left"></i> Kembali ke Halaman Publik
        </a>
        <div class="demo-box">
          <i class="fas fa-info-circle" style="color:var(--info);margin-right:5px"></i>
          Demo: <strong>admin@masjid.com</strong> &nbsp;/&nbsp; <strong>password</strong>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
// Toggle show/hide password
document.getElementById('pwdToggle').addEventListener('click', () => {
  const inp = document.getElementById('pwdInput');
  const eye = document.getElementById('pwdEye');
  inp.type = inp.type === 'password' ? 'text' : 'password';
  eye.classList.toggle('fa-eye');
  eye.classList.toggle('fa-eye-slash');
});

// Loading state saat submit
document.getElementById('loginForm').addEventListener('submit', () => {
  document.getElementById('btnText').style.display = 'none';
  document.getElementById('btnLoad').style.display = 'inline-flex';
  document.getElementById('loginBtn').disabled = true;
});

// Fokus input animasi
document.querySelectorAll('.inp').forEach(inp => {
  inp.addEventListener('focus', () => {
    inp.closest('.inp-wrap').style.transform = 'scale(1.01)';
    inp.closest('.inp-wrap').style.transition = 'transform .2s';
  });
  inp.addEventListener('blur', () => {
    inp.closest('.inp-wrap').style.transform = 'scale(1)';
  });
});
</script>
</body>
</html>
