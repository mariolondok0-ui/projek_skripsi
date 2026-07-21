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
        $stmt->bind_param('s',$email); $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_id']    = $user['id'];
            $_SESSION['admin_nama']  = $user['nama'];
            $_SESSION['admin_email'] = $user['email'];
            setAlert('success','Selamat datang, '.$user['nama'].'!');
            redirect(APP_URL.'/admin/dashboard.php');
        } else { $error = 'Email atau password salah. Silakan coba lagi.'; }
    }
}
?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Login Admin – <?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head><body>
<div class="login-page">
  <div class="login-left">
    <div style="position:relative;z-index:2;width:100%;max-width:380px;text-align:center">
      <div class="ll-icon"><i class="fas fa-mosque" style="color:#fff"></i></div>
      <h2><?= MASJID_NAME ?></h2>
      <p>Sistem Informasi Pengelolaan Keuangan Kas Masjid Berbasis Web yang transparan, akurat, dan mudah digunakan.</p>
      <div class="login-features" style="margin-top:36px">
        <div class="login-feature delay-1"><div class="lf-icon"><i class="fas fa-shield-alt"></i></div><div class="lf-text"><h4>Aman & Terproteksi</h4><p>Akses dibatasi hanya untuk admin resmi</p></div></div>
        <div class="login-feature delay-2"><div class="lf-icon"><i class="fas fa-chart-bar"></i></div><div class="lf-text"><h4>Visualisasi Data</h4><p>Grafik interaktif untuk laporan keuangan</p></div></div>
        <div class="login-feature delay-3"><div class="lf-icon"><i class="fas fa-file-pdf"></i></div><div class="lf-text"><h4>Ekspor PDF</h4><p>Cetak laporan keuangan dalam format PDF</p></div></div>
      </div>
    </div>
  </div>
  <div class="login-right">
    <div class="login-box animate-scaleIn">
      <div class="lb-header">
        <div class="lb-logo"><i class="fas fa-mosque"></i></div>
        <h2>Login Admin</h2>
        <p>Masukkan kredensial Anda untuk mengakses panel administrasi</p>
      </div>
      <?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>
      <form method="POST" id="loginForm">
        <div class="form-group">
          <label class="form-label">Email <span class="required">*</span></label>
          <div class="input-group"><i class="fas fa-envelope input-icon"></i>
            <input type="email" name="email" class="form-control" placeholder="admin@masjid.com" value="<?= htmlspecialchars($_POST['email']??'') ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Password <span class="required">*</span></label>
          <div class="input-group" style="position:relative"><i class="fas fa-lock input-icon"></i>
            <input type="password" name="password" id="pwdInput" class="form-control" placeholder="Masukkan password" required>
            <button type="button" id="togglePwd" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;color:var(--text-muted);font-size:.9rem;padding:4px"><i class="fas fa-eye" id="pwdIcon"></i></button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-lg" id="loginBtn" style="margin-top:8px">
          <span id="btnText"><i class="fas fa-sign-in-alt"></i> Masuk</span>
          <span id="btnLoad" style="display:none"><span class="spinner"></span> Memproses...</span>
        </button>
      </form>
      <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border-light);text-align:center">
        <a href="<?= APP_URL ?>/index.php" style="font-size:.875rem;color:var(--text-muted);display:inline-flex;align-items:center;gap:6px"><i class="fas fa-arrow-left"></i> Kembali ke Halaman Publik</a>
      </div>
      <div style="margin-top:16px;padding:14px;background:var(--bg-main);border-radius:var(--radius-sm);font-size:.78rem;color:var(--text-muted);text-align:center">
        <i class="fas fa-info-circle" style="color:var(--info)"></i> Demo: <strong>admin@masjid.com</strong> / <strong>password</strong>
      </div>
    </div>
  </div>
</div>
<script>
document.getElementById('togglePwd').addEventListener('click',()=>{
  const i=document.getElementById('pwdInput'),c=document.getElementById('pwdIcon');
  i.type=i.type==='password'?'text':'password';
  c.classList.toggle('fa-eye');c.classList.toggle('fa-eye-slash');
});
document.getElementById('loginForm').addEventListener('submit',()=>{
  document.getElementById('btnText').style.display='none';
  document.getElementById('btnLoad').style.display='inline-flex';
  document.getElementById('loginBtn').disabled=true;
});
</script>
</body></html>
