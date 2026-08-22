<?php
require_once 'includes/config.php';

$error = '';

// Proses hanya berjalan jika tombol submit (POST) diklik
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        // Cek data user ke database dengan aman menggunakan prepared statement
        $stmt = $conn->prepare("SELECT id, nama, email, password FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // Validasi apakah email terdaftar dan passwordnya cocok
        if ($user && password_verify($password, $user['password'])) {
            // Set session login admin
            $_SESSION['admin_id']    = $user['id'];
            $_SESSION['admin_nama']  = $user['nama'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['user_id']     = $user['id'];

            // Redirect ke dashboard admin
            header("Location: admin/dashboard.php");
            exit;
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
  --primary: #1e6eb5;
  --primary-hover: #155a96;
  --text-dark: #1e293b;
  --text-secondary: #334155;
  --text-muted: #64748b;
  --card-bg-start: #f0f8ff;
  --card-bg-end: #dbeafe;
  --floating-shadow: 0 10px 25px rgba(30, 110, 181, 0.12);
}

* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Cambria', 'Times New Roman', Times, serif; }

body {
  background: linear-gradient(135deg, #0f2b48 0%, #1e6eb5 100%);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  position: relative;
}

body::before {
  content: ''; position: fixed; top: -100px; left: -100px;
  width: 450px; height: 450px; background: rgba(255,255,255,.03); border-radius: 50%;
  z-index: -1;
}
body::after {
  content: ''; position: fixed; bottom: -80px; right: -80px;
  width: 350px; height: 350px; background: rgba(255,255,255,.04); border-radius: 50%;
  z-index: -1;
}

.login-card {
  background: linear-gradient(145deg, var(--card-bg-start) 0%, var(--card-bg-end) 100%);
  width: 100%;
  max-width: 420px;
  border-radius: 24px;
  padding: 45px 35px;
  box-shadow: 0 25px 60px rgba(0,0,0,0.3);
  border: 1px solid #bfdbfe;
  text-align: center;
  position: relative;
  z-index: 1;
  animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
  margin: 20px 0; 
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.logo-box {
  width: 80px;
  height: 80px;
  background: var(--primary);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  box-shadow: 0 12px 28px rgba(30, 110, 181, 0.4);
  border: 2px solid #ffffff;
}
.logo-box i { font-size: 2.3rem; color: #ffffff; }

.login-title {
  font-size: 1.8rem;
  color: var(--text-dark);
  font-weight: bold;
  margin-bottom: 6px;
  text-shadow: 1px 1px 2px rgba(255,255,255,0.9);
}

.login-subtitle {
  font-size: 1.05rem;
  color: var(--text-muted);
  margin-bottom: 35px;
  line-height: 1.5;
}

.form-group { text-align: left; margin-bottom: 24px; }

.form-label {
  display: block;
  font-weight: bold;
  color: var(--text-secondary);
  margin-bottom: 8px;
  font-size: 1.05rem;
}
.form-label span { color: #e11d48; }

.input-wrapper { position: relative; }

.input-wrapper i.icon-left {
  position: absolute; left: 18px; top: 50%; transform: translateY(-50%);
  color: #94a3b8; font-size: 1.1rem; z-index: 2;
}

.form-control {
  width: 100%;
  padding: 15px 16px 15px 48px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #ffffff;
  color: var(--text-dark);
  font-size: 1.05rem;
  box-shadow: var(--floating-shadow);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.form-control:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 12px 25px rgba(30, 110, 181, 0.2);
  transform: translateY(-2px);
}

.inp-pw { padding-right: 46px; }

.btn-toggle-pass {
  position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
  background: transparent; border: none; color: #94a3b8; font-size: 1.1rem; z-index: 2; cursor: pointer;
}
.btn-toggle-pass:hover { color: var(--primary); }

.btn-submit {
  width: 100%;
  background: var(--primary);
  color: #ffffff;
  border: none;
  padding: 16px;
  border-radius: 14px;
  font-size: 1.15rem;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  margin-top: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 10px 25px rgba(30, 110, 181, 0.35);
}

.btn-submit:hover {
  background: var(--primary-hover);
  transform: translateY(-3px);
  box-shadow: 0 14px 30px rgba(30, 110, 181, 0.45);
}

.login-footer {
  margin-top: 30px;
  padding-top: 25px;
  border-top: 1px solid #bfdbfe;
}

.back-link {
  color: var(--text-muted);
  text-decoration: none;
  font-weight: bold;
  font-size: 1rem;
  transition: 0.3s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.back-link:hover { color: var(--primary); transform: translateX(-3px); }

.alert-error {
  background: #ffffff;
  color: #be123c;
  padding: 14px 16px;
  border-radius: 12px;
  margin-bottom: 25px;
  font-size: 0.95rem;
  border: 1px solid #fecdd3;
  border-left: 5px solid #e11d48; 
  box-shadow: var(--floating-shadow);
  text-align: left;
  display: flex;
  align-items: center;
  gap: 12px;
}

@media (max-width: 480px) {
  body { padding: 15px; }
  .login-card { padding: 35px 25px; margin: 15px 0; }
  .login-title { font-size: 1.6rem; }
  .logo-box { width: 70px; height: 70px; }
  .logo-box i { font-size: 2rem; }
}
</style>
</head>
<body>

<div class="login-card">
  <div class="logo-box">
    <i class="fas fa-mosque"></i>
  </div>
  <h2 class="login-title">Masjid Baeturrahman</h2>
  <p class="login-subtitle">Sistem Pengelolaan Kas Masjid</p>

  <?php if (!empty($error)): ?>
  <div class="alert-error">
    <i class="fas fa-exclamation-circle fs-5"></i>
    <span><?= htmlspecialchars($error) ?></span>
  </div>
  <?php endif; ?>

  <!-- METHOD WAJIB POST SUPAYA TIDAK LANGSUNG MASUK -->
  <form method="POST" id="loginForm" autocomplete="off">
    <div class="form-group">
      <label class="form-label">Email <span>*</span></label>
      <div class="input-wrapper">
        <input type="email" name="email" class="form-control" placeholder="Masukkan email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
        <i class="fas fa-envelope icon-left"></i>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Password <span>*</span></label>
      <div class="input-wrapper">
        <input type="password" name="password" id="pwdInput" class="form-control inp-pw" placeholder="••••••••" required autocomplete="current-password">
        <i class="fas fa-lock icon-left"></i>
        <button type="button" id="pwdToggle" class="btn-toggle-pass">
          <i class="fas fa-eye" id="pwdEye"></i>
        </button>
      </div>
    </div>

    <button type="submit" class="btn-submit" id="loginBtn">
      <span id="btnText"><i class="fas fa-sign-in-alt"></i> Masuk</span>
      <span id="btnLoad" style="display:none"><i class="fas fa-spinner fa-spin"></i> Memproses...</span>
    </button>
  </form>

  <div class="login-footer">
    <a href="index.php" class="back-link">
      <i class="fas fa-arrow-left"></i> Kembali ke Halaman Publik
    </a>
  </div>
</div>

<script>
document.getElementById('pwdToggle').addEventListener('click', () => {
  const inp = document.getElementById('pwdInput');
  const eye = document.getElementById('pwdEye');
  if (inp.type === 'password') {
    inp.type = 'text';
    eye.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    inp.type = 'password';
    eye.classList.replace('fa-eye-slash', 'fa-eye');
  }
});

document.getElementById('loginForm').addEventListener('submit', function() {
  document.getElementById('btnText').style.display = 'none';
  document.getElementById('btnLoad').style.display = 'inline-flex';
  document.getElementById('loginBtn').style.opacity = '0.8';
  document.getElementById('loginBtn').style.pointerEvents = 'none';
});
</script>
</body>
</html>