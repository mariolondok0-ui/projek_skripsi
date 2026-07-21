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
        $stmt->bind_param('s',$email); 
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
<title>Login Admin - <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body style="margin: 0; padding: 0; background: linear-gradient(135deg, #0b2e1d 0%, #1a7a4a 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center;">

<!-- Container Utama yang Menyatukan Hijau dan Putih Menjadi Satu Kartu (Card) -->
<div class="login-card-wrapper animate-scaleIn" style="display: flex; width: 100%; max-width: 900px; min-height: 520px; background: #fff; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.25); margin: 20px;">
  
  <!-- Bagian Kiri (Latar Belakang Hijau dengan Logo & Nama Masjid) -->
  <div style="flex: 1; background: linear-gradient(145deg, #0b2e1d 0%, #1a7a4a 100%); display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px; text-align: center; color: #fff; position: relative;">
    <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.15); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.2); backdrop-filter: blur(6px);">
      <i class="fas fa-mosque"></i>
    </div>
    <h2 style="font-size: 1.5rem; font-weight: 800; margin: 0; line-height: 1.4; letter-spacing: 0.5px;"><?= MASJID_NAME ?></h2>
  </div>

  <!-- Bagian Kanan (Form Login Latar Putih) -->
  <div style="flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 50px 40px; background: #fff;">
    <div style="margin-bottom: 24px;">
      <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary); margin-bottom: 6px;">Masuk Admin</h2>
      <p style="font-size: 0.83rem; color: var(--text-muted);">Silakan masukkan kredensial akun Anda</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger" style="margin-bottom: 16px; font-size: 0.85rem;">
      <i class="fas fa-exclamation-circle"></i> <?= $error ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="loginForm">
      <div class="form-group" style="margin-bottom: 16px;">
        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; margin-bottom: 4px; display: block;">Email <span class="required" style="color:var(--danger)">*</span></label>
        <div class="input-group" style="position: relative;">
          <i class="fas fa-envelope input-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
          <input type="email" name="email" class="form-control" placeholder="admin@masjid.com" value="<?= htmlspecialchars($_POST['email']??'') ?>" required style="padding-left: 42px; height: 42px; border-radius: 8px; font-size: 0.9rem;">
        </div>
      </div>

      <div class="form-group" style="margin-bottom: 20px;">
        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; margin-bottom: 4px; display: block;">Password <span class="required" style="color:var(--danger)">*</span></label>
        <div class="input-group" style="position: relative;">
          <i class="fas fa-lock input-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
          <input type="password" name="password" id="pwdInput" class="form-control" placeholder="••••••••" required style="padding-left: 42px; padding-right: 40px; height: 42px; border-radius: 8px; font-size: 0.9rem;">
          <button type="button" id="togglePwd" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 0.9rem;">
            <i class="fas fa-eye" id="pwdIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100" id="loginBtn" style="height: 42px; border-radius: 8px; font-weight: 600; background: var(--primary); font-size: 0.9rem;">
        <span id="btnText"><i class="fas fa-sign-in-alt" style="margin-right: 6px;"></i> Masuk ke Sistem</span>
        <span id="btnLoad" style="display:none"><span class="spinner"></span> Memproses...</span>
      </button>
    </form>

    <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border-light); text-align: center;">
      <a href="<?= APP_URL ?>/index.php" style="font-size: 0.82rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
        <i class="fas fa-arrow-left"></i> Kembali ke Halaman Publik
      </a>
    </div>

    <div style="margin-top: 12px; padding: 10px; background: var(--bg-main); border-radius: 8px; font-size: 0.75rem; color: var(--text-muted); text-align: center;">
      <i class="fas fa-info-circle" style="color: var(--info);"></i> Demo: <strong>admin@masjid.com</strong> / <strong>password</strong>
    </div>
  </div>

</div>

<script>
document.getElementById('togglePwd').addEventListener('click', () => {
  const i = document.getElementById('pwdInput');
  const c = document.getElementById('pwdIcon');
  i.type = i.type === 'password' ? 'text' : 'password';
  c.classList.toggle('fa-eye');
  c.classList.toggle('fa-eye-slash');
});

document.getElementById('loginForm').addEventListener('submit', () => {
  document.getElementById('btnText').style.display = 'none';
  document.getElementById('btnLoad').style.display = 'inline-flex';
  document.getElementById('loginBtn').disabled = true;
});
</script>
</body>
</html>